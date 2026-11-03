<?php

namespace Tests\Feature;

use A17\Twill\Models\User;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DeploymentInitializationTest extends TestCase
{
    use RefreshDatabase;

    public function test_initialization_creates_content_and_admin_and_preserves_existing_edits_and_passwords(): void
    {
        config(['deployment.admin_email' => 'deployment@example.test', 'deployment.admin_password' => 'initial-deployment-test-password', 'analytics.country_database' => __FILE__]);
        $this->artisan('charlot:initialize-deployment')->assertSuccessful();
        $admin = User::where('email', 'deployment@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('initial-deployment-test-password', $admin->password));
        $page = Page::where('import_key', '/en/services/')->firstOrFail();
        $page->translate('en')->update(['heading' => 'Preserve this deployed edit']);
        $admin->forceFill(['password' => Hash::make('changed-deployment-password')])->save();
        $this->artisan('charlot:initialize-deployment')->assertSuccessful();
        $this->assertSame('Preserve this deployed edit', $page->fresh()->translate('en')->heading);
        $this->assertTrue(Hash::check('changed-deployment-password', $admin->fresh()->password));
        $this->assertSame(1, User::where('email', 'deployment@example.test')->count());
    }
}
