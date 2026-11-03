<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DriveDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function work(): Work
    {
        $customer = Customer::create(['title' => 'Drive customer', 'email' => 'drive@example.test']);

        return Work::create(['title' => 'Drive shoot', 'customer_id' => $customer->id, 'status' => 'in_progress']);
    }

    public function test_drive_links_are_hidden_until_delivery_and_edits_require_redelivery(): void
    {
        Mail::fake();
        $this->actingAsAdmin();
        $work = $this->work();
        $this->get(route('admin.works.edit', $work))->assertOk()->assertSee('Google Drive teslimat bağlantıları')->assertDontSee('Dosyaları yükle')->assertDontSee('name="files[]"', false);
        $url = 'https://drive.google.com/drive/folders/final-photos';
        $this->post(route('admin.works.delivery-links.store', $work), ['title' => 'Final photos', 'url' => $url])->assertRedirect();
        $link = $work->deliveryLinks()->sole();
        $this->assertNull($link->released_at);
        $this->get($work->signedShowUrl())->assertOk()->assertDontSee($url);
        $this->post(route('admin.works.deliver', $work), ['delivery_note' => 'Drive delivery'])->assertRedirect();
        $this->assertNotNull($link->fresh()->released_at);
        $this->get($work->signedShowUrl())->assertOk()->assertSee($url)->assertSee('Final photos');
        $this->put(route('admin.works.delivery-links.update', [$work, $link]), ['title' => 'New version', 'url' => $url.'-v2'])->assertRedirect();
        $this->assertNull($link->fresh()->released_at);
        $this->get($work->signedShowUrl())->assertOk()->assertDontSee($url.'-v2');
        $work->update(['status' => 'completed']);
        $this->post(route('admin.works.deliver', $work), ['delivery_note' => 'Updated delivery'])->assertRedirect();
        $this->assertSame('in_progress', $work->fresh()->status);
        $this->assertNotNull($link->fresh()->released_at);
        $this->assertDatabaseCount('work_files', 0);
    }

    public function test_only_google_drive_https_urls_are_accepted(): void
    {
        $this->actingAsAdmin();
        $work = $this->work();
        foreach (['http://drive.google.com/file/d/a', 'https://drive.google.com.evil.test/a', 'https://example.com/a', 'javascript:alert(1)', 'https://user@drive.google.com/a', 'https://drive.google.com:8443/a'] as $url) {
            $this->postJson(route('admin.works.delivery-links.store', $work), ['title' => 'Invalid', 'url' => $url])->assertUnprocessable()->assertJsonValidationErrors('url');
        }
        $this->assertDatabaseCount('work_delivery_links', 0);
        $this->post(route('admin.works.delivery-links.store', $work), ['title' => 'Document', 'url' => 'https://docs.google.com/document/d/example/edit'])->assertRedirect();
    }

    public function test_link_editing_is_scoped_to_its_work_and_delete_removes_only_the_link(): void
    {
        $this->actingAsAdmin();
        $work = $this->work();
        $other = Work::create(['title' => 'Other', 'customer_id' => $work->customer_id]);
        $link = $work->deliveryLinks()->create(['title' => 'Final', 'url' => 'https://drive.google.com/file/d/test/view']);
        $this->put(route('admin.works.delivery-links.update', [$other, $link]), ['title' => 'Changed', 'url' => $link->url])->assertNotFound();
        $this->delete(route('admin.works.delivery-links.destroy', [$other, $link]))->assertNotFound();
        $this->delete(route('admin.works.delivery-links.destroy', [$work, $link]))->assertRedirect();
        $this->assertDatabaseCount('work_delivery_links', 0);
    }

    public function test_account_delivery_links_are_only_visible_in_the_owners_portal(): void
    {
        $work = $this->work();
        $work->update(['delivered_at' => now()]);
        $link = $work->deliveryLinks()->create(['title' => 'Account delivery', 'url' => 'https://drive.google.com/drive/folders/account-final', 'released_at' => now()]);
        $draft = $work->deliveryLinks()->create(['title' => 'Unreleased', 'url' => 'https://drive.google.com/drive/folders/draft']);
        $account = CustomerAccount::create(['customer_id' => $work->customer_id, 'email' => 'drive@example.test', 'password' => 'customer-test-password', 'activated_at' => now()]);
        $this->get($work->signedShowUrl())->assertOk()->assertDontSee($link->url)->assertSee('Müşteri panelini aç');
        $this->post('/panel/login', ['email' => $account->email, 'password' => 'customer-test-password'])->assertRedirect();
        $this->get(route('customer.works.show', $work->id))->assertOk()->assertSee($link->url)->assertDontSee($draft->url);
        $other = $this->work();
        $this->get(route('customer.works.show', $other->id))->assertNotFound();
        $work->update(['status' => 'cancelled']);
        $this->get(route('customer.works.show', $work->id))->assertOk()->assertDontSee($link->url);
    }
}
