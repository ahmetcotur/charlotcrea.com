<?php

namespace Tests\Feature;

use App\Models\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ReferenceAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_references(): void
    {
        $this->get('/admin/references')->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_create_a_reference_with_both_locales(): void
    {
        $this->actingAsAdmin();

        $this->post('/admin/references', [
            'published' => '1',
            'title' => ['en' => 'Accor', 'tr' => 'Accor'],
            'description' => ['en' => '', 'tr' => ''],
        ])->assertRedirect();

        $reference = Reference::firstWhere('import_key', null);
        $this->assertNotNull($reference);
        $this->assertSame('Accor', $reference->translate('en')->title);
        $this->assertSame('Accor', $reference->translate('tr')->title);
        $this->assertTrue((bool) $reference->published);
    }

    public function test_title_is_required_for_both_locales(): void
    {
        $this->actingAsAdmin();

        $this->post('/admin/references', ['title' => ['en' => 'Accor', 'tr' => '']])
            ->assertSessionHasErrors('title.tr');
    }

    public function test_admin_can_upload_and_remove_a_logo(): void
    {
        $this->actingAsAdmin();
        $reference = Reference::create(['published' => true, 'position' => 1]);
        foreach (['en', 'tr'] as $locale) {
            $reference->translations()->create(['locale' => $locale, 'title' => 'Test', 'active' => true]);
        }

        $file = UploadedFile::fake()->image('logo.jpg', 200, 100);
        $this->put("/admin/references/{$reference->id}", [
            'title' => ['en' => 'Test', 'tr' => 'Test'],
            'logo' => $file,
        ])->assertRedirect();

        $this->assertTrue($reference->refresh()->hasImage('logo'));

        $this->put("/admin/references/{$reference->id}", [
            'title' => ['en' => 'Test', 'tr' => 'Test'],
            'remove_logo' => '1',
        ])->assertRedirect();

        $this->assertFalse($reference->refresh()->hasImage('logo'));
    }

    public function test_admin_can_archive_a_reference(): void
    {
        $this->actingAsAdmin();
        $reference = Reference::create(['published' => true, 'position' => 1]);

        $this->delete("/admin/references/{$reference->id}")->assertRedirect(route('admin.references.index'));

        $this->assertSoftDeleted('references', ['id' => $reference->id]);
    }
}
