<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MardanPalaceSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_is_seeded_with_bilingual_pages_location_links_and_five_managed_photos_without_overwriting_edits(): void
    {
        Storage::fake(config('twill.media_library.disk'));
        $this->artisan('charlot:import')->assertSuccessful();
        $this->artisan('charlot:seed-mardan-palace')->assertSuccessful();
        $this->artisan('charlot:import-media', ['--project' => '/en/projects/mardan-palace-antalya/'])->assertSuccessful();
        $project = Project::where('import_key', '/en/projects/mardan-palace-antalya/')->firstOrFail();
        $this->assertTrue($project->published);
        $this->assertSame(0, Project::where('import_key', '/en/projects/raffles-the-palm-dubai/')->firstOrFail()->medias()->count());
        $this->assertCount(5, $project->galleryImages());
        $this->assertNotNull($project->coverImage());
        $this->assertSame('Türkiye', $project->country);
        $this->assertSame('Antalya', $project->city);
        $this->assertSame(['/en/destinations/turkey/'], $project->countries->pluck('import_key')->all());
        $this->assertSame(['/en/destinations/turkey/antalya/'], $project->destinations->pluck('import_key')->all());
        foreach (['en', 'tr'] as $locale) {
            $translation = $project->translate($locale);
            $this->assertNotEmpty($translation->seo_title);
            $this->assertNotEmpty($translation->description);
            $html = $this->get($project->publicPath($locale))->assertOk()->assertSee('Mardan Palace Antalya')->getContent();
            $doc = new \DOMDocument;
            @$doc->loadHTML($html);
            $this->assertSame(5, (new \DOMXPath($doc))->query('//div[contains(@class,"photo-gallery")]//img')->length);
        }
        $this->get('/en/photography/')->assertOk()->assertSee('Mardan Palace Antalya');
        foreach (['/en/destinations/turkey/', '/en/destinations/turkey/antalya/'] as $path) {
            $this->get($path)->assertOk()->assertSee('Mardan Palace Antalya');
        }
        $project->translate('en')->update(['title' => 'Edited project title']);
        $project->countries()->detach();
        $project->update(['published' => false]);
        $mediaIds = $project->medias()->get()->modelKeys();
        $this->artisan('charlot:seed-mardan-palace')->assertSuccessful();
        $this->artisan('charlot:import-media', ['--project' => '/en/projects/mardan-palace-antalya/'])->assertSuccessful();
        $project->refresh();
        $this->assertSame('Edited project title', $project->translate('en')->title);
        $this->assertFalse($project->published);
        $this->assertSame(0, $project->countries()->count());
        $this->assertSame($mediaIds, $project->medias()->get()->modelKeys());
        $this->assertSame(1, Project::where('import_key', '/en/projects/mardan-palace-antalya/')->count());
    }

    public function test_missing_destinations_fail_without_creating_a_partial_project(): void
    {
        $this->artisan('charlot:seed-mardan-palace')->assertFailed();
        $this->assertSame(0, Project::count());
        $this->assertSame(0, Destination::count());
    }
}
