<?php

namespace Tests\Feature;

use App\Mail\RevisionResolvedMail;
use App\Models\Customer;
use App\Models\RevisionRequest;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RevisionRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private function openRevision(): RevisionRequest
    {
        $customer = Customer::create(['title' => 'Ada Yılmaz', 'email' => 'ada@example.test']);
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);

        return $work->revisionRequests()->create(['customer_note' => 'Daha fazla manzara fotoğrafı istiyorum']);
    }

    public function test_guest_cannot_access_revision_requests(): void
    {
        $this->get('/admin/revision-requests')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_open_revision_requests_across_works(): void
    {
        $this->actingAsAdmin();
        $this->openRevision();

        $this->get('/admin/revision-requests')->assertOk()->assertSee('Daha fazla manzara fotoğrafı istiyorum');
    }

    public function test_resolved_revision_requests_are_not_listed(): void
    {
        $this->actingAsAdmin();
        $revision = $this->openRevision();
        $revision->update(['status' => 'approved', 'admin_response' => 'Tamam', 'resolved_at' => now()]);

        $this->get('/admin/revision-requests')->assertOk()->assertDontSee('Daha fazla manzara fotoğrafı istiyorum');
    }

    public function test_resolving_a_revision_request_requires_an_admin_response(): void
    {
        $this->actingAsAdmin();
        $revision = $this->openRevision();

        $this->put("/admin/revision-requests/{$revision->id}", ['status' => 'approved'])
            ->assertSessionHasErrors('admin_response');
    }

    public function test_resolving_a_revision_request_requires_a_valid_status(): void
    {
        $this->actingAsAdmin();
        $revision = $this->openRevision();

        $this->put("/admin/revision-requests/{$revision->id}", ['status' => 'maybe', 'admin_response' => 'Tamam'])
            ->assertSessionHasErrors('status');
    }

    public function test_admin_can_approve_a_revision_request_and_notify_the_customer(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $revision = $this->openRevision();

        $this->put("/admin/revision-requests/{$revision->id}", ['status' => 'approved', 'admin_response' => 'Yeni fotoğraflar eklendi'])
            ->assertRedirect();

        $revision->refresh();
        $this->assertSame('approved', $revision->status);
        $this->assertSame('Yeni fotoğraflar eklendi', $revision->admin_response);
        $this->assertNotNull($revision->resolved_at);
        Mail::assertQueued(RevisionResolvedMail::class);
    }

    public function test_admin_can_reject_a_revision_request(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $revision = $this->openRevision();

        $this->put("/admin/revision-requests/{$revision->id}", ['status' => 'rejected', 'admin_response' => 'Kapsam dışında'])
            ->assertRedirect();

        $this->assertSame('rejected', $revision->refresh()->status);
        Mail::assertQueued(RevisionResolvedMail::class);
    }
}
