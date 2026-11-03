<?php

namespace Tests\Feature;

use A17\Twill\Models\User as Admin;
use App\Models\Destination;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentEditingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): void
    {
        $this->actingAs(Admin::forceCreate(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => bcrypt('test-only'), 'role' => 'SUPERADMIN', 'published' => true, 'registered_at' => now()]), 'twill_users');
    }

    public function test_new_content_generates_unique_addresses_and_renaming_keeps_existing_address(): void
    {
        $this->editor();
        $fields = ['title' => ['tr' => 'İstanbul Çekimi', 'en' => 'Istanbul Shoot']];
        foreach (['projects', 'films', 'posts', 'destinations'] as $type) {
            $this->post('/admin/'.$type, $fields)->assertRedirect()->assertSessionHasNoErrors();
        }
        $project = Project::sole();
        $this->assertSame('/tr/projeler/istanbul-cekimi/', $project->translate('tr')->path);
        $this->post('/admin/projects', $fields)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('/en/projects/istanbul-shoot-2/', Project::latest('id')->first()->translate('en')->path);
        $this->put('/admin/projects/'.$project->id, ['title' => ['tr' => 'Yeni başlık', 'en' => 'New title']])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('/en/projects/istanbul-shoot/', $project->fresh()->translate('en')->path);
        $parent = Destination::sole();
        $this->post('/admin/destinations', [...$fields, 'title' => ['tr' => 'Kemer', 'en' => 'Kemer'], 'parent_id' => $parent->id])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('/tr/destinasyonlar/istanbul-cekimi/kemer/', Destination::latest('id')->first()->translate('tr')->path);
    }

    public function test_unsaved_preview_is_private_and_does_not_persist_or_publish_changes(): void
    {
        $this->editor();
        $this->artisan('charlot:import')->assertSuccessful();
        foreach (['posts' => Post::firstOrFail(), 'pages' => Page::where('import_key', '/en/')->firstOrFail()] as $type => $model) {
            $before = $model->translate('tr')->heading;
            $url = route('admin.content.preview', ['type' => $type, 'id' => $model->id]);
            $this->post($url, ['preview_locale' => 'tr', 'heading' => ['tr' => 'Unsaved heading']])
                ->assertOk()->assertSee('Unsaved heading')->assertSee('Taslak önizleme')
                ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
            $this->assertSame($before, $model->fresh()->translate('tr')->heading);
        }
        $this->app['auth']->forgetGuards();
        $this->post($url, ['preview_locale' => 'tr'])->assertRedirect();
    }

    public function test_gallery_order_persists_and_cannot_include_another_projects_media(): void
    {
        $this->editor();
        Storage::fake(config('twill.media_library.disk'));
        $this->post('/admin/projects', [
            'title' => ['tr' => 'Çekim', 'en' => 'Shoot'],
            'gallery' => [UploadedFile::fake()->image('one.jpg'), UploadedFile::fake()->image('two.jpg')],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $project = Project::sole();
        $ids = $project->medias->pluck('id')->all();
        $this->put('/admin/projects/'.$project->id, ['title' => ['tr' => 'Çekim', 'en' => 'Shoot'], 'gallery_order' => array_reverse($ids)])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(array_reverse($ids), $project->fresh()->medias->pluck('id')->all());
        $this->putJson('/admin/projects/'.$project->id, ['title' => ['tr' => 'Çekim', 'en' => 'Shoot'], 'gallery_order' => [999999]])->assertUnprocessable();
        $this->assertSame(array_reverse($ids), $project->fresh()->medias->pluck('id')->all());
    }
}
