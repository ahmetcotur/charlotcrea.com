<?php

namespace Tests;

use A17\Twill\Models\User as Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(config('twill.media_library.disk'));
    }

    /**
     * Mirrors AuthController@store: a real admin login also creates the
     * matching App\Models\User row that observers resolve `changed_by` from.
     */
    protected function actingAsAdmin(string $email = 'editor@example.test', string $role = 'SUPERADMIN'): Admin
    {
        $admin = Admin::forceCreate([
            'name' => 'Editor',
            'email' => $email,
            'password' => Hash::make('test-only'),
            'role' => $role,
            'published' => true,
            'registered_at' => now(),
        ]);

        User::factory()->create(['email' => $email]);

        $this->actingAs($admin, 'twill_users');

        return $admin;
    }
}
