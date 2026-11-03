<?php

namespace Tests\Feature;

use App\Mail\CustomerPasswordMail;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CustomerPortalTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $email = 'ada@example.test'): CustomerAccount
    {
        $customer = Customer::create(['title' => 'Ada', 'email' => $email, 'notes' => 'PRIVATE CUSTOMER NOTE']);

        return CustomerAccount::create(['customer_id' => $customer->id, 'email' => $email, 'password' => 'customer-test-password', 'activated_at' => now(), 'enabled' => true]);
    }

    private function signIn(CustomerAccount $account): void
    {
        $this->post('/panel/login', ['email' => $account->email, 'password' => 'customer-test-password'])->assertRedirect(route('customer.dashboard'));
    }

    public function test_customer_login_uses_a_separate_guard_and_portal_is_private(): void
    {
        $this->get('/panel')->assertRedirect(route('customer.login'));
        $account = $this->account();
        $this->signIn($account);
        $this->assertAuthenticatedAs($account, 'customers');
        $this->get('/panel?lang=en')->assertOk()->assertSee('Client portal')->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertDontSee('PRIVATE CUSTOMER NOTE');
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->post('/panel/logout')->assertRedirect(route('customer.login'));
        $this->assertGuest('customers');
    }

    public function test_customer_only_sees_own_sent_quotes_and_published_works(): void
    {
        $account = $this->account();
        $other = $this->account('other@example.test');
        $own = Quote::create(['title' => 'Own quote', 'customer_id' => $account->customer_id, 'status' => 'sent', 'internal_notes' => 'PRIVATE QUOTE NOTE']);
        $draft = Quote::create(['title' => 'Unsent draft', 'customer_id' => $account->customer_id]);
        $foreign = Quote::create(['title' => 'Other quote', 'customer_id' => $other->customer_id, 'status' => 'sent']);
        $work = Work::create(['title' => 'Own work', 'customer_id' => $account->customer_id, 'internal_notes' => 'PRIVATE WORK NOTE']);
        $foreignWork = Work::create(['title' => 'Other work', 'customer_id' => $other->customer_id]);
        $this->signIn($account);
        $this->get('/panel')->assertOk()->assertSee('Own quote')->assertSee('Own work')->assertDontSee('Other quote')->assertDontSee('Unsent draft')->assertDontSee('PRIVATE');
        $this->get('/panel/quotes/'.$own->id)->assertOk()->assertDontSee('PRIVATE QUOTE NOTE');
        $this->get('/panel/works/'.$work->id)->assertOk()->assertDontSee('PRIVATE WORK NOTE');
        foreach ([$foreign, $draft] as $quote) {
            $this->get('/panel/quotes/'.$quote->id)->assertNotFound();
            $this->post('/panel/quotes/'.$quote->id.'/accept')->assertNotFound();
        }
        $this->get('/panel/works/'.$foreignWork->id)->assertNotFound();
        $this->post('/panel/works/'.$foreignWork->id.'/approve')->assertNotFound();
        $this->post('/panel/works/'.$foreignWork->id.'/revision', ['customer_note' => 'test'])->assertNotFound();
    }

    public function test_accepting_own_quote_creates_one_job_and_records_customer_actor(): void
    {
        Mail::fake();
        $account = $this->account();
        $quote = Quote::create(['title' => 'Quote', 'customer_id' => $account->customer_id, 'status' => 'sent']);
        $this->signIn($account);
        $this->post('/panel/quotes/'.$quote->id.'/accept')->assertRedirect();
        $this->post('/panel/quotes/'.$quote->id.'/accept')->assertRedirect();
        $this->assertSame('accepted', $quote->refresh()->status);
        $this->assertSame(1, $quote->works()->count());
        $event = $quote->statusEvents()->first();
        $this->assertNull($event->changed_by);
        $this->assertStringContainsString('müşteri paneli', $event->note);
    }

    public function test_admin_can_create_single_use_invitation_without_sending_mail(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $customer = Customer::create(['title' => 'New customer', 'email' => 'new@example.test']);
        $response = $this->post(route('admin.customers.access.invite', $customer))->assertRedirect()->assertSessionHas('customer_invite_url');
        $url = session('customer_invite_url');
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        $token = basename(parse_url($url, PHP_URL_PATH));
        Auth::guard('twill_users')->logout();
        $this->get($url)->assertOk();
        $data = ['token' => $token, 'email' => $query['email'], 'password' => 'new-customer-password', 'password_confirmation' => 'new-customer-password'];
        $this->post('/panel/reset-password', $data)->assertRedirect(route('customer.login'));
        $this->post('/panel/reset-password', $data)->assertSessionHasErrors('email');
        $this->post('/panel/login', ['email' => $customer->email, 'password' => 'new-customer-password'])->assertRedirect(route('customer.dashboard'));
        $this->get('/panel')->assertOk();
        $this->assertDatabaseCount('customer_accounts', 1);
        Mail::assertNothingQueued();
    }

    public function test_password_reset_expires_and_does_not_reveal_unknown_emails(): void
    {
        Mail::fake();
        $account = $this->account();
        $this->post('/panel/forgot-password', ['email' => $account->email])->assertRedirect()->assertSessionHas('status');
        $known = session('status');
        Mail::assertQueued(CustomerPasswordMail::class);
        $this->post('/panel/forgot-password', ['email' => 'unknown@example.test'])->assertRedirect()->assertSessionHas('status', $known);
        $token = Password::broker('customers')->createToken($account);
        $this->travel(61)->minutes();
        $this->post('/panel/reset-password', ['token' => $token, 'email' => $account->email, 'password' => 'new-customer-password', 'password_confirmation' => 'new-customer-password'])->assertSessionHasErrors('email');
    }

    public function test_disabled_archived_or_password_changed_accounts_lose_access(): void
    {
        $account = $this->account();
        $this->signIn($account);
        $account->update(['enabled' => false]);
        $this->get('/panel')->assertRedirect(route('customer.login'));
        $account->update(['enabled' => true]);
        $this->signIn($account);
        $account->update(['password' => 'another-new-password']);
        $this->get('/panel')->assertRedirect(route('customer.login'));
        $this->post('/panel/login', ['email' => $account->email, 'password' => 'another-new-password'])->assertRedirect(route('customer.dashboard'));
        $account->customer->delete();
        $this->get('/panel')->assertRedirect(route('customer.login'));
    }

    public function test_changing_crm_email_disables_existing_access_until_reinvited(): void
    {
        $account = $this->account();
        $account->customer->update(['email' => 'changed@example.test']);
        $this->assertFalse($account->fresh()->enabled);
    }
}
