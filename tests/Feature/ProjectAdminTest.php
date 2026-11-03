<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProjectAdminTest extends TestCase
{
    use RefreshDatabase;

    private function fields(array $overrides = []): array
    {
        return array_merge([
            'published' => '1',
            'title' => ['en' => 'Test Resort', 'tr' => 'Test Tatil Köyü'],
            'path' => ['en' => '/en/projects/test-resort/', 'tr' => '/tr/projeler/test-tatil-koyu/'],
        ], $overrides);
    }

    public function test_guest_cannot_access_projects(): void
    {
        $this->get('/admin/projects')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_a_project(): void
    {
        $this->actingAsAdmin();

        $this->post('/admin/projects', $this->fields())->assertRedirect();

        $project = Project::firstWhere('position', 1);
        $this->assertNotNull($project);
        $this->assertSame('Test Resort', $project->translate('en')->title);
        $this->assertSame('/tr/projeler/test-tatil-koyu/', $project->translate('tr')->path);
    }

    public function test_path_must_be_unique_across_content_types(): void
    {
        $this->actingAsAdmin();
        $existing = Project::create(['published' => true, 'position' => 1]);
        $existing->translations()->create(['locale' => 'en', 'title' => 'Existing', 'path' => '/en/projects/taken/', 'active' => true]);

        $this->post('/admin/projects', $this->fields(['path' => ['en' => '/en/projects/taken/', 'tr' => '/tr/projeler/yeni/']]))
            ->assertSessionHasErrors('path.en');
    }

    public function test_tagging_a_point_destination_also_tags_its_country_and_fills_labels(): void
    {
        $this->actingAsAdmin();
        $country = Destination::create(['published' => true, 'position' => 1]);
        $country->translations()->create(['locale' => 'en', 'title' => 'Turkiye', 'active' => true]);
        $point = Destination::create(['published' => true, 'position' => 1, 'parent_id' => $country->id]);
        $point->translations()->create(['locale' => 'en', 'title' => 'Kemer', 'active' => true]);

        $this->post('/admin/projects', $this->fields(['destinations' => [$point->id]]))->assertRedirect();

        $project = Project::firstWhere('position', 1);
        $this->assertTrue($project->countries->contains('id', $country->id));
        $this->assertTrue($project->locations->contains('id', $point->id));
        $this->assertSame('Turkiye', $project->country);
        $this->assertSame('Kemer', $project->city);
    }

    public function test_admin_can_upload_cover_and_gallery_images(): void
    {
        $this->actingAsAdmin();
        $project = Project::create(['published' => true, 'position' => 1]);
        foreach (['en', 'tr'] as $locale) {
            $project->translations()->create(['locale' => $locale, 'title' => 'Test', 'path' => "/{$locale}/projects/test/", 'active' => true]);
        }

        $this->put("/admin/projects/{$project->id}", $this->fields([
            'path' => ['en' => '/en/projects/test/', 'tr' => '/tr/projeler/test/'],
            'cover' => UploadedFile::fake()->image('cover.jpg', 800, 600),
            'gallery' => [UploadedFile::fake()->image('g1.jpg'), UploadedFile::fake()->image('g2.jpg')],
        ]))->assertRedirect();

        $project->refresh();
        $this->assertTrue($project->hasImage('cover'));
        $this->assertCount(2, $project->medias->filter(fn ($m) => $m->pivot->role === 'gallery'));
    }

    public function test_admin_can_archive_a_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::create(['published' => true, 'position' => 1]);

        $this->delete("/admin/projects/{$project->id}")->assertRedirect(route('admin.projects.index'));

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
