<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use App\Models\Customer;
use App\Models\Quote;
use App\Models\Work;
use App\Services\AcceptedQuoteWork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AcceptedQuoteWorkTest extends TestCase
{
    use RefreshDatabase;

    private function quote(string $status = 'sent'): Quote
    {
        $customer = Customer::create(['title' => 'Ada', 'email' => 'ada@example.test']);

        return Quote::create(['title' => 'Fotoğraf çekimi', 'customer_id' => $customer->id, 'scope' => 'Çekim ve 50 fotoğraf', 'status' => $status]);
    }

    public function test_customer_acceptance_creates_one_planned_work_with_scope_and_customer(): void
    {
        Mail::fake();
        $quote = $this->quote();
        $url = URL::temporarySignedRoute('public.quotes.accept', now()->addMinutes(30), ['quote' => $quote->id]);
        $this->post($url)->assertOk();
        $work = Work::sole();
        $this->assertSame($quote->id, $work->quote_id);
        $this->assertSame($quote->customer_id, $work->customer_id);
        $this->assertSame($quote->title, $work->title);
        $this->assertSame($quote->scope, $work->brief);
        $this->assertSame('planned', $work->status);
        $this->post($url)->assertOk();
        $this->assertDatabaseCount('works', 1);
    }

    public function test_admin_status_change_and_reacceptance_do_not_overwrite_existing_work(): void
    {
        $admin = Admin::forceCreate(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => bcrypt('test-only'), 'role' => 'SUPERADMIN', 'published' => true, 'registered_at' => now()]);
        $this->actingAs($admin, 'twill_users');
        $quote = $this->quote();
        $this->patchJson(route('admin.quotes.update-status', $quote), ['status' => 'accepted'])->assertOk();
        $work = Work::sole();
        $work->update(['status' => 'in_progress', 'brief' => 'Ekip tarafından düzenlendi']);
        $quote->refresh()->update(['status' => 'sent']);
        $quote->update(['status' => 'accepted']);
        $this->assertDatabaseCount('works', 1);
        $this->assertSame('in_progress', $work->refresh()->status);
        $this->assertSame('Ekip tarafından düzenlendi', $work->brief);
        $this->get(route('admin.quotes.edit', $quote))->assertOk()->assertSee(route('admin.works.edit', $work), false);
    }

    public function test_accepted_creation_and_archived_work_do_not_generate_duplicates(): void
    {
        $quote = $this->quote('accepted');
        $work = Work::sole();
        $work->delete();
        app(AcceptedQuoteWork::class)->ensure($quote);
        $this->assertSame(1, Work::withTrashed()->count());
        $this->assertSame(0, Work::count());
    }

    public function test_existing_manually_linked_work_is_preserved(): void
    {
        $quote = $this->quote();
        $work = Work::create(['title' => 'Özel iş', 'customer_id' => $quote->customer_id, 'quote_id' => $quote->id]);
        $quote->update(['status' => 'accepted']);
        $this->assertSame($work->id, Work::sole()->id);
    }

    public function test_overdue_quote_cannot_be_accepted_through_a_still_valid_action_link(): void
    {
        Mail::fake();
        $quote = $this->quote();
        $quote->update(['valid_until' => now()->subDay()]);
        $url = URL::temporarySignedRoute('public.quotes.accept', now()->addMinutes(30), ['quote' => $quote->id]);
        $this->post($url)->assertOk();
        $this->assertSame('expired', $quote->refresh()->status);
        $this->assertDatabaseCount('works', 0);
        Mail::assertNothingQueued();
    }

    public function test_quote_remains_valid_until_the_end_of_its_validity_date(): void
    {
        Mail::fake();
        $this->travelTo(now()->startOfDay()->addHours(16));
        $quote = $this->quote();
        $quote->update(['valid_until' => today()]);
        $url = URL::temporarySignedRoute('public.quotes.accept', now()->addMinutes(30), ['quote' => $quote->id]);
        $this->post($url)->assertOk();
        $this->assertSame('accepted', $quote->refresh()->status);
        $this->assertDatabaseCount('works', 1);
    }
}
