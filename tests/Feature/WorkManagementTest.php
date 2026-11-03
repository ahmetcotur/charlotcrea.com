<?php

namespace Tests\Feature;

use App\Mail\WorkDeliveredMail;
use App\Models\Customer;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WorkManagementTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge(['title' => 'Ada Yılmaz', 'email' => 'ada@example.test'], $overrides));
    }

    public function test_guest_cannot_access_works(): void
    {
        $this->get('/admin/works')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_and_search_works(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);
        Work::create(['title' => 'Kurumsal video', 'customer_id' => $customer->id]);

        $this->get('/admin/works')->assertOk()->assertSee('Düğün çekimi')->assertSee('Kurumsal video');
        $this->get('/admin/works?'.http_build_query(['q' => 'Düğün']))->assertOk()->assertSee('Düğün çekimi')->assertDontSee('Kurumsal video');
    }

    public function test_admin_can_create_a_work(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post('/admin/works', ['title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'planned'])
            ->assertRedirect();

        $this->assertDatabaseHas('works', ['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);
    }

    public function test_creating_a_work_records_an_initial_status_event(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post('/admin/works', ['title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'planned']);

        $work = Work::firstWhere('title', 'Düğün çekimi');
        $this->assertDatabaseHas('work_status_events', ['work_id' => $work->id, 'from_status' => null, 'to_status' => 'planned']);
    }

    public function test_updating_a_work_status_records_a_status_event(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'planned']);

        $this->put("/admin/works/{$work->id}", ['title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'in_progress'])
            ->assertRedirect();

        $work->refresh();
        $this->assertSame('in_progress', $work->status);
        $event = $work->statusEvents()->latest()->first();
        $this->assertSame('planned', $event->from_status);
        $this->assertSame('in_progress', $event->to_status);
    }

    public function test_edit_page_shows_status_history_and_revision_requests(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);
        $work->revisionRequests()->create(['customer_note' => 'Daha fazla manzara fotoğrafı istiyorum']);

        $this->get("/admin/works/{$work->id}/edit")->assertOk()->assertSee('Daha fazla manzara fotoğrafı istiyorum');
    }

    public function test_archiving_a_work_soft_deletes_it(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);

        $this->delete("/admin/works/{$work->id}")->assertRedirect(route('admin.works.index'));

        $this->assertSoftDeleted('works', ['id' => $work->id]);
    }

    public function test_delivering_a_work_requires_a_delivery_note(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);

        $this->post("/admin/works/{$work->id}/deliver", [])->assertSessionHasErrors('delivery_note');
    }

    public function test_delivering_a_work_without_customer_email_still_records_delivery_but_skips_mail(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = $this->customer(['email' => null]);
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);

        $this->post("/admin/works/{$work->id}/deliver", ['delivery_note' => 'Galeri bağlantısı: ...'])
            ->assertSessionHas('success');

        Mail::assertNothingQueued();
        $this->assertNotNull($work->refresh()->delivered_at);
    }

    public function test_delivering_a_work_sets_timestamps_logs_timeline_and_queues_mail(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = $this->customer();
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'in_progress']);

        $this->post("/admin/works/{$work->id}/deliver", ['delivery_note' => 'Galeri bağlantısı: https://example.test/galeri'])
            ->assertRedirect();

        $work->refresh();
        $this->assertNotNull($work->delivered_at);
        $this->assertSame('Galeri bağlantısı: https://example.test/galeri', $work->delivery_note);
        $this->assertDatabaseHas('work_status_events', ['work_id' => $work->id, 'from_status' => 'in_progress', 'to_status' => 'in_progress']);
        Mail::assertQueued(WorkDeliveredMail::class);
    }

    public function test_copying_a_work_link_has_no_side_effects(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = $this->customer();
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);

        $this->get("/admin/works/{$work->id}/link")->assertOk()->assertJsonStructure(['url']);

        Mail::assertNothingQueued();
        $this->assertNull($work->refresh()->delivered_at);
    }
}
