<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_every_protected_admin_route(): void
    {
        foreach (['/admin', '/admin/customers', '/admin/quotes', '/admin/works', '/admin/inquiries', '/admin/revision-requests', '/admin/site-settings'] as $path) {
            $this->get($path)->assertRedirect(route('admin.login'));
        }
    }

    public function test_login_page_is_visible_to_guests(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_valid_credentials_log_the_admin_in_and_redirect_to_dashboard(): void
    {
        Admin::forceCreate([
            'name' => 'Editor', 'email' => 'editor@example.test', 'password' => Hash::make('correct-password'),
            'role' => 'SUPERADMIN', 'published' => true, 'registered_at' => now(),
        ]);

        $this->post('/admin/login', ['email' => 'editor@example.test', 'password' => 'correct-password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'editor@example.test']);
        $this->get('/admin')->assertOk();
    }

    public function test_invalid_credentials_are_rejected_with_a_generic_error(): void
    {
        Admin::forceCreate([
            'name' => 'Editor', 'email' => 'editor@example.test', 'password' => Hash::make('correct-password'),
            'role' => 'SUPERADMIN', 'published' => true, 'registered_at' => now(),
        ]);

        $this->post('/admin/login', ['email' => 'editor@example.test', 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_login_attempts_are_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $this->post('/admin/login', ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_admin_can_log_out(): void
    {
        $this->actingAsAdmin();
        $this->get('/admin')->assertOk();

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }
}
