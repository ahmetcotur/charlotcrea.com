<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PostAdminTest extends TestCase
{
    use RefreshDatabase;

    private function fields(array $overrides = []): array
    {
        return array_merge([
            'published' => '1',
            'title' => ['en' => 'How to prepare a brief', 'tr' => 'Brief nasıl hazırlanır'],
            'path' => ['en' => '/en/blog/how-to-prepare-a-brief/', 'tr' => '/tr/blog/brief-nasil-hazirlanir/'],
            'category' => ['en' => 'Photography', 'tr' => 'Fotoğraf'],
            'body' => ['en' => '<h2>Start here</h2><p>Hello</p>', 'tr' => '<h2>Buradan başla</h2><p>Merhaba</p>'],
        ], $overrides);
    }

    public function test_guest_cannot_access_posts(): void
    {
        $this->get('/admin/posts')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_a_post_with_sanitized_body(): void
    {
        $this->actingAsAdmin();

        $this->post('/admin/posts', $this->fields([
            'body' => ['en' => '<h2>Start</h2><script>alert(1)</script><p>Hello</p>', 'tr' => '<p>Merhaba</p>'],
        ]))->assertRedirect();

        $post = Post::firstWhere('position', 1);
        $this->assertNotNull($post);
        $this->assertSame('How to prepare a brief', $post->translate('en')->title);
        $this->assertStringNotContainsString('<script>', $post->translate('en')->body);
        $this->assertStringContainsString('<h2>Start</h2>', $post->translate('en')->body);
        $this->assertTrue((bool) $post->published);
    }

    public function test_path_must_be_unique_across_content_types(): void
    {
        $this->actingAsAdmin();
        $existing = Post::create(['published' => true, 'position' => 1]);
        $existing->translations()->create(['locale' => 'en', 'title' => 'Existing', 'path' => '/en/blog/taken/', 'active' => true]);

        $this->post('/admin/posts', $this->fields(['path' => ['en' => '/en/blog/taken/', 'tr' => '/tr/blog/yeni/']]))
            ->assertSessionHasErrors('path.en');
    }

    public function test_admin_can_upload_a_cover_image(): void
    {
        $this->actingAsAdmin();
        $post = Post::create(['published' => true, 'position' => 1]);
        foreach (['en', 'tr'] as $locale) {
            $post->translations()->create(['locale' => $locale, 'title' => 'Test', 'path' => "/{$locale}/blog/test/", 'active' => true]);
        }

        $this->put("/admin/posts/{$post->id}", $this->fields([
            'path' => ['en' => '/en/blog/test/', 'tr' => '/tr/blog/test/'],
            'cover' => UploadedFile::fake()->image('cover.jpg', 800, 600),
        ]))->assertRedirect();

        $this->assertTrue($post->refresh()->hasImage('cover'));
    }

    public function test_admin_can_archive_a_post(): void
    {
        $this->actingAsAdmin();
        $post = Post::create(['published' => true, 'position' => 1]);

        $this->delete("/admin/posts/{$post->id}")->assertRedirect(route('admin.posts.index'));

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }
}
