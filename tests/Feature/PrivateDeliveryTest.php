<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PrivateDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function work(): Work
    {
        $customer = Customer::create(['title' => 'Ada', 'email' => 'ada@example.test']);

        return Work::create(['title' => 'Shoot', 'customer_id' => $customer->id, 'status' => 'in_progress']);
    }

    public function test_delivery_upload_endpoint_is_removed(): void
    {
        Storage::fake('deliveries');
        $this->actingAsAdmin();
        $work = $this->work();
        $this->post('/admin/works/'.$work->id.'/files', ['files' => [UploadedFile::fake()->create('delivery.pdf', 1)]])->assertNotFound();
        $this->assertDatabaseCount('work_files', 0);
        $this->assertSame([], Storage::disk('deliveries')->allFiles());
    }

    public function test_customer_cannot_download_another_customers_or_another_works_file(): void
    {
        Storage::fake('deliveries');
        $work = $this->work();
        $otherCustomer = Customer::create(['title' => 'Other', 'email' => 'other@example.test']);
        $otherWork = Work::create(['title' => 'Other job', 'customer_id' => $otherCustomer->id, 'delivered_at' => now()]);
        $file = $otherWork->files()->create(['path' => 'other/private.zip', 'name' => 'secret.zip', 'size' => 10, 'released_at' => now()]);
        Storage::disk('deliveries')->put($file->path, 'secret');
        $account = CustomerAccount::create(['customer_id' => $work->customer_id, 'email' => 'ada@example.test', 'password' => 'customer-test-password', 'activated_at' => now()]);
        $this->post('/panel/login', ['email' => $account->email, 'password' => 'customer-test-password'])->assertRedirect();
        foreach ([$work->id, $otherWork->id] as $id) {
            $url = URL::temporarySignedRoute('customer.files.download', now()->addMinutes(15), ['id' => $id, 'file' => $file->id]);
            $this->get($url)->assertNotFound();
        }
    }

    public function test_admin_cannot_mix_file_with_another_work_and_deleting_file_revokes_access(): void
    {
        Storage::fake('deliveries');
        $this->actingAsAdmin();
        $work = $this->work();
        $other = Work::create(['title' => 'Other', 'customer_id' => $work->customer_id]);
        $file = $work->files()->create(['path' => 'private.pdf', 'name' => 'private.pdf', 'size' => 4, 'released_at' => now()]);
        Storage::disk('deliveries')->put($file->path, 'data');
        $this->get(route('admin.works.files.download', [$other, $file]))->assertNotFound();
        $this->delete(route('admin.works.files.destroy', [$other, $file]))->assertNotFound();
        $this->delete(route('admin.works.files.destroy', [$work, $file]))->assertRedirect();
        $this->assertDatabaseCount('work_files', 0);
        Storage::disk('deliveries')->assertMissing('private.pdf');
    }

    public function test_authenticated_owner_can_download_released_file_but_not_an_unsigned_link(): void
    {
        Storage::fake('deliveries');
        $work = $this->work();
        $work->update(['delivered_at' => now()]);
        $file = $work->files()->create(['path' => 'owner/private.zip', 'name' => 'private.zip', 'size' => 4, 'released_at' => now()]);
        Storage::disk('deliveries')->put($file->path, 'data');
        $account = CustomerAccount::create(['customer_id' => $work->customer_id, 'email' => 'ada@example.test', 'password' => 'customer-test-password', 'activated_at' => now()]);
        $this->post('/panel/login', ['email' => $account->email, 'password' => 'customer-test-password'])->assertRedirect();
        $this->get(route('customer.files.download', ['id' => $work->id, 'file' => $file->id]))->assertForbidden();
        $url = URL::temporarySignedRoute('customer.files.download', now()->addMinutes(15), ['id' => $work->id, 'file' => $file->id]);
        $this->get($url)->assertDownload('private.zip')->assertStreamedContent('data')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_shared_download_link_requires_account_login_when_customer_has_an_account(): void
    {
        Storage::fake('deliveries');
        $work = $this->work();
        $work->update(['delivered_at' => now()]);
        $file = $work->files()->create(['path' => 'account/file.pdf', 'name' => 'file.pdf', 'size' => 4, 'released_at' => now()]);
        Storage::disk('deliveries')->put($file->path, 'data');
        CustomerAccount::create(['customer_id' => $work->customer_id, 'email' => 'ada@example.test', 'password' => 'customer-test-password', 'activated_at' => now(), 'enabled' => false]);
        $url = URL::temporarySignedRoute('public.works.files.download', now()->addMinutes(15), ['work' => $work->id, 'file' => $file->id, 'customer' => $work->customer_id]);
        $response = $this->get($url)->assertRedirect();
        $this->get($response->headers->get('Location'))->assertRedirect(route('customer.login'));
    }
}
