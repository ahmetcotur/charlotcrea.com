<?php

namespace Tests\Feature;

use App\Models\Destination;
use App\Models\Film;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HierarchyAndLiveContentTest extends TestCase
{
    use RefreshDatabase;

    private function import(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
    }

    private function destination(string $slug): Destination
    {
        return Destination::where('import_key', '/en/destinations/'.$slug.'/')->firstOrFail();
    }

    public function test_country_region_city_relationships_are_imported_and_survive_repeat_import(): void
    {
        $this->import();
        $country = $this->destination('turkey');
        $region = $this->destination('turkey/antalya');
        $city = $this->destination('turkey/kemer');
        $this->assertSame($country->id, $region->parent_id);
        $this->assertSame($region->id, $city->parent_id);
        $this->assertTrue($city->isDescendantOf($country));
        $city->parent_id = $country->id;
        $city->save();
        $this->import();
        $this->assertSame($country->id, $city->fresh()->parent_id);
        $this->assertFalse(Destination::isBroken());
    }

    public function test_child_work_rolls_up_to_the_region_and_country_and_new_slugs_are_used(): void
    {
        $this->import();
        $country = $this->destination('turkey');
        $region = $this->destination('turkey/antalya');
        $city = $this->destination('turkey/kemer');
        $project = Project::where('import_key', '/en/projects/balmy-beach-resort-kemer/')->firstOrFail();
        $project->destinations()->sync([$city->id]);
        $project->translate('en')->path = '/en/projects/new-balmy-address/';
        $project->save();
        foreach ([$city, $region, $country] as $destination) {
            $this->get($destination->translate('en')->path)->assertOk()->assertSee('href="/en/projects/new-balmy-address/"', false);
        }
        $this->get('/en/photography/')->assertSee('href="/en/projects/new-balmy-address/"', false);
        $project->published = false;
        $project->save();
        $this->get('/en/photography/')->assertDontSee('href="/en/projects/new-balmy-address/"', false);
        $this->get($city->translate('en')->path)->assertDontSee('href="/en/projects/new-balmy-address/"', false);
    }

    public function test_core_content_uses_blade_and_new_posts_appear_without_snapshot_html(): void
    {
        $this->import();
        $post = new Post(['published' => true]);
        foreach (['en', 'tr'] as $locale) {
            $post->translateOrNew($locale)->fill(['active' => true, 'title' => 'A new story', 'heading' => 'A new story', 'path' => '/'.$locale.'/blog/new-story/', 'intro' => 'A newly published story.', 'body' => '<h2>First section</h2><p>New content.</p>']);
        }
        $post->save();
        $this->get('/en/blog/')->assertOk()->assertSee('href="/en/blog/new-story/"', false);
        $response = $this->get('/en/blog/new-story/')->assertOk()->assertSee('New content.')->assertSee('section-1');
        $this->assertSame(1, substr_count($response->getContent(), '<h1>'));
        $project = Project::first();
        $project->translate('en')->legacy_html = '<h1>SNAPSHOT MUST NOT BE USED</h1>';
        $project->save();
        $this->get($project->translate('en')->path)->assertOk()->assertDontSee('SNAPSHOT MUST NOT BE USED');
    }

    public function test_drive_film_uses_embed_player_instead_of_mp4_attribute(): void
    {
        $this->import();
        $film = Film::where('import_key', '/en/films/ali-bey-hotels/')->firstOrFail();
        $this->get($film->translate('en')->path)->assertOk()->assertSee('data-drive-id=', false)->assertDontSee('data-video-src=', false)->assertSee('film-dialog');
    }
}
