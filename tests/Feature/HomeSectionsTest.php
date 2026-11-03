<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Project;
use App\Services\HomeSections;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSectionsTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->actingAs(Admin::forceCreate(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => bcrypt('test-only'), 'role' => 'SUPERADMIN', 'published' => true, 'registered_at' => now()]), 'twill_users');
    }

    private function fields(Page $page): array
    {
        return ['published' => true, 'title' => ['tr' => $page->translate('tr')->title, 'en' => $page->translate('en')->title]];
    }

    public function test_each_homepage_has_its_own_sections_and_editing_does_not_change_other_variants(): void
    {
        $this->editor();
        foreach (HomeSections::HOMES as $identity) {
            $page = Page::where('import_key', $identity)->firstOrFail();
            $this->get(route('admin.pages.edit', $page))->assertOk()->assertSee('Ana sayfa bölümleri')->assertSee('Seçili projeler');
        }
        $home = Page::where('import_key', '/en/home-2/')->firstOrFail();
        $this->get('/en/home-2/')->assertOk()->assertSee('data-home-section="destinations"', false);
        $this->put(route('admin.pages.update', $home), [...$this->fields($home), 'home_sections' => ['destinations' => ['visible' => 0, 'position' => 4, 'mode' => 'automatic', 'limit' => 4]]])->assertRedirect()->assertSessionHasNoErrors();
        foreach (['/en/home-2/', '/tr/home-2/'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('data-home-section="destinations"', false);
        }
        foreach (['/en/', '/en/home-3/'] as $path) {
            $this->get($path)->assertOk()->assertSee('data-home-section="destinations"', false);
        }
        $this->assertFalse($home->fresh()->source_data['home_sections']['destinations']['visible']);
    }

    public function test_manual_selection_is_ordered_and_reads_live_titles_paths_and_publication_state(): void
    {
        $this->editor();
        $home = Page::where('import_key', '/en/')->firstOrFail();
        $projects = Project::orderBy('id')->take(2)->get();
        $ids = $projects->pluck('id')->reverse()->values()->all();
        foreach ($projects as $project) {
            $project->update(['published' => true]);
            foreach ($project->translations as $translation) {
                $translation->update(['title' => 'LIVE PROJECT '.$project->id]);
            }
        }
        $this->put(route('admin.pages.update', $home), [...$this->fields($home), 'home_sections' => ['projects' => [
            'visible' => 1, 'position' => 2, 'mode' => 'manual', 'items' => $ids, 'limit' => 2,
            'texts' => ['en' => [1 => 'Chosen projects'], 'tr' => [1 => 'Secilen projeler']],
        ]]])->assertRedirect()->assertSessionHasNoErrors();
        $html = $this->get('/en/')->assertOk()->assertSee('Chosen projects')->getContent();
        $doc = new \DOMDocument;
        @$doc->loadHTML($html);
        $xp = new \DOMXPath($doc);
        $cards = $xp->query('//*[@data-home-section="projects"]//a[contains(@class,"project-card")]');
        $this->assertSame(2, $cards->length);
        $this->assertStringContainsString('LIVE PROJECT '.$ids[0], $cards->item(0)->textContent);
        $this->assertSame($projects->firstWhere('id', $ids[0])->publicPath('en'), $cards->item(0)->getAttribute('href'));
        $projects->firstWhere('id', $ids[0])->update(['published' => false]);
        $this->get('/en/')->assertOk()->assertDontSee('LIVE PROJECT '.$ids[0]);
        $other = Page::where('import_key', '/en/home-3/')->firstOrFail();
        $this->assertArrayNotHasKey('home_sections', $other->source_data ?? []);
        $this->get(route('admin.pages.edit', $home))->assertOk()->assertSee('Chosen projects');
    }

    public function test_section_order_and_preview_are_applied_without_persisting_preview(): void
    {
        $this->editor();
        $home = Page::where('import_key', '/en/home-3/')->firstOrFail();
        $sections = ['cta' => ['visible' => 1, 'position' => 1, 'texts' => ['en' => [1 => 'Preview CTA']]], 'hero' => ['visible' => 1, 'position' => 2]];
        $html = $this->post(route('admin.content.preview', ['type' => 'pages', 'id' => $home->id]), ['preview_locale' => 'en', 'home_sections' => $sections])->assertOk()->assertSee('Preview CTA')->getContent();
        $this->assertLessThan(strpos($html, 'data-home-section="hero"'), strpos($html, 'data-home-section="cta"'));
        $this->assertArrayNotHasKey('home_sections', $home->fresh()->source_data ?? []);
        $this->put(route('admin.pages.update', $home), [...$this->fields($home), 'home_sections' => $sections])->assertRedirect()->assertSessionHasNoErrors();
        $this->get('/en/home-3/')->assertOk()->assertSee('Preview CTA');
    }

    public function test_unknown_sections_and_nonexistent_selected_records_are_rejected(): void
    {
        $this->editor();
        $home = Page::where('import_key', '/en/')->firstOrFail();
        $this->putJson(route('admin.pages.update', $home), [...$this->fields($home), 'home_sections' => ['unknown' => ['visible' => 1]]])->assertUnprocessable();
        $this->putJson(route('admin.pages.update', $home), [...$this->fields($home), 'home_sections' => ['projects' => ['visible' => 1, 'position' => 2, 'mode' => 'manual', 'limit' => 4, 'items' => [999999]]]])->assertUnprocessable();
    }

    public function test_home_locations_use_compact_country_city_context_instead_of_seo_intro(): void
    {
        $this->editor();
        $country = Destination::where('import_key', '/en/destinations/uae/')->firstOrFail();
        foreach ($country->translations as $translation) {
            $translation->update(['intro' => 'LONG SEO INTRO '.str_repeat('Detailed destination content. ', 15)]);
        }
        foreach (['/en/', '/tr/', '/en/home-2/', '/tr/home-2/', '/en/home-3/', '/tr/home-3/'] as $path) {
            $html = $this->get($path)->assertOk()->assertSee('/site-content.css', false)->getContent();
            $doc = new \DOMDocument;
            @$doc->loadHTML($html);
            $xp = new \DOMXPath($doc);
            $section = $xp->query('//*[@data-home-section="destinations"]')->item(0);
            $this->assertStringNotContainsString('LONG SEO INTRO', $section->textContent);
            $this->assertStringContainsString('Dubai', $section->textContent);
            foreach ($xp->query('.//a[contains(@class,"destination-row")]/p', $section) as $summary) {
                $this->assertLessThan(100, mb_strlen(trim($summary->textContent)));
            }
        }
        $this->get('/en/destinations/uae/')->assertOk()->assertSee('LONG SEO INTRO');
    }
}
