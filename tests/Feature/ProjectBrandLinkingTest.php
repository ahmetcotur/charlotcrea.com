<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Reference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectBrandLinkingTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_brand_mapping_links_projects_and_preserves_manual_selection(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->artisan('charlot:import-references')->assertSuccessful();
        $project = Project::where('import_key', '/en/projects/fairmont-makkah/')->firstOrFail();
        $manual = Reference::where('import_key', '/assets/references/accor.png')->firstOrFail();
        $project->update(['reference_id' => $manual->id]);
        $this->artisan('charlot:link-project-brands')->assertSuccessful();
        $this->assertSame(18, Project::whereNotNull('reference_id')->count());
        $this->assertSame($manual->id, $project->fresh()->reference_id);
        $balmy = Project::where('import_key', '/en/projects/balmy-beach-resort-kemer/')->firstOrFail();
        $this->assertSame('Balmy Beach Resort Kemer', $balmy->reference->title);
        $this->assertTrue($balmy->reference->hasImage('logo'));
        $this->assertNull(Project::where('import_key', '/en/projects/architecture-interiors/')->firstOrFail()->reference_id);
        $this->artisan('charlot:link-project-brands')->assertSuccessful();
        $this->assertSame(15, Reference::count());
        $this->get('/en/references/')->assertOk()->assertSee('Balmy Beach Resort Kemer');
        $this->get('/tr/projeler/balmy-beach-resort-kemer/')->assertOk()->assertSee('Balmy Beach Resort Kemer');
    }
}
