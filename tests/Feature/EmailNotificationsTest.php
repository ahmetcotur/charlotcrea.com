<?php

namespace Tests\Feature;

use App\Mail\CustomerInvitationMail;
use App\Mail\CustomerPasswordMail;
use App\Mail\InquiryReceiptMail;
use App\Mail\InquiryReceivedMail;
use App\Mail\QuoteSentMail;
use App\Mail\RevisionResolvedMail;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Inquiry;
use App\Models\Quote;
use App\Models\Work;
use App\Services\MailPreviews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_queues_customer_receipt_in_form_language_and_admin_notification_after_commit(): void
    {
        Mail::fake();
        $this->postJson('/contact-inquiries', ['name' => 'Alex', 'email' => 'alex@example.test', 'details' => 'A new hotel project', 'locale' => 'en', 'consent' => 1])->assertCreated();
        $inquiry = Inquiry::sole();
        $this->assertSame('en', $inquiry->customer->preferred_locale);
        Mail::assertQueued(InquiryReceiptMail::class, fn ($mail) => $mail->hasTo('alex@example.test') && $mail->language === 'en' && $mail->afterCommit === true);
        Mail::assertQueued(InquiryReceivedMail::class, fn ($mail) => $mail->hasTo(config('charlot.admin_notification_email')) && $mail->language === 'tr' && $mail->afterCommit === true);
        Mail::assertQueuedCount(2);
        $receipt = new InquiryReceiptMail($inquiry);
        $receipt->assertSeeInHtml('We received your message')->assertSeeInHtml('A new hotel project')->assertDontSeeInHtml('/admin/inquiries/');
        $admin = new InquiryReceivedMail($inquiry);
        $admin->render();
        $this->assertSame('alex@example.test', $admin->replyTo[0]['address']);
    }

    public function test_quote_receipt_is_distinct_and_anonymous_forms_do_not_override_customer_language(): void
    {
        Mail::fake();
        $customer = Customer::create(['title' => 'Existing customer', 'email' => 'existing@example.test', 'preferred_locale' => 'en']);
        $this->postJson('/quote-requests', ['name' => 'Someone', 'email' => $customer->email, 'details' => 'Two shooting days', 'locale' => 'tr', 'consent' => 1, 'property' => 'Test hotel', 'country' => 'Türkiye', 'city' => 'Antalya', 'service' => 'Photography', 'project_type' => 'Hotel', 'usage' => 'Website', 'deliverables' => '30 images'])->assertCreated();
        $this->assertSame('en', $customer->fresh()->preferred_locale);
        $receipt = new InquiryReceiptMail(Inquiry::sole());
        $receipt->assertSeeInHtml('Proje talebinizi aldık')->assertSeeInHtml('fiyat teklifi veya rezervasyon onayı değildir');
        $admin = new InquiryReceivedMail(Inquiry::sole());
        $admin->assertSeeInHtml('Yeni proje teklif talebi')->assertSeeInHtml('30 images');
        Mail::assertQueuedCount(2);
    }

    public function test_invalid_and_bot_submissions_do_not_queue_email(): void
    {
        Mail::fake();
        $this->postJson('/contact-inquiries', ['website' => 'spam'])->assertCreated();
        $this->postJson('/contact-inquiries', ['name' => 'Bot', 'email' => 'bad'])->assertUnprocessable();
        $this->assertDatabaseCount('inquiries', 0);
        Mail::assertNothingQueued();
    }

    public function test_invitation_and_password_emails_use_the_persisted_customer_language(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = Customer::create(['title' => 'English customer', 'email' => 'english@example.test', 'preferred_locale' => 'en']);
        $this->post(route('admin.customers.access.invite', $customer))->assertRedirect()->assertSessionHas('customer_invite_url');
        Mail::assertQueued(CustomerInvitationMail::class, fn ($mail) => $mail->language === 'en' && str_contains($mail->inviteUrl, 'lang=en') && $mail->afterCommit);
        $this->travel(61)->seconds();
        $this->post('/panel/forgot-password?lang=tr', ['email' => $customer->email])->assertRedirect();
        Mail::assertQueued(CustomerPasswordMail::class, fn ($mail) => $mail->language === 'en' && str_contains($mail->resetUrl, 'lang=en'));
        $this->assertDatabaseCount('customer_accounts', 1);
        $this->assertNull(CustomerAccount::sole()->activated_at);
    }

    public function test_queued_quote_preserves_english_locale_and_only_exposes_customer_data(): void
    {
        $customer = Customer::create(['title' => 'English customer', 'email' => 'english@example.test', 'preferred_locale' => 'en', 'notes' => 'PRIVATE CUSTOMER NOTE']);
        $quote = Quote::create(['customer_id' => $customer->id, 'title' => 'Hotel project', 'status' => 'sent', 'amount' => 5000, 'internal_notes' => 'PRIVATE QUOTE NOTE']);
        $mail = unserialize(serialize(new QuoteSentMail($quote, 'https://example.test/quote')));
        $this->assertSame('en', $mail->language);
        $this->assertSame(3, $mail->tries);
        $mail->assertSeeInHtml('Your quote is ready')->assertSeeInHtml('5,000.00')->assertDontSeeInHtml('PRIVATE QUOTE NOTE')->assertDontSeeInHtml('PRIVATE CUSTOMER NOTE');
        $mail->assertSeeInText('Your quote is ready')->assertSeeInText('https://example.test/quote');
    }

    public function test_all_branded_previews_render_in_both_languages_without_sending_or_creating_records(): void
    {
        Mail::fake();
        $previews = app(MailPreviews::class);
        foreach (['tr', 'en'] as $language) {
            foreach (array_keys(MailPreviews::TYPES) as $type) {
                $mail = $previews->make($type, $language);
                $html = $mail->render();
                $this->assertStringContainsString('assets/logo.png', $html, $type);
                $this->assertStringContainsString('<html lang="'.$mail->language.'">', $html, $type);
                $this->assertStringContainsString('info@charlotcrea.com', $html, $type);
                $this->assertStringNotContainsString('mail.', $mail->subject, $type);
            }
        }
        $previews->make('delivery', 'en')->assertSeeInHtml('Your delivery is ready to review')->assertSeeInHtml('final approval is yours');
        $previews->make('revision-accepted', 'en')->assertSeeInHtml('does not mean the changes have been delivered');
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('inquiries', 0);
        Mail::assertNothingQueued();
    }

    public function test_submitted_copy_is_escaped_in_html_and_retained_in_plain_text(): void
    {
        $inquiry = new Inquiry(['name' => '<script>alert(1)</script>', 'email' => 'safe@example.test', 'type' => 'contact', 'locale' => 'en', 'details' => '<img src=x onerror=alert(1)>']);
        $inquiry->id = 1;
        $mail = new InquiryReceiptMail($inquiry);
        $html = $mail->render();
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('&lt;img', $html);
    }

    public function test_preview_routes_are_private_and_read_only(): void
    {
        Mail::fake();
        $this->get('/admin/mail-previews')->assertRedirect(route('admin.login'));
        $this->get('/admin/mail-previews/delivery?lang=en')->assertRedirect(route('admin.login'));
        $this->actingAsAdmin();
        $this->get('/admin/mail-previews?type=delivery&lang=en')->assertOk()->assertSee('E-posta önizlemeleri');
        $this->get('/admin/mail-previews/delivery?lang=en')->assertOk()->assertSee('Your delivery is ready to review')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/admin/mail-previews/unknown')->assertNotFound();
        Mail::assertNothingQueued();
    }

    public function test_english_customer_email_links_open_english_quote_delivery_and_results(): void
    {
        Mail::fake();
        $customer = Customer::create(['title' => 'English customer', 'email' => 'english@example.test', 'preferred_locale' => 'en']);
        $quote = Quote::create(['title' => 'English project', 'customer_id' => $customer->id, 'status' => 'sent', 'amount' => 1000, 'published' => true]);
        $this->get($quote->signedShowUrl())->assertOk()->assertSee('<html lang="en">', false)->assertSee('Download PDF')->assertSee('Accept quote')->assertSee('1,000.00');
        $url = URL::temporarySignedRoute('public.quotes.pdf', now()->addMinutes(10), ['quote' => $quote->id]);
        $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $url = URL::temporarySignedRoute('public.quotes.accept', now()->addMinutes(10), ['quote' => $quote->id]);
        $this->post($url)->assertOk()->assertSee('You accepted the quote');
        $work = $quote->works()->firstOrFail();
        $work->update(['delivered_at' => now()]);
        $this->get($work->signedShowUrl())->assertOk()->assertSee('<html lang="en">', false)->assertSee('Approve delivery')->assertSee('Request a revision');
        $url = URL::temporarySignedRoute('public.works.approve', now()->addMinutes(10), ['work' => $work->id]);
        $this->post($url)->assertOk()->assertSee('You approved the delivery');
    }

    public function test_resolving_the_same_revision_twice_does_not_duplicate_mail_or_overwrite_response(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = Customer::create(['title' => 'Customer', 'email' => 'customer@example.test']);
        $work = Work::create(['title' => 'Project', 'customer_id' => $customer->id]);
        $revision = $work->revisionRequests()->create(['status' => 'requested', 'customer_note' => 'Please revise', 'requested_at' => now()]);
        $route = route('admin.revision-requests.update', $revision);
        $this->put($route, ['status' => 'approved', 'admin_response' => 'First response'])->assertRedirect();
        $this->put($route, ['status' => 'rejected', 'admin_response' => 'Duplicate response'])->assertRedirect();
        Mail::assertQueued(RevisionResolvedMail::class, 1);
        $this->assertSame('First response', $revision->fresh()->admin_response);
        $this->assertSame('approved', $revision->fresh()->status);
    }
}
