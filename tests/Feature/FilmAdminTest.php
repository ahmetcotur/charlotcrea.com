<?php

namespace Tests\Feature;

use App\Models\Film;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilmAdminTest extends TestCase
{
    use RefreshDatabase;

    private function fields(array $overrides = []): array
    {
        return array_merge([
            'published' => '1',
            'video_url' => 'https://drive.google.com/file/d/abc123/preview',
            'title' => ['en' => 'Test Showreel', 'tr' => 'Test Showreel'],
            'path' => ['en' => '/en/films/test-showreel/', 'tr' => '/tr/filmler/test-showreel/'],
        ], $overrides);
    }

    public function test_guest_cannot_access_films(): void
    {
        $this->get('/admin/films')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_a_film_linked_to_a_project(): void
    {
        $this->actingAsAdmin();
        $project = Project::create(['published' => true, 'position' => 1]);
        $project->translations()->create(['locale' => 'en', 'title' => 'Parent Project', 'path' => '/en/projects/parent/', 'active' => true]);

        $this->post('/admin/films', $this->fields(['project_id' => $project->id]))->assertRedirect();

        $film = Film::firstWhere('position', 1);
        $this->assertNotNull($film);
        $this->assertSame($project->id, $film->project_id);
        $this->assertSame('Test Showreel', $film->translate('en')->title);
    }

    public function test_path_must_be_unique_across_content_types(): void
    {
        $this->actingAsAdmin();
        $existing = Film::create(['published' => true, 'position' => 1]);
        $existing->translations()->create(['locale' => 'en', 'title' => 'Existing', 'path' => '/en/films/taken/', 'active' => true]);

        $this->post('/admin/films', $this->fields(['path' => ['en' => '/en/films/taken/', 'tr' => '/tr/filmler/yeni/']]))
            ->assertSessionHasErrors('path.en');
    }

    public function test_admin_can_archive_a_film(): void
    {
        $this->actingAsAdmin();
        $film = Film::create(['published' => true, 'position' => 1]);

        $this->delete("/admin/films/{$film->id}")->assertRedirect(route('admin.films.index'));

        $this->assertSoftDeleted('films', ['id' => $film->id]);
    }
}
