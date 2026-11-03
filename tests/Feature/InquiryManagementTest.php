<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function inquiry(array $overrides = []): Inquiry
    {
        return Inquiry::create(array_merge([
            'name' => 'Sophie Laurent', 'email' => 'sophie@example.test', 'property' => 'Villa',
            'country' => 'Türkiye', 'city' => 'Bodrum', 'service' => 'Fotoğraf', 'details' => 'Düğün çekimi istiyorum',
            'locale' => 'en', 'status' => 'new', 'received_at' => now(),
        ], $overrides));
    }

    public function test_guest_cannot_access_inquiries(): void
    {
        $this->get('/admin/inquiries')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_and_search_inquiries(): void
    {
        $this->actingAsAdmin();
        $this->inquiry(['name' => 'Sophie Laurent']);
        $this->inquiry(['name' => 'Mert Kaya', 'email' => 'mert@example.test']);

        $this->get('/admin/inquiries')->assertOk()->assertSee('Sophie Laurent')->assertSee('Mert Kaya');
        $this->get('/admin/inquiries?q=Sophie')->assertOk()->assertSee('Sophie Laurent')->assertDontSee('Mert Kaya');
    }

    public function test_admin_can_view_an_inquiry(): void
    {
        $this->actingAsAdmin();
        $inquiry = $this->inquiry();

        $this->get("/admin/inquiries/{$inquiry->id}")->assertOk()->assertSee('Düğün çekimi istiyorum');
    }

    public function test_admin_can_update_inquiry_status(): void
    {
        $this->actingAsAdmin();
        $inquiry = $this->inquiry();

        $this->put("/admin/inquiries/{$inquiry->id}", ['status' => 'replied', 'internal_notes' => 'Teklif gönderildi'])
            ->assertRedirect();

        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->id, 'status' => 'replied', 'internal_notes' => 'Teklif gönderildi']);
    }

    public function test_inquiry_status_must_be_valid(): void
    {
        $this->actingAsAdmin();
        $inquiry = $this->inquiry();

        $this->put("/admin/inquiries/{$inquiry->id}", ['status' => 'not-a-real-status'])
            ->assertSessionHasErrors('status');
    }

    public function test_archiving_an_inquiry_soft_deletes_it(): void
    {
        $this->actingAsAdmin();
        $inquiry = $this->inquiry();

        $this->delete("/admin/inquiries/{$inquiry->id}")->assertRedirect(route('admin.inquiries.index'));

        $this->assertSoftDeleted('inquiries', ['id' => $inquiry->id]);
    }
}
