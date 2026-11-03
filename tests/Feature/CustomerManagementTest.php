<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customers(): void
    {
        $this->get('/admin/customers')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_and_search_customers(): void
    {
        $this->actingAsAdmin();
        Customer::create(['title' => 'Ada Yılmaz', 'email' => 'ada@example.test']);
        Customer::create(['title' => 'Mert Kaya', 'email' => 'mert@example.test']);

        $this->get('/admin/customers')->assertOk()->assertSee('Ada Yılmaz')->assertSee('Mert Kaya');
        $this->get('/admin/customers?q=Ada')->assertOk()->assertSee('Ada Yılmaz')->assertDontSee('Mert Kaya');
    }

    public function test_admin_can_create_a_customer(): void
    {
        $this->actingAsAdmin();

        $response = $this->post('/admin/customers', [
            'title' => 'Ada Yılmaz', 'company_name' => null, 'email' => 'ada@example.test',
            'phone' => null, 'country' => 'Türkiye', 'notes' => null,
        ]);

        $customer = Customer::firstWhere('email', 'ada@example.test');
        $response->assertRedirect(route('admin.customers.edit', $customer));
        $this->assertDatabaseHas('customers', ['title' => 'Ada Yılmaz', 'email' => 'ada@example.test']);
    }

    public function test_customer_creation_requires_a_title(): void
    {
        $this->actingAsAdmin();

        $this->post('/admin/customers', ['email' => 'ada@example.test'])->assertSessionHasErrors('title');
    }

    public function test_customer_email_must_be_unique(): void
    {
        $this->actingAsAdmin();
        Customer::create(['title' => 'Existing', 'email' => 'taken@example.test']);

        $this->post('/admin/customers', ['title' => 'New', 'email' => 'taken@example.test'])
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_update_a_customer(): void
    {
        $this->actingAsAdmin();
        $customer = Customer::create(['title' => 'Ada Yılmaz']);

        $this->put("/admin/customers/{$customer->id}", ['title' => 'Ada Demir'])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'title' => 'Ada Demir']);
    }

    public function test_edit_page_shows_related_quotes_and_works(): void
    {
        $this->actingAsAdmin();
        $customer = Customer::create(['title' => 'Ada Yılmaz']);
        Quote::create(['title' => 'Düğün paketi', 'customer_id' => $customer->id]);

        $this->get("/admin/customers/{$customer->id}/edit")->assertOk()->assertSee('Düğün paketi');
    }

    public function test_archiving_a_customer_soft_deletes_it(): void
    {
        $this->actingAsAdmin();
        $customer = Customer::create(['title' => 'Ada Yılmaz']);

        $this->delete("/admin/customers/{$customer->id}")->assertRedirect(route('admin.customers.index'));

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }
}
