<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use App\Models\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReferenceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_logos_are_imported_once_and_editor_changes_are_preserved(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->assertDatabaseCount('references', 14);
        $reference = Reference::first();
        $this->assertCount(1, $reference->medias);
        $reference->translate('tr')->title = 'Düzenlenmiş firma';
        $reference->save();
        $this->artisan('charlot:import-references')->assertSuccessful();
        $this->assertDatabaseCount('references', 14);
        $this->assertSame('Düzenlenmiş firma', $reference->fresh()->translate('tr')->title);
    }

    public function test_reference_updates_order_and_publication_are_live_in_both_languages(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $reference = Reference::first();
        $this->get('/tr/referanslar/')->assertOk()->assertSee('Accor')->assertSee('reference-grid full');
        $reference->translate('tr')->title = 'Yeni firma adı';
        $reference->position = 999;
        $reference->save();
        $this->get('/tr/referanslar/')->assertOk()->assertSee('Yeni firma adı')->assertDontSee('>Accor<', false);
        $this->get('/en/references/')->assertOk()->assertSee('Accor');
        $reference->published = false;
        $reference->save();
        $this->get('/tr/referanslar/')->assertDontSee('Yeni firma adı');
        $this->get('/en/references/')->assertDontSee('>Accor<', false);
    }

    public function test_editor_can_manage_references_and_unsafe_web_addresses_are_rejected(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->get('/admin/references')->assertRedirect();
        $this->actingAs(Admin::forceCreate(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => Hash::make('test-only'), 'registered_at' => now(), 'role' => 'SUPERADMIN', 'published' => true]), 'twill_users');
        $reference = Reference::first();
        $this->get('/admin/references')->assertOk();
        $this->get('/admin/references/'.$reference->id.'/edit')->assertOk()->assertSee('Logo');
        $fields = ['title' => ['en' => 'Accor', 'tr' => 'Accor'], 'published' => true, 'website_url' => 'https://example.test/'];
        $this->put('/admin/references/'.$reference->id, $fields)->assertRedirect();
        $this->get('/tr/referanslar/')->assertSee('href="https://example.test/"', false)->assertSee('rel="noopener noreferrer"', false);
        $fields['website_url'] = 'javascript:alert(1)';
        $this->put('/admin/references/'.$reference->id, $fields)->assertSessionHasErrors('website_url');
    }
}
