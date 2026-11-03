<?php

namespace Tests\Feature;

use App\Mail\QuoteAcceptedMail;
use App\Mail\QuoteDeclinedMail;
use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class QuoteApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function sentQuote(array $overrides = []): Quote
    {
        $customer = Customer::create(['title' => 'Ada Yılmaz', 'email' => 'ada@example.test']);

        return Quote::create(array_merge([
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'sent',
            'amount' => 4200, 'currency' => 'EUR',
        ], $overrides));
    }

    private function signedShowUrl(Quote $quote): string
    {
        return URL::temporarySignedRoute('public.quotes.show', now()->addMinutes(30), ['quote' => $quote->id]);
    }

    public function test_visiting_a_valid_signed_link_shows_the_quote(): void
    {
        $quote = $this->sentQuote();

        $this->get($this->signedShowUrl($quote))->assertOk()->assertSee('Düğün paketi')->assertSee('Teklifi kabul et');
    }

    public function test_pdf_is_a_branded_download_and_requires_a_valid_signature(): void
    {
        $quote = $this->sentQuote(['scope' => 'Türkçe çekim kapsamı', 'internal_notes' => 'PRIVATE ADMIN NOTE']);
        $quote->syncItems([['title' => 'Fotoğraf çekimi', 'quantity' => 2, 'unit_price' => 1250]]);
        $url = URL::temporarySignedRoute('public.quotes.pdf', now()->addMinutes(30), ['quote' => $quote->id]);
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
        $this->get(route('public.quotes.pdf', $quote))->assertForbidden();
        $this->get(URL::temporarySignedRoute('public.quotes.pdf', now()->subMinute(), ['quote' => $quote->id]))->assertForbidden();
        $this->get($this->signedShowUrl($quote))->assertOk()->assertSee('PDF indir')->assertSee('assets/logo.png')->assertSee('Fotoğraf çekimi')->assertDontSee('PRIVATE ADMIN NOTE');
        $this->assertSame('sent', $quote->fresh()->status);
    }

    public function test_visiting_a_tampered_signed_link_is_rejected_without_leaking_data(): void
    {
        $quote = $this->sentQuote();
        $url = preg_replace('/signature=\w+/', 'signature=0000000000000000000000000000000000000000000000000000000000000000', $this->signedShowUrl($quote));

        $response = $this->get($url);

        $response->assertStatus(403);
        $response->assertDontSee('Düğün paketi');
        $response->assertDontSee((string) $quote->amount);
    }

    public function test_visiting_an_expired_signature_is_rejected(): void
    {
        $quote = $this->sentQuote();
        $url = URL::temporarySignedRoute('public.quotes.show', now()->subMinute(), ['quote' => $quote->id]);

        $this->get($url)->assertStatus(403)->assertSee('süresi doldu');
    }

    public function test_an_overdue_quote_flips_to_expired_when_viewed(): void
    {
        $quote = $this->sentQuote(['valid_until' => now()->subDay()]);

        $this->get($this->signedShowUrl($quote))->assertOk()->assertDontSee('Teklifi kabul et');

        $this->assertSame('expired', $quote->refresh()->status);
    }

    public function test_customer_can_accept_a_sent_quote(): void
    {
        Mail::fake();
        $quote = $this->sentQuote();
        $acceptUrl = URL::temporarySignedRoute('public.quotes.accept', now()->addMinutes(30), ['quote' => $quote->id]);

        $this->post($acceptUrl)->assertOk()->assertSee('kabul ettiniz');

        $quote->refresh();
        $this->assertSame('accepted', $quote->status);
        $this->assertNotNull($quote->accepted_at);
        $event = $quote->statusEvents()->latest()->first();
        $this->assertNull($event->changed_by);
        $this->assertStringContainsString('Müşteri işlemi', $event->note);
        Mail::assertQueued(QuoteAcceptedMail::class);
    }

    public function test_customer_can_decline_a_sent_quote(): void
    {
        Mail::fake();
        $quote = $this->sentQuote();
        $declineUrl = URL::temporarySignedRoute('public.quotes.decline', now()->addMinutes(30), ['quote' => $quote->id]);

        $this->post($declineUrl)->assertOk()->assertSee('reddettiniz');

        $this->assertSame('declined', $quote->refresh()->status);
        Mail::assertQueued(QuoteDeclinedMail::class);
    }

    public function test_accepting_an_already_resolved_quote_is_a_no_op(): void
    {
        Mail::fake();
        $quote = $this->sentQuote(['status' => 'declined']);
        $acceptUrl = URL::temporarySignedRoute('public.quotes.accept', now()->addMinutes(30), ['quote' => $quote->id]);

        $this->post($acceptUrl)->assertOk();

        $this->assertSame('declined', $quote->refresh()->status);
        Mail::assertNothingQueued();
    }
}
