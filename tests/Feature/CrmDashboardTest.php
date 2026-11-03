<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge(['title' => 'Ada Yılmaz', 'email' => 'ada@example.test'], $overrides));
    }

    public function test_guest_cannot_access_the_crm_dashboard(): void
    {
        $this->get('/admin/crm')->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_groups_quotes_and_works_by_status(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'sent', 'amount' => 1000]);
        Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'in_progress']);

        $this->get('/admin/crm')->assertOk()->assertSee('Düğün paketi')->assertSee('Düğün çekimi');
    }

    public function test_date_range_is_inclusive_and_can_use_delivery_dates(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        foreach ([['Boundary start', '2030-06-01'], ['Boundary end', '2030-06-30'], ['Outside range', '2030-07-01'], ['Undated work', null]] as [$title, $date]) {
            Work::create(['title' => $title, 'customer_id' => $customer->id, 'starts_at' => '2029-01-01', 'due_at' => $date]);
        }
        $this->get('/admin/crm?date_field=due_at&from=2030-06-01&to=2030-06-30&year=2030')->assertOk()->assertSee('Boundary start')->assertSee('Boundary end')->assertDontSee('Outside range')->assertDontSee('Undated work');
        $this->get('/admin/crm?date_field=due_at&to=2030-06-01')->assertOk()->assertSee('Boundary start')->assertDontSee('Boundary end');
        $this->get('/admin/crm?date_field=due_at&year=2029')->assertOk()->assertDontSee('Boundary start');
    }

    public function test_upcoming_shoots_exclude_past_completed_cancelled_and_undated_work(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        foreach ([['Today shoot', today(), 'planned'], ['Future shoot', today()->addDay(), 'in_progress'], ['Past shoot', today()->subDay(), 'planned'], ['Completed shoot', today()->addDay(), 'completed'], ['Cancelled shoot', today()->addDay(), 'cancelled'], ['No shoot date', null, 'planned']] as [$title, $date, $status]) {
            Work::create(['title' => $title, 'customer_id' => $customer->id, 'starts_at' => $date, 'status' => $status]);
        }
        $this->get('/admin/crm?view=upcoming')->assertOk()->assertSee('Today shoot')->assertSee('Future shoot')->assertDontSee('Past shoot')->assertDontSee('Completed shoot')->assertDontSee('Cancelled shoot')->assertDontSee('No shoot date');
    }

    public function test_approved_view_filters_quotes_by_acceptance_date_and_works_by_shoot_date(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create(['title' => 'Approved proposal', 'customer_id' => $customer->id, 'status' => 'accepted', 'accepted_at' => '2031-02-01']);
        $work = $quote->works()->firstOrFail();
        $work->update(['title' => 'Approved shoot', 'starts_at' => '2031-05-01']);
        Quote::create(['title' => 'Unapproved proposal', 'customer_id' => $customer->id, 'status' => 'sent']);
        Work::create(['title' => 'Manual shoot', 'customer_id' => $customer->id, 'starts_at' => '2031-05-01']);
        $this->get('/admin/crm?view=approved&year=2031')->assertOk()->assertSee('Approved proposal')->assertSee('Approved shoot')->assertDontSee('Unapproved proposal')->assertDontSee('Manual shoot');
        $this->get('/admin/crm?view=approved&year=2030')->assertOk()->assertDontSee('Approved proposal')->assertDontSee('Approved shoot');
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $this->actingAsAdmin();
        $this->getJson('/admin/crm?date_field=internal_notes&year=invalid&from=2030-06-30&to=2030-06-01')->assertUnprocessable()->assertJsonValidationErrors(['date_field', 'year', 'to']);
    }

    public function test_dragging_a_quote_card_updates_its_status(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'amount' => 1000]);

        $this->patchJson("/admin/quotes/{$quote->id}/status", ['status' => 'sent'])
            ->assertOk()->assertJson(['status' => 'sent']);

        $this->assertSame('sent', $quote->refresh()->status);
        $this->assertDatabaseHas('quote_status_events', ['quote_id' => $quote->id, 'from_status' => 'draft', 'to_status' => 'sent']);
    }

    public function test_quote_status_must_be_a_known_value(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'amount' => 1000]);

        $this->patchJson("/admin/quotes/{$quote->id}/status", ['status' => 'not-a-status'])
            ->assertUnprocessable();

        $this->assertSame('draft', $quote->refresh()->status);
    }

    public function test_dragging_a_work_card_updates_its_status(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'planned']);

        $this->patchJson("/admin/works/{$work->id}/status", ['status' => 'in_progress'])
            ->assertOk()->assertJson(['status' => 'in_progress']);

        $this->assertSame('in_progress', $work->refresh()->status);
        $this->assertDatabaseHas('work_status_events', ['work_id' => $work->id, 'from_status' => 'planned', 'to_status' => 'in_progress']);
    }

    public function test_work_status_must_be_a_known_value(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'planned']);

        $this->patchJson("/admin/works/{$work->id}/status", ['status' => 'not-a-status'])
            ->assertUnprocessable();

        $this->assertSame('planned', $work->refresh()->status);
    }
}
