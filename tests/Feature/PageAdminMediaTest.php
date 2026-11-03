<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PageAdminMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_a_parent_page(): void
    {
        $this->actingAsAdmin();
        $parent = Page::create(['published' => true, 'position' => 1]);
        $parent->translations()->create(['locale' => 'en', 'title' => 'Parent', 'path' => '/en/parent/', 'active' => true]);

        $this->post('/admin/pages', [
            'parent_id' => $parent->id,
            'title' => ['en' => 'Child Page', 'tr' => 'Alt Sayfa'],
        ])->assertRedirect();

        $child = Page::firstWhere('parent_id', $parent->id);
        $this->assertNotNull($child);
    }

    public function test_a_page_cannot_be_moved_under_its_own_descendant(): void
    {
        $this->actingAsAdmin();
        $parent = Page::create(['published' => true, 'position' => 1]);
        $parent->translations()->create(['locale' => 'en', 'title' => 'Parent', 'path' => '/en/parent/', 'active' => true]);
        $child = Page::create(['published' => true, 'position' => 1, 'parent_id' => $parent->id]);
        $child->translations()->create(['locale' => 'en', 'title' => 'Child', 'path' => '/en/parent/child/', 'active' => true]);

        $this->put("/admin/pages/{$parent->id}", [
            'parent_id' => $child->id,
            'title' => ['en' => 'Parent'],
        ])->assertSessionHasErrors('parent_id');
    }

    public function test_admin_can_set_a_background_video_url(): void
    {
        $this->actingAsAdmin();
        $page = Page::create(['published' => true, 'position' => 1, 'import_key' => '/en/home-3/']);
        $page->translations()->create(['locale' => 'en', 'title' => 'Home 3', 'path' => '/en/home-3/', 'active' => true]);

        $this->put("/admin/pages/{$page->id}", [
            'video_url' => 'https://example.test/bg.mp4',
            'title' => ['en' => 'Home 3'],
        ])->assertRedirect();

        $this->assertSame('https://example.test/bg.mp4', $page->refresh()->video_url);
    }

    public function test_admin_can_upload_a_cover_image(): void
    {
        $this->actingAsAdmin();
        $page = Page::create(['published' => true, 'position' => 1]);
        $page->translations()->create(['locale' => 'en', 'title' => 'Test', 'path' => '/en/test/', 'active' => true]);

        $this->put("/admin/pages/{$page->id}", [
            'title' => ['en' => 'Test'],
            'cover' => UploadedFile::fake()->image('cover.jpg', 800, 600),
        ])->assertRedirect();

        $this->assertTrue($page->refresh()->hasImage('cover'));
    }
}
