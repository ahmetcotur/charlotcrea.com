<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_customer_shells_show_the_shared_brand_and_only_owners_can_download_pdf(): void
    {
        $customer = Customer::create(['title' => 'Brand customer', 'email' => 'brand@example.test']);
        $account = CustomerAccount::create(['customer_id' => $customer->id, 'email' => $customer->email, 'password' => 'brand-test-password', 'activated_at' => now()]);
        $quote = Quote::create(['title' => 'Branded quote', 'customer_id' => $customer->id, 'published' => true, 'status' => 'sent']);
        $other = Customer::create(['title' => 'Other', 'email' => 'other@example.test']);
        $otherQuote = Quote::create(['title' => 'Other quote', 'customer_id' => $other->id, 'published' => true, 'status' => 'sent']);
        $draft = Quote::create(['title' => 'Draft quote', 'customer_id' => $customer->id, 'published' => true, 'status' => 'draft']);
        $work = Work::create(['title' => 'Branded delivery', 'customer_id' => $customer->id, 'published' => true, 'delivered_at' => now()]);
        foreach (['/panel/login', '/panel/forgot-password', '/panel/reset-password/test-token?email=brand@example.test', $work->signedShowUrl()] as $url) {
            $this->get($url)->assertOk()->assertSee('portal-logo')->assertSee('assets/logo.png')->assertSee('info@charlotcrea.com');
        }
        $this->get(route('customer.quotes.pdf', $quote->id))->assertRedirect(route('customer.login'));
        $this->post('/panel/login', ['email' => $account->email, 'password' => 'brand-test-password'])->assertRedirect();
        foreach ([route('customer.dashboard'), route('customer.quotes.show', $quote->id), route('customer.works.show', $work->id)] as $url) {
            $this->get($url)->assertOk()->assertSee('portal-logo');
        }
        $this->get(route('customer.quotes.pdf', $quote->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('customer.quotes.pdf', $otherQuote->id))->assertNotFound();
        $this->get(route('customer.quotes.pdf', $draft->id))->assertNotFound();
    }
}
