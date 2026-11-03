<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Services\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ContentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_is_complete_and_does_not_overwrite_editor_changes(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->assertDatabaseCount('projects', 19);
        $this->assertDatabaseCount('films', 14);
        $this->assertDatabaseCount('posts', 5);
        $this->assertDatabaseCount('destinations', 16);
        $this->assertDatabaseCount('pages', 11);
        $post = Post::first();
        $post->translate('tr')->heading = 'Panelden düzenlenen başlık';
        $post->save();
        $this->artisan('charlot:import')->assertSuccessful();
        $this->assertSame('Panelden düzenlenen başlık', $post->fresh()->translate('tr')->heading);
    }

    public function test_published_content_is_live_and_unpublishing_invalidates_cache(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $project = Project::where('import_key', '/en/projects/25hours-dubai/')->firstOrFail();
        $this->get('/en/projects/25hours-dubai/')->assertOk()->assertSee('25 Hours Dubai');
        $project->translate('en')->heading = 'Updated portfolio heading';
        $project->save();
        $this->get('/en/projects/25hours-dubai/')->assertOk()->assertSee('Updated portfolio heading');
        $project->published = false;
        $project->save();
        $this->get('/en/projects/25hours-dubai/')->assertNotFound();
        $this->get('/tr/projeler/25hours-dubai/')->assertNotFound();
    }

    public function test_admin_modules_require_login_and_forms_render_for_administrator(): void
    {
        $this->get('/admin/projects')->assertRedirect();
        $this->get('/admin/login')->assertOk();
        $this->artisan('charlot:import')->assertSuccessful();
        $admin = Admin::forceCreate([
            'name' => 'Test Admin', 'email' => 'test@example.test', 'published' => true,
            'role' => 'SUPERADMIN', 'registered_at' => now(), 'password' => Hash::make('test-only-password'),
        ]);
        $this->actingAs($admin, 'twill_users');
        $this->get('/admin/page-manager')->assertOk();
        $this->get('/admin/pages/'.Page::first()->id.'/edit')->assertOk()->assertSee('SEO');
        foreach (['projects' => Project::class, 'posts' => Post::class] as $module => $class) {
            $this->get('/admin/'.$module)->assertOk();
            $this->get('/admin/'.$module.'/'.$class::first()->id.'/edit')->assertOk()->assertSee('SEO');
        }
    }

    public function test_changing_a_path_redirects_old_links_and_sitemap_uses_new_path(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $post = Post::first();
        $old = $post->translate('en')->path;
        $post->translate('en')->path = '/en/blog/new-address/';
        $post->save();
        $this->assertDatabaseHas('legacy_redirects', ['source' => $old, 'target' => '/en/blog/new-address/']);
        $this->get($old.'?source=test')->assertRedirect('/en/blog/new-address/?source=test')->assertStatus(301);
        $this->get('/en/blog/new-address/')->assertOk();
        $this->get('/sitemap.xml')->assertOk()->assertSee('/en/blog/new-address/');
    }

    public function test_preview_is_not_indexable_and_unknown_routes_are_not_served(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->get('/')->assertRedirect('/en/')->assertStatus(301);
        $this->get('/tr/')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->get('/robots.txt')->assertSee('Disallow: /');
        $this->get('/en/does-not-exist/')->assertNotFound();
        $this->get('/.env')->assertNotFound();
    }

    public function test_rich_text_cannot_execute_scripts_or_event_handlers(): void
    {
        $html = RichText::clean('<h2>Başlık</h2><p onclick="evil()">Text <a href="javascript:evil()">link</a></p><script>evil()</script><img src=x onerror=evil()>');
        $this->assertStringContainsString('<h2>Başlık</h2>', $html);
        $this->assertStringNotContainsString('evil', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }
}
