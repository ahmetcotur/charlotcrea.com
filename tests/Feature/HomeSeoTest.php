<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Services\HomeSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_metadata_is_repaired_in_both_languages_without_changing_layouts_or_editorial_edits(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $video = Page::where('import_key', '/en/home-3/')->firstOrFail();
        $source = $video->source_data;
        $video->translate('en')->forceFill(['description' => 'An alternative Charlot Crea homepage concept with a video hero.'])->save();
        $video->translate('tr')->forceFill(['seo_title' => 'Özel editör başlığı'])->save();

        $this->artisan('charlot:prepare-home-seo')->assertSuccessful();
        $copy = json_decode(file_get_contents(base_path('data/home-seo-content.json')), true);
        foreach (HomeSections::HOMES as $key) {
            $page = Page::with('translations')->where('import_key', $key)->firstOrFail();
            foreach (['en', 'tr'] as $locale) {
                $this->assertSame($copy[$key][$locale]['description'], $page->translate($locale)->description);
                $this->get($page->publicPath($locale))->assertOk()
                    ->assertSee($copy[$key][$locale]['description'], false)
                    ->assertDontSee('homepage concept');
            }
        }
        $video->refresh();
        $this->assertSame('Özel editör başlığı', $video->translate('tr')->seo_title);
        $this->assertSame($source, array_diff_key($video->source_data, ['home_seo_version' => true]));
        $video->translate('en')->forceFill(['description' => 'Edited after preparation.'])->save();
        $this->artisan('charlot:prepare-home-seo')->assertSuccessful();
        $this->assertSame('Edited after preparation.', $video->fresh()->translate('en')->description);
    }
}
