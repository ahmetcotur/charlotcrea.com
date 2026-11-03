<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\Inquiry;
use App\Models\Quote;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowDemosTest extends TestCase
{
    use RefreshDatabase;

    public function test_demos_connect_forms_portal_quotes_jobs_drive_and_revisions_without_emails_or_overwrites(): void
    {
        Storage::fake('local');
        Mail::fake();
        $this->artisan('charlot:demo-workflows')->assertSuccessful();
        $this->assertDatabaseCount('customers', 5);
        $this->assertDatabaseCount('customer_accounts', 5);
        $this->assertDatabaseCount('quotes', 5);
        $this->assertDatabaseCount('works', 4);
        $this->assertDatabaseCount('inquiries', 10);
        $this->assertDatabaseCount('work_files', 0);
        $this->assertDatabaseCount('work_delivery_links', 5);
        $this->assertDatabaseCount('revision_requests', 3);
        foreach (Quote::where('status', 'accepted')->get() as $quote) {
            $this->assertSame(1, $quote->works()->count());
            $this->assertSame(5000.0, (float) $quote->amount);
        }
        $pending = Quote::where('demo_key', 'workflow-v2-pending')->firstOrFail();
        $this->assertSame(0, $pending->works()->count());
        $pending->update(['title' => 'Preserve demo edits']);
        $this->artisan('charlot:demo-workflows')->assertSuccessful();
        $this->assertDatabaseCount('quotes', 5);
        $this->assertDatabaseCount('works', 4);
        $this->assertSame('Preserve demo edits', $pending->fresh()->title);
        $manifest = json_decode(Storage::disk('local')->get('demo-workflows.json'), true);
        $demo = $manifest['revision'];
        $this->post('/panel/login', ['email' => $demo['email'], 'password' => $demo['password']])->assertRedirect();
        $this->get(route('customer.works.show', $demo['work_id']))->assertOk()->assertSee('DEMO-revision-placeholder')->assertDontSee('DEMO-revision-draft-placeholder');
        $this->get('/admin/demo-workflows')->assertRedirect(route('admin.login'));
        $this->actingAsAdmin();
        $this->get('/admin/demo-workflows')->assertOk()->assertSee('Demo iş akışları');
        Mail::assertNothingQueued();
        $this->assertSame(5, CustomerAccount::where('enabled', true)->count());
        $this->assertSame(5, Inquiry::where('type', 'contact')->count());
        $this->assertSame(5, Customer::where('email', 'like', '%@demo.example.test')->count());
        $this->assertSame(4, Work::whereNotNull('quote_id')->count());
    }
}
