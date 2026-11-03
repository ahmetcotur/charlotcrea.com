<?php

namespace Tests\Feature;

use App\Mail\RevisionRequestedMail;
use App\Mail\WorkApprovedMail;
use App\Models\Customer;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class WorkDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function deliveredWork(array $overrides = []): Work
    {
        $customer = Customer::create(['title' => 'Ada Yılmaz', 'email' => 'ada@example.test']);

        return Work::create(array_merge([
            'title' => 'Düğün çekimi', 'customer_id' => $customer->id, 'status' => 'in_progress',
            'delivered_at' => now(), 'delivery_note' => 'Galeri: https://example.test/galeri',
        ], $overrides));
    }

    private function signedShowUrl(Work $work): string
    {
        return URL::temporarySignedRoute('public.works.show', now()->addDays(30), ['work' => $work->id]);
    }

    public function test_visiting_a_delivered_work_shows_approve_and_revision_actions(): void
    {
        $work = $this->deliveredWork();

        $this->get($this->signedShowUrl($work))->assertOk()->assertSee('Teslimatı onayla')->assertSee('Revizyon talep et');
    }

    public function test_an_undelivered_work_shows_no_actions(): void
    {
        $customer = Customer::create(['title' => 'Ada Yılmaz', 'email' => 'ada@example.test']);
        $work = Work::create(['title' => 'Düğün çekimi', 'customer_id' => $customer->id]);

        $this->get($this->signedShowUrl($work))->assertOk()->assertDontSee('Teslimatı onayla');
    }

    public function test_customer_can_approve_a_delivered_work(): void
    {
        Mail::fake();
        $work = $this->deliveredWork();
        $approveUrl = URL::temporarySignedRoute('public.works.approve', now()->addDays(30), ['work' => $work->id]);

        $this->post($approveUrl)->assertOk()->assertSee('onayladınız');

        $this->assertSame('completed', $work->refresh()->status);
        Mail::assertQueued(WorkApprovedMail::class);
    }

    public function test_customer_can_request_a_revision(): void
    {
        Mail::fake();
        $work = $this->deliveredWork();
        $revisionUrl = URL::temporarySignedRoute('public.works.requestRevision', now()->addDays(30), ['work' => $work->id]);

        $this->post($revisionUrl, ['customer_note' => 'Daha fazla manzara fotoğrafı istiyorum'])
            ->assertOk()->assertSee('gönderildi');

        $this->assertDatabaseHas('revision_requests', [
            'work_id' => $work->id, 'status' => 'requested', 'customer_note' => 'Daha fazla manzara fotoğrafı istiyorum',
        ]);
        Mail::assertQueued(RevisionRequestedMail::class);
    }

    public function test_revision_request_requires_a_note(): void
    {
        $work = $this->deliveredWork();
        $revisionUrl = URL::temporarySignedRoute('public.works.requestRevision', now()->addDays(30), ['work' => $work->id]);

        $this->post($revisionUrl, [])->assertSessionHasErrors('customer_note');
    }

    public function test_work_with_an_open_revision_request_cannot_be_approved_again(): void
    {
        $work = $this->deliveredWork();
        $work->revisionRequests()->create(['customer_note' => 'Lütfen gözden geçirin']);

        $this->get($this->signedShowUrl($work))->assertOk()->assertDontSee('Teslimatı onayla');
    }
}
