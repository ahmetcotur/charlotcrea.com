<?php

namespace Tests\Feature;

use App\Models\Destination;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationAdminTest extends TestCase
{
    use RefreshDatabase;

    private function fields(array $overrides = []): array
    {
        return array_merge([
            'published' => '1',
            'title' => ['en' => 'Turkiye', 'tr' => 'Türkiye'],
            'path' => ['en' => '/en/destinations/turkiye/', 'tr' => '/tr/destinasyonlar/turkiye/'],
        ], $overrides);
    }

    public function test_guest_cannot_access_destinations(): void
    {
        $this->get('/admin/destinations')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_a_root_destination(): void
    {
        $this->actingAsAdmin();

        $this->post('/admin/destinations', $this->fields())->assertRedirect();

        $destination = Destination::firstWhere('parent_id', null);
        $this->assertNotNull($destination);
        $this->assertSame('Turkiye', $destination->translate('en')->title);
        $this->assertNull($destination->parent_id);
        $this->assertSame('Turkiye', $destination->country);
        $this->assertNull($destination->city);
    }

    public function test_creating_a_child_derives_country_and_city_from_parent(): void
    {
        $this->actingAsAdmin();
        $country = Destination::create(['published' => true, 'position' => 1]);
        $country->translations()->create(['locale' => 'en', 'title' => 'Turkiye', 'path' => '/en/destinations/turkiye/', 'active' => true]);
        $country->translations()->create(['locale' => 'tr', 'title' => 'Türkiye', 'path' => '/tr/destinasyonlar/turkiye/', 'active' => true]);
        $country->update(['country' => 'Turkiye']);

        $this->post('/admin/destinations', $this->fields([
            'parent_id' => $country->id,
            'title' => ['en' => 'Kemer', 'tr' => 'Kemer'],
            'path' => ['en' => '/en/destinations/turkiye/kemer/', 'tr' => '/tr/destinasyonlar/turkiye/kemer/'],
        ]))->assertRedirect();

        $child = Destination::firstWhere('parent_id', $country->id);
        $this->assertNotNull($child);
        $this->assertSame('Turkiye', $child->country);
        $this->assertSame('Kemer', $child->city);
    }

    public function test_path_must_be_unique_across_content_types(): void
    {
        $this->actingAsAdmin();
        $existing = Destination::create(['published' => true, 'position' => 1]);
        $existing->translations()->create(['locale' => 'en', 'title' => 'Existing', 'path' => '/en/destinations/taken/', 'active' => true]);

        $this->post('/admin/destinations', $this->fields(['path' => ['en' => '/en/destinations/taken/', 'tr' => '/tr/destinasyonlar/yeni/']]))
            ->assertSessionHasErrors('path.en');
    }

    public function test_parent_cannot_be_nested_beyond_nine_levels(): void
    {
        $this->actingAsAdmin();

        $parent = null;
        for ($i = 0; $i < 9; $i++) {
            $node = Destination::create(['published' => true, 'position' => 1, 'parent_id' => $parent?->id]);
            $node->translations()->create(['locale' => 'en', 'title' => "Level $i", 'path' => "/en/destinations/level-$i/", 'active' => true]);
            $parent = $node;
        }

        $this->post('/admin/destinations', $this->fields(['parent_id' => $parent->id]))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_deleting_a_destination_with_children_is_blocked(): void
    {
        $this->actingAsAdmin();
        $country = Destination::create(['published' => true, 'position' => 1]);
        $country->translations()->create(['locale' => 'en', 'title' => 'Turkiye', 'active' => true]);
        $child = Destination::create(['published' => true, 'position' => 1, 'parent_id' => $country->id]);
        $child->translations()->create(['locale' => 'en', 'title' => 'Kemer', 'active' => true]);

        $this->delete("/admin/destinations/{$country->id}")->assertRedirect()->assertSessionHas('error');

        $this->assertDatabaseHas('destinations', ['id' => $country->id, 'deleted_at' => null]);
    }

    public function test_deleting_a_childless_destination_succeeds(): void
    {
        $this->actingAsAdmin();
        $destination = Destination::create(['published' => true, 'position' => 1]);

        $this->delete("/admin/destinations/{$destination->id}")->assertRedirect(route('admin.destinations.index'));

        $this->assertSoftDeleted('destinations', ['id' => $destination->id]);
    }
}
