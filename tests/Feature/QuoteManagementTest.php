<?php

namespace Tests\Feature;

use App\Mail\QuoteSentMail;
use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuoteManagementTest extends TestCase
{
    use RefreshDatabase;

    private function customer(array $overrides = []): Customer
    {
        return Customer::create(array_merge(['title' => 'Ada Yılmaz', 'email' => 'ada@example.test'], $overrides));
    }

    private function itemFields(float $quantity = 2, float $unitPrice = 500): array
    {
        return ['items' => [['title' => 'Çekim günü', 'quantity' => $quantity, 'unit_price' => $unitPrice]]];
    }

    public function test_guest_cannot_access_quotes(): void
    {
        $this->get('/admin/quotes')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_and_search_quotes(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id]);
        Quote::create(['title' => 'Kurumsal çekim', 'customer_id' => $customer->id]);

        $this->get('/admin/quotes')->assertOk()->assertSee('Düğün paketi')->assertSee('Kurumsal çekim');
        $this->get('/admin/quotes?'.http_build_query(['q' => 'Düğün']))->assertOk()->assertSee('Düğün paketi')->assertDontSee('Kurumsal çekim');
    }

    public function test_creating_a_quote_without_a_number_generates_one_automatically(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post('/admin/quotes', [
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft',
            'currency' => 'EUR', ...$this->itemFields(),
        ])->assertRedirect();

        $quote = Quote::firstWhere('title', 'Düğün paketi');
        $this->assertNotNull($quote->quote_number);
        $this->assertStringStartsWith('CC-', $quote->quote_number);
        $this->assertSame('1000.00', $quote->amount);
    }

    public function test_creating_a_quote_records_an_initial_status_event(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post('/admin/quotes', [
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft',
            'currency' => 'EUR', ...$this->itemFields(),
        ]);

        $quote = Quote::firstWhere('title', 'Düğün paketi');
        $this->assertDatabaseHas('quote_status_events', ['quote_id' => $quote->id, 'from_status' => null, 'to_status' => 'draft']);
    }

    public function test_updating_a_quote_status_records_a_status_event_with_the_acting_admin(): void
    {
        $admin = $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'amount' => 1000]);

        $this->put("/admin/quotes/{$quote->id}", [
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'sent',
            'currency' => 'EUR', ...$this->itemFields(),
        ])->assertRedirect();

        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertNotNull($quote->sent_at);
        $event = $quote->statusEvents()->latest()->first();
        $this->assertSame('draft', $event->from_status);
        $this->assertSame('sent', $event->to_status);
        $this->assertDatabaseHas('users', ['email' => $admin->email]);
    }

    public function test_quote_number_must_be_unique(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        Quote::create(['title' => 'Existing', 'customer_id' => $customer->id, 'quote_number' => 'CC-2026-000001']);

        $this->post('/admin/quotes', [
            'title' => 'New', 'customer_id' => $customer->id, 'quote_number' => 'CC-2026-000001',
            'status' => 'draft', 'currency' => 'EUR', ...$this->itemFields(),
        ])->assertSessionHasErrors('quote_number');
    }

    public function test_creating_a_quote_requires_at_least_one_item(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post('/admin/quotes', [
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'currency' => 'EUR', 'items' => [],
        ])->assertSessionHasErrors('items');
    }

    public function test_quote_item_fields_are_required(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post('/admin/quotes', [
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'currency' => 'EUR',
            'items' => [['title' => '', 'quantity' => '', 'unit_price' => '']],
        ])->assertSessionHasErrors(['items.0.title', 'items.0.quantity', 'items.0.unit_price']);
    }

    public function test_total_is_computed_from_multiple_line_items(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();

        $this->post('/admin/quotes', [
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'currency' => 'EUR',
            'items' => [
                ['title' => 'Fotoğraf çekimi', 'quantity' => 2, 'unit_price' => 1500],
                ['title' => 'Kısa film', 'quantity' => 1, 'unit_price' => 1200],
            ],
        ])->assertRedirect();

        $quote = Quote::firstWhere('title', 'Düğün paketi');
        $this->assertSame('4200.00', $quote->amount);
        $this->assertCount(2, $quote->items);
    }

    public function test_updating_items_replaces_the_previous_set_and_recomputes_the_total(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft']);
        $quote->syncItems([['title' => 'Eski kalem', 'quantity' => 1, 'unit_price' => 999]]);

        $this->put("/admin/quotes/{$quote->id}", [
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'currency' => 'EUR',
            'items' => [['title' => 'Yeni kalem', 'quantity' => 3, 'unit_price' => 100]],
        ])->assertRedirect();

        $quote->refresh();
        $this->assertSame('300.00', $quote->amount);
        $this->assertCount(1, $quote->items);
        $this->assertSame('Yeni kalem', $quote->items->first()->title);
    }

    public function test_archiving_a_quote_soft_deletes_it(): void
    {
        $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id]);

        $this->delete("/admin/quotes/{$quote->id}")->assertRedirect(route('admin.quotes.index'));

        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
    }

    public function test_sending_a_quote_without_customer_email_still_marks_it_sent_but_skips_mail(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = $this->customer(['email' => null]);
        $quote = Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'amount' => 1000]);

        $this->post("/admin/quotes/{$quote->id}/send")->assertSessionHas('success');

        Mail::assertNothingQueued();
        $this->assertSame('sent', $quote->refresh()->status);
    }

    public function test_sending_an_expired_quote_is_blocked(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create([
            'title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft',
            'amount' => 1000, 'valid_until' => now()->subDay(),
        ]);

        $this->post("/admin/quotes/{$quote->id}/send")->assertSessionHas('error');

        Mail::assertNothingQueued();
    }

    public function test_sending_a_quote_marks_it_sent_and_queues_mail(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'amount' => 1000]);

        $this->post("/admin/quotes/{$quote->id}/send")->assertRedirect();

        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertNotNull($quote->sent_at);
        Mail::assertQueued(QuoteSentMail::class);
    }

    public function test_copying_a_quote_link_has_no_side_effects(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = $this->customer();
        $quote = Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id, 'status' => 'draft', 'amount' => 1000]);

        $response = $this->get("/admin/quotes/{$quote->id}/link")->assertOk();
        $response->assertJsonStructure(['url']);

        Mail::assertNothingQueued();
        $this->assertSame('draft', $quote->refresh()->status);
    }
}
