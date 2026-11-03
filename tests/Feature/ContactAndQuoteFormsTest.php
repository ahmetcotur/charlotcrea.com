<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactAndQuoteFormsTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->artisan('charlot:prepare-enquiry-pages')->assertSuccessful();
    }

    public function test_contact_and_quote_pages_are_separate_in_each_language_and_footer_is_professional(): void
    {
        $this->prepare();
        foreach (['tr' => ['/tr/iletisim/', '/tr/teklif-al/'], 'en' => ['/en/contact/', '/en/request-a-quote/']] as $locale => [$contact,$quote]) {
            $html = $this->get($contact)->assertOk()->assertSee('name="details"', false)->assertDontSee('name="deliverables"', false)->assertSee('href="'.$quote.'"', false)->assertDontSee('Private preview')->assertDontSee('Form enquiries are stored in admin')->getContent();
            $this->get($quote)->assertOk()->assertSee('name="deliverables"', false)->assertSee('name="usage"', false)->assertSee('name="project_type"', false)->assertSee('name="budget"', false)->assertSee('name="locale" value="'.$locale.'"', false);
        }
    }

    public function test_simple_contact_message_does_not_require_project_fields(): void
    {
        $this->postJson('/contact-inquiries', ['name' => 'Ada', 'email' => 'ada@example.test', 'phone' => '+90 555 000 00 00', 'details' => 'A collaboration question', 'locale' => 'en', 'consent' => 1])->assertCreated();
        $inquiry = Inquiry::sole();
        $this->assertSame('contact', $inquiry->type);
        $this->assertNull($inquiry->service);
        $this->assertSame('+90 555 000 00 00', $inquiry->phone);
    }

    public function test_quote_request_requires_a_project_brief_and_stores_all_fields(): void
    {
        $base = ['name' => 'Ada', 'email' => 'ada@example.test', 'details' => 'Hotel production', 'locale' => 'tr', 'consent' => 1];
        $this->postJson('/quote-requests', $base)->assertUnprocessable()->assertJsonValidationErrors(['property', 'country', 'city', 'service', 'project_type', 'usage', 'deliverables']);
        $this->postJson('/quote-requests', [...$base, 'property' => 'Sample hotel', 'country' => 'Türkiye', 'city' => 'Antalya', 'service' => 'Photography & film', 'project_type' => 'Hotel / Hospitality', 'usage' => 'Website and social', 'deliverables' => '30 photos and one film', 'budget' => '3000 EUR', 'timing' => 'March'])->assertCreated();
        $inquiry = Inquiry::sole();
        $this->assertSame('quote', $inquiry->type);
        $this->assertSame('3000 EUR', $inquiry->budget);
        $this->assertSame('30 photos and one film', $inquiry->deliverables);
        $this->actingAsAdmin();
        $this->get('/admin/inquiries?type=quote')->assertOk()->assertSee('Sample hotel');
        $this->get('/admin/inquiries?type=contact')->assertOk()->assertDontSee('Sample hotel');
        $this->get('/admin/inquiries/'.$inquiry->id)->assertOk()->assertSee('3000 EUR');
    }

    public function test_consent_and_honeypot_are_enforced_for_both_forms(): void
    {
        foreach (['/contact-inquiries', '/quote-requests'] as $url) {
            $this->postJson($url, ['name' => 'Ada', 'email' => 'ada@example.test', 'details' => 'Hello', 'locale' => 'en'])->assertUnprocessable()->assertJsonValidationErrors('consent');
            $this->postJson($url, ['website' => 'spam'])->assertCreated();
        }
        $this->assertDatabaseCount('inquiries',0);
    }
}
