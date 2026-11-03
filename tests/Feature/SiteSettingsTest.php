<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->actingAs(Admin::forceCreate(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => Hash::make('test-only'), 'role' => 'SUPERADMIN', 'published' => true, 'registered_at' => now()]), 'twill_users');
    }

    private function values(): array
    {
        return ['name' => 'Charlot Crea', 'email' => 'studio@example.test', 'telephone' => '+90 212 555 12 34', 'whatsapp_number' => '+90 555 123 45 67', 'address' => 'Yeni adres, İstanbul, Türkiye', 'street_address' => 'Yeni adres', 'district' => 'Beyoğlu', 'city' => 'İstanbul', 'country' => 'tr', 'country_name' => 'Türkiye', 'map_query' => '41.03,28.97', 'map_url' => 'https://maps.app.goo.gl/example', 'instagram_url' => 'https://www.instagram.com/example/', 'vimeo_url' => null, 'linkedin_url' => null];
    }

    public function test_settings_screen_is_accessible_to_admin_and_contains_grouped_fields(): void
    {
        $this->get('/admin/site-settings')->assertRedirect();
        $this->editor();
        $this->get('/admin/site-settings')->assertOk()->assertSee('Site ayarları')->assertSee('Adres ve harita')->assertSee('Sosyal medya')->assertSee('info@charlotcrea.com');
    }

    public function test_contact_and_footer_and_schema_change_together_in_both_languages(): void
    {
        $this->editor();
        $this->get('/tr/iletisim/')->assertOk()->assertSee('info@charlotcrea.com');
        $this->get('/tr/')->assertOk();
        $this->put('/admin/site-settings', $this->values())->assertRedirect();
        foreach (['/tr/iletisim/', '/en/contact/', '/tr/referanslar/', '/tr/', '/en/home-2/'] as $path) {
            $html = $this->get($path)->assertOk()->assertSee('studio@example.test')->assertSee('+90 212 555 12 34')->assertSee('Yeni adres')->assertSee('https://www.instagram.com/example/')->getContent();
            $this->assertStringNotContainsString('info@charlotcrea.com', $html);
            $this->assertStringContainsString('studio@example.test', $html);
        }
        $this->get('/en/contact/')->assertSee('https://wa.me/905551234567', false)->assertSee('https://maps.app.goo.gl/example', false)->assertSee('q=41.03%2C28.97', false)->assertSee('id="brief"', false);
        $this->assertDatabaseHas('site_settings', ['id' => 1, 'country' => 'TR', 'email' => 'studio@example.test']);
    }

    public function test_optional_links_can_be_removed_without_stale_cache(): void
    {
        $this->editor();
        $this->put('/admin/site-settings', $this->values())->assertRedirect();
        $this->get('/en/contact/')->assertSee('https://wa.me/905551234567', false);
        foreach (app('router')->getRoutes() as $route) {
            $route->flushController();
        }
        $values = $this->values();
        $values['whatsapp_number'] = null;
        $values['instagram_url'] = null;
        $values['map_url'] = null;
        $values['map_query'] = null;
        $this->put('/admin/site-settings', $values)->assertRedirect();
        $this->get('/en/contact/')->assertDontSee('wa.me')->assertDontSee('instagram.com/example')->assertDontSee('maps.app.goo.gl/example');
    }

    public function test_invalid_contact_data_is_rejected_and_viewer_cannot_save(): void
    {
        $this->editor();
        $invalid = $this->values();
        $invalid['email'] = 'not-an-email';
        $invalid['instagram_url'] = 'javascript:alert(1)';
        $this->putJson('/admin/site-settings', $invalid)->assertUnprocessable();
        $this->assertDatabaseHas('site_settings', ['email' => 'info@charlotcrea.com']);
    }

    public function test_reimport_does_not_replace_saved_site_settings(): void
    {
        $this->editor();
        SiteSetting::findOrFail(1)->update(['email' => 'saved@example.test']);
        $this->artisan('charlot:seed-settings')->assertSuccessful();
        $this->assertDatabaseHas('site_settings', ['email' => 'saved@example.test']);
    }
    public function test_homepage_variant_switches_both_languages_and_clears_cached_homepage(): void
    {
        $this->editor();
        foreach (['/en/' => 'Classic hero', '/en/home-2/' => 'Photo strip hero', '/en/home-3/' => 'Video hero'] as $key => $heading) {
            $page = \App\Models\Page::where('import_key', $key)->firstOrFail();
            foreach ($page->translations as $translation) {
                $translation->update(['heading' => $heading]);
            }
        }
        $this->get('/tr/')->assertOk()->assertSee('Classic hero');
        foreach (['home-2' => 'Photo strip hero', 'home-3' => 'Video hero', 'home-1' => 'Classic hero'] as $variant => $heading) {
            $this->put('/admin/site-settings', [...$this->values(), 'homepage_variant' => $variant])->assertRedirect();
            $this->assertDatabaseHas('site_settings', ['homepage_variant' => $variant]);
            if ($variant !== 'home-1') {
                $alias = $this->get('/en/'.$variant.'/')->assertOk()->getContent();
                $this->assertStringContainsString('rel="canonical" href="'.rtrim(config('app.url'), '/').'/en/"', $alias);
                $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>'.rtrim(config('app.url'), '/').'/en/'.$variant.'/</loc>', false);
            }
            foreach (['tr', 'en'] as $locale) {
                $html = $this->get('/'.$locale.'/')->assertOk()->assertSee($heading)->getContent();
                $this->assertStringContainsString('rel="canonical" href="'.rtrim(config('app.url'), '/').'/'.$locale.'/', $html);
                $this->assertStringContainsString('hreflang="en"', $html);
            }
        }
        $this->get('/en/home-2/')->assertOk()->assertSee('Photo strip hero');
        $this->get('/en/home-3/')->assertOk()->assertSee('Video hero');
    }

    public function test_invalid_homepage_variant_is_rejected(): void
    {
        $this->editor();
        $this->putJson('/admin/site-settings', [...$this->values(), 'homepage_variant' => 'other'])->assertUnprocessable()->assertJsonValidationErrors('homepage_variant');
    }

    public function test_unpublished_homepage_variant_falls_back_to_original_homepage(): void
    {
        $this->editor();
        \App\Models\Page::where('import_key', '/en/home-3/')->firstOrFail()->update(['published' => false]);
        $this->put('/admin/site-settings', [...$this->values(), 'homepage_variant' => 'home-3'])->assertRedirect();
        $this->get('/tr/')->assertOk();
        $this->get('/en/')->assertOk();
    }
}
