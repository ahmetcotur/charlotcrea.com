<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Services\HomeSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NativePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_five_native_pages_render_without_legacy_layout_html_and_keep_cms_copy(): void
    {
        Storage::fake('local');
        $this->artisan('charlot:import')->assertSuccessful();
        $pages = Page::with('translations')->whereIn('import_key', [...HomeSections::HOMES, '/en/about/', '/en/services/'])->get();
        $this->assertCount(5, $pages);
        foreach ($pages as $page) {
            foreach ($page->translations as $translation) {
                $translation->forceFill(['legacy_html' => '<script>LEGACY_LAYOUT_POISON</script>'])->save();
            }
        }
        foreach ($pages as $page) {
            foreach (['tr', 'en'] as $locale) {
                $this->get($page->publicPath($locale))->assertOk()->assertDontSee('LEGACY_LAYOUT_POISON')->assertSee('application/ld+json', false);
            }
        }
        $services = $pages->firstWhere('import_key', '/en/services/');
        $services->translate('en')->update(['heading' => 'Native service heading', 'body' => '<h2>Photography</h2><p>Editable service copy</p><h2>Film</h2><p>Editable film copy</p><h2>Next project</h2><p>Let us talk</p>']);
        $this->get('/en/services/')->assertOk()->assertSee('Native service heading')->assertSee('Editable service copy')->assertSee('href="/en/photography/"', false)->assertSee('href="/en/film/"', false);
        $this->artisan('charlot:prepare-native-pages')->assertSuccessful();
        $this->assertSame('Native service heading', $services->fresh()->translate('en')->heading);
    }
}
