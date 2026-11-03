<?php

namespace Tests\Feature;

use A17\Twill\Models\Media;
use A17\Twill\Models\User as Admin;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PageManagementTest extends TestCase
{
    use RefreshDatabase;

    private function setupEditor(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $admin = Admin::forceCreate(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => Hash::make('test-only'), 'registered_at' => now(), 'role' => 'SUPERADMIN', 'published' => true]);
        $this->actingAs($admin, 'twill_users');
    }

    private function fields(Page $page): array
    {
        $fields = ['published' => $page->published];
        foreach (['title', 'path', 'heading', 'intro', 'body', 'seo_title', 'description'] as $field) {
            foreach (['en', 'tr'] as $locale) {
                $fields[$field][$locale] = $page->translate($locale)?->$field;
            }
        }

        return $fields;
    }

    public function test_page_overview_has_clear_names_and_module_shortcuts(): void
    {
        $this->get('/admin/page-manager')->assertRedirect();
        $this->setupEditor();
        $this->get('/admin/page-manager')->assertOk()->assertSee('Ana sayfalar')->assertSee('Kurumsal sayfalar')->assertSee('İçerik sayfaları')->assertSee('Referanslar')->assertSee('Firma ve logoları yönet')->assertSee('/admin/pages/create', false);
    }

    public function test_page_forms_show_only_relevant_media_fields_for_each_page_type(): void
    {
        $this->setupEditor();
        $page = Page::where('import_key', '/en/references/')->firstOrFail();
        $this->get('/admin/pages/'.$page->id.'/edit')->assertOk()->assertSee('SEO başlığı')->assertSee('Üst sayfa')->assertDontSee('Sayfa görseli')->assertDontSee('Şeritteki fotoğraflar');
        $page = Page::where('import_key', '/en/home-2/')->firstOrFail();
        $this->get('/admin/pages/'.$page->id.'/edit')->assertOk()->assertSee('Şeritteki fotoğraflar')->assertDontSee('Arka plan videosu');
        $page = Page::where('import_key', '/en/home-3/')->firstOrFail();
        $this->get('/admin/pages/'.$page->id.'/edit')->assertOk()->assertSee('Sayfa görseli')->assertSee('Arka plan videosu');
        $this->get('/admin/pages/create')->assertOk()->assertSee('Tüm bölüm metinleri');
    }

    public function test_new_pages_get_bilingual_addresses_and_render_editable_content(): void
    {
        $this->setupEditor();
        $fields = ['title' => ['en' => 'Our approach', 'tr' => 'Yaklaşımımız'], 'body' => ['en' => '<h2>How we work</h2><p>Our process.</p>', 'tr' => '<h2>Nasıl çalışırız</h2><p>Sürecimiz.</p>'], 'published' => true];
        $this->post('/admin/pages', $fields)->assertRedirect();
        $page = Page::whereHas('translations', fn ($q) => $q->where('title', 'Our approach'))->firstOrFail();
        $this->assertSame('/en/our-approach/', $page->translate('en')->path);
        $this->assertSame('/tr/yaklasimimiz/', $page->translate('tr')->path);
        $this->assertFalse($page->published, 'New pages start as drafts; there is no publish toggle on the create screen.');
        $this->put('/admin/pages/'.$page->id, array_merge($this->fields($page), ['published' => true]))->assertRedirect();
        $this->get('/tr/yaklasimimiz/')->assertOk()->assertSee('Sürecimiz.')->assertSee('Nasıl çalışırız');
        $this->get('/en/our-approach/')->assertOk()->assertSee('Our process.');
        $fields = $this->fields($page);
        $fields['title']['en'] = 'Changed page name';
        $this->put('/admin/pages/'.$page->id, $fields)->assertRedirect();
        $this->assertSame('/en/our-approach/', $page->fresh()->translate('en')->path);
    }

    public function test_new_child_pages_use_parent_paths_and_slug_collisions_get_suffixes(): void
    {
        $this->setupEditor();
        $parent = Page::where('import_key', '/en/about/')->firstOrFail();
        $fields = ['title' => ['en' => 'Ekibimiz', 'tr' => 'Ekibimiz'], 'body' => ['tr' => '<p>Ekibimiz hakkında.</p>'], 'parent_id' => $parent->id, 'published' => false];
        $this->post('/admin/pages', $fields)->assertRedirect();
        $page = Page::whereHas('translations', fn ($q) => $q->where('path', '/tr/hakkimizda/ekibimiz/'))->firstOrFail();
        $this->assertSame($parent->id, $page->parent_id);
        $this->assertSame('/en/about/ekibimiz/', $page->translate('en')->path);
        $this->post('/admin/pages', $fields)->assertRedirect();
        $this->assertDatabaseHas('page_translations', ['path' => '/tr/hakkimizda/ekibimiz-2/']);
    }

    public function test_existing_page_names_and_media_initialization_preserve_editor_changes(): void
    {
        $this->setupEditor();
        $page = Page::where('import_key', '/en/home-2/')->firstOrFail();
        $this->assertSame('Ana sayfa · Fotoğraf şeridi', $page->translate('tr')->title);
        $this->assertCount(12, $page->source_data['images']);
        $page->translate('tr')->title = 'Özel giriş sayfası';
        $page->save();
        $this->artisan('charlot:prepare-page-editor')->assertSuccessful();
        $this->assertSame('Özel giriş sayfası', $page->fresh()->translate('tr')->title);
    }

    public function test_video_homepage_edits_use_video_and_poster_without_touching_other_images(): void
    {
        $this->setupEditor();
        $page = Page::where('import_key', '/en/home-3/')->firstOrFail();
        $fields = $this->fields($page);
        $fields['video_url'] = 'https://example.test/new-background.mp4';
        $this->put('/admin/pages/'.$page->id, $fields)->assertRedirect();
        $this->get('/en/home-3/')->assertOk()->assertSee('src="https://example.test/new-background.mp4"', false);
    }

    public function test_photo_strip_uses_cms_gallery_order_and_supports_removing_all_images(): void
    {
        $this->setupEditor();
        $page = Page::where('import_key', '/en/home-2/')->firstOrFail();
        $one = Media::create(['uuid' => 'editor-one.jpg', 'filename' => 'One.jpg', 'alt_text' => 'First photograph', 'width' => 800, 'height' => 1200]);
        $two = Media::create(['uuid' => 'editor-two.jpg', 'filename' => 'Two.jpg', 'alt_text' => 'Second photograph', 'width' => 800, 'height' => 1200]);
        foreach ([$two, $one] as $i => $media) {
            $page->medias()->attach($media->id, ['role' => 'gallery', 'crop' => 'default', 'ratio' => 'default', 'position' => $i, 'locale' => 'en', 'metadatas' => json_encode(['caption' => null, 'altText' => null, 'video' => null])]);
        }
        $source = $page->source_data;
        $source['media_imported_gallery'] = true;
        $page->source_data = $source;
        $page->save();
        $html = $this->get('/en/home-2/')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'editor-one.jpg'), strpos($html, 'editor-two.jpg'));
        $fields = $this->fields($page);
        $fields['remove_gallery'] = [$one->id, $two->id];
        $this->put('/admin/pages/'.$page->id, $fields)->assertRedirect();
        $this->get('/en/home-2/')->assertOk()->assertDontSee('editor-one.jpg');
    }
}
