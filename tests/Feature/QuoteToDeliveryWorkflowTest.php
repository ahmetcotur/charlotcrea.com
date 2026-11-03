<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use App\Mail\QuoteSentMail;
use App\Mail\WorkDeliveredMail;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class QuoteToDeliveryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_quote_to_approved_delivery_workflow(): void
    {
        Mail::fake();
        $admin = Admin::forceCreate(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => bcrypt('test-only'), 'role' => 'SUPERADMIN', 'published' => true, 'registered_at' => now()]);
        $customer = Customer::create(['title' => 'Ada', 'email' => 'ada@example.test']);
        $this->actingAs($admin, 'twill_users');
        $this->post(route('admin.quotes.store'), [
            'title' => 'Çekim paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'currency' => 'EUR',
            'scope' => 'Çekim ve teslim', 'items' => [['title' => 'Fotoğraf', 'quantity' => 1, 'unit_price' => 1500]],
        ])->assertRedirect();
        $quote = Quote::sole();
        $this->post(route('admin.quotes.send', $quote))->assertRedirect();
        Mail::assertQueued(QuoteSentMail::class);
        $this->app['auth']->forgetGuards();
        $this->get($quote->signedShowUrl())->assertOk()->assertSee('Teklifi kabul et');
        $this->post(URL::temporarySignedRoute('public.quotes.accept', now()->addMinutes(30), ['quote' => $quote->id]))->assertOk();
        $work = Work::sole();
        $this->assertSame('planned', $work->status);
        $this->actingAs($admin, 'twill_users');
        $this->patchJson(route('admin.works.update-status', $work), ['status' => 'in_progress'])->assertOk();
        $this->post(route('admin.works.deliver', $work), ['delivery_note' => 'Fotoğraflarınız: https://example.test/galeri'])->assertRedirect();
        Mail::assertQueued(WorkDeliveredMail::class);
        $this->app['auth']->forgetGuards();
        $this->get($work->signedShowUrl())->assertOk()->assertSee('Teslimatı onayla');
        $url = URL::temporarySignedRoute('public.works.approve', now()->addMinutes(30), ['work' => $work->id]);
        $this->post($url)->assertOk();
        $this->assertSame('completed', $work->refresh()->status);
        $this->post($url)->assertOk();
        $this->assertSame(1, $work->statusEvents()->where('to_status', 'completed')->count());
        $this->assertDatabaseCount('works', 1);
    }

    public function test_undelivered_cancelled_and_open_revision_work_cannot_be_approved(): void
    {
        Mail::fake();
        $customer = Customer::create(['title' => 'Ada']);
        foreach (['undelivered', 'cancelled', 'revision'] as $case) {
            $work = Work::create(['title' => $case, 'customer_id' => $customer->id, 'status' => $case === 'cancelled' ? 'cancelled' : 'in_progress', 'delivered_at' => $case === 'undelivered' ? null : now()]);
            if ($case === 'revision') {
                $work->revisionRequests()->create(['customer_note' => 'Düzeltme']);
            }
            $url = URL::temporarySignedRoute('public.works.approve', now()->addMinutes(30), ['work' => $work->id]);
            $this->post($url)->assertStatus(409);
            $this->assertNotSame('completed', $work->refresh()->status);
        }
        Mail::assertNothingQueued();
    }

    public function test_revision_cannot_be_submitted_twice_or_after_completion(): void
    {
        Mail::fake();
        $customer = Customer::create(['title' => 'Ada']);
        $work = Work::create(['title' => 'Çekim', 'customer_id' => $customer->id, 'delivered_at' => now()]);
        $url = URL::temporarySignedRoute('public.works.requestRevision', now()->addMinutes(30), ['work' => $work->id]);
        $this->post($url, ['customer_note' => 'Düzeltme'])->assertOk();
        $this->post($url, ['customer_note' => 'Düzeltme'])->assertStatus(409);
        $this->assertDatabaseCount('revision_requests', 1);
        $work->update(['status' => 'completed']);
        $this->post($url, ['customer_note' => 'Düzeltme'])->assertStatus(409);
    }
}
