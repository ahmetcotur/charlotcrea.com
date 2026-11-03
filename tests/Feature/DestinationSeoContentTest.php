<?php

namespace Tests\Feature;

use App\Models\Destination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DestinationSeoContentTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): void
    {
        Storage::fake('local');
        $this->artisan('charlot:import')->assertSuccessful();
        $this->artisan('charlot:prepare-enquiry-pages')->assertSuccessful();
        $this->assertNotNull(Destination::where('import_key', '/en/destinations/saudi-arabia/makkah/')->first());
    }

    public function test_all_locations_receive_bilingual_unique_metadata_copy_faq_and_keyword_plan_without_changing_paths(): void
    {
        $this->prepare();
        $paths = Destination::with('translations')->get()->flatMap(fn ($d) => $d->translations->pluck('path'))->sort()->values()->all();
        $this->artisan('charlot:prepare-destination-seo')->assertSuccessful();
        $titles = [];
        foreach (Destination::with('translations')->get() as $destination) {
            foreach (['en', 'tr'] as $locale) {
                $translation = $destination->translate($locale);
                $titles[] = $translation->seo_title;
                $this->assertNotEmpty($translation->body);
                $this->assertNotEmpty($translation->faq_body);
                $this->assertCount(4, $destination->source_data['seo_keywords'][$locale]);
                $html = $this->get($translation->path)->assertOk()->assertSee('href="'.($locale === 'tr' ? '/tr/teklif-al/' : '/en/request-a-quote/').'"', false)->getContent();
                $doc = new \DOMDocument;
                @$doc->loadHTML($html);
                $xp = new \DOMXPath($doc);
                $this->assertSame(1, $xp->query('//h1')->length);
                $this->assertSame(2, $xp->query('//div[contains(@class,"destination-faq")]//details')->length);
            }
        }
        $this->assertCount(34, array_unique($titles));
        $this->assertSame($paths, Destination::with('translations')->get()->flatMap(fn ($d) => $d->translations->pluck('path'))->sort()->values()->all());
        $this->get('/en/destinations/')->assertOk()->assertSee('portfolio-editorial', false);
        $this->assertCount(1, Storage::disk('local')->allFiles('seo-backups'));
    }

    public function test_reapplying_preserves_panel_edits_and_draft_publication_state(): void
    {
        $this->prepare();
        $this->artisan('charlot:prepare-destination-seo')->assertSuccessful();
        $destination = Destination::where('import_key', '/en/destinations/uae/dubai/')->firstOrFail();
        $destination->translate('tr')->update(['body' => '<h2>Editor copy</h2><p>Keep this text.</p>']);
        $destination->update(['published' => false]);
        $this->artisan('charlot:prepare-destination-seo')->assertSuccessful();
        $this->assertSame('<h2>Editor copy</h2><p>Keep this text.</p>', $destination->fresh()->translate('tr')->body);
        $this->assertFalse($destination->fresh()->published);
    }

    public function test_keyword_plan_can_be_edited_in_admin(): void
    {
        $this->prepare();
        $this->artisan('charlot:prepare-destination-seo')->assertSuccessful();
        $this->actingAsAdmin();
        $destination = Destination::where('import_key', '/en/destinations/uae/dubai/')->firstOrFail();
        $this->get(route('admin.destinations.edit', $destination))->assertOk()->assertSee('Hedef anahtar kelimeler');
        $this->put(route('admin.destinations.update', $destination), ['published' => true, 'parent_id' => $destination->parent_id, 'title' => ['tr' => 'Dubai', 'en' => 'Dubai'], 'seo_keywords' => ['tr' => 'Dubai otel çekimi, Dubai otel çekimi, Dubai tanıtım filmi', 'en' => 'Dubai hotel photography']])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['Dubai otel çekimi', 'Dubai tanıtım filmi'], $destination->fresh()->source_data['seo_keywords']['tr']);
    }
}
