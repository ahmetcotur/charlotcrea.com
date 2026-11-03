<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use App\Models\Page;
use App\Models\Project;
use App\Models\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortfolioSeoAndBrandTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): void
    {
        Storage::fake(config('twill.media_library.disk'));
        $this->artisan('charlot:import')->assertSuccessful();
        $this->artisan('charlot:import-references')->assertSuccessful();
        $this->artisan('charlot:prepare-portfolio-content')->assertSuccessful();
    }

    public function test_portfolio_seo_copy_and_internal_links_work_in_each_language(): void
    {
        $this->prepare();
        foreach (['tr' => ['photography' => '/tr/fotograf/', 'film' => '/tr/film/', 'services' => '/tr/hizmetler/'], 'en' => ['photography' => '/en/photography/', 'film' => '/en/film/', 'services' => '/en/services/']] as $locale => $paths) {
            foreach (['photography', 'film'] as $type) {
                $html = $this->get($paths[$type])->assertOk()->assertSee('portfolio-editorial', false)->getContent();
                $doc = new \DOMDocument;
                @$doc->loadHTML($html);
                $xp = new \DOMXPath($doc);
                $this->assertSame(1, $xp->query('//h1')->length);
                $this->assertSame(3, $xp->query('//section[contains(@class,"portfolio-editorial")]//h3')->length);
                $this->assertGreaterThan(180, str_word_count($xp->query('//section[contains(@class,"portfolio-editorial")]')->item(0)->textContent));
                $this->assertSame(0, $xp->query('//section[contains(@class,"portfolio-editorial")]//a[not(starts-with(@href,"/'.$locale.'/"))]')->length);
            }
            $html = $this->get($paths['services'])->assertOk()->getContent();
            $doc = new \DOMDocument;
            @$doc->loadHTML($html);
            $xp = new \DOMXPath($doc);
            foreach (['photography', 'film'] as $type) {
                $this->assertSame($paths[$type], $xp->query('//h2[@id="'.$type.'"]/a')->item(0)->getAttribute('href'));
            }
        }
    }

    public function test_seeding_preserves_editor_changes_and_service_links_follow_current_paths(): void
    {
        $this->prepare();
        $page = Page::where('import_key', '/en/photography/')->firstOrFail();
        $page->translate('tr')->update(['body' => '<h2>Yeni SEO metni</h2><p>Editör düzenlemesi.</p>', 'path' => '/tr/fotograf-calismalari/']);
        $this->artisan('charlot:prepare-portfolio-content')->assertSuccessful();
        $this->get('/tr/fotograf-calismalari/')->assertOk()->assertSee('Editör düzenlemesi.');
        $this->get('/tr/hizmetler/')->assertOk()->assertSee('href="/tr/fotograf-calismalari/"', false);
    }

    public function test_project_brand_logo_and_country_are_read_from_related_records_in_both_languages(): void
    {
        $this->prepare();
        $project = Project::where('import_key', '/en/projects/25hours-dubai/')->firstOrFail();
        $brand = Reference::where('import_key', '/assets/references/25hours.png')->firstOrFail();
        $this->assertSame($brand->id, $project->reference_id);
        foreach (['tr', 'en'] as $locale) {
            $html = $this->get($project->publicPath($locale))->assertOk()->assertSee('25hours Hotels')->assertSee('project-credit', false)->getContent();
            $doc = new \DOMDocument;
            @$doc->loadHTML($html);
            $xp = new \DOMXPath($doc);
            $logo = $xp->query('//div[contains(@class,"project-brand")]//img')->item(0);
            $this->assertNotNull($logo);
            $this->assertSame('25hours Hotels', $logo->getAttribute('alt'));
            $this->assertSame(1, $xp->query('//div[contains(@class,"project-country")]//a')->length);
        }
        $brand->update(['published' => false]);
        $this->get($project->publicPath('tr'))->assertOk()->assertDontSee('class="project-brand"', false);
    }

    public function test_admin_can_choose_and_clear_project_brand(): void
    {
        $this->prepare();
        $admin = Admin::forceCreate(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => bcrypt('test-only'), 'role' => 'SUPERADMIN', 'published' => true, 'registered_at' => now()]);
        $this->actingAs($admin, 'twill_users');
        $project = Project::where('import_key', '/en/projects/25hours-dubai/')->firstOrFail();
        $fields = ['title' => ['tr' => 'Test', 'en' => 'Test'], 'published' => true, 'reference_id' => $project->reference_id];
        $this->put(route('admin.projects.update', $project), $fields)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNotNull($project->fresh()->reference_id);
        $this->put(route('admin.projects.update', $project), [...$fields, 'reference_id' => null])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull($project->fresh()->reference_id);
    }
}
