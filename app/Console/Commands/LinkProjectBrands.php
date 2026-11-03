<?php

namespace App\Console\Commands;

use A17\Twill\Models\Media;
use App\Models\Project;
use App\Models\Reference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LinkProjectBrands extends Command
{
    protected $signature = 'charlot:link-project-brands';

    protected $description = 'Link verified portfolio brands, preserving existing manual selections';

    public function handle(): int
    {
        $source = '/assets/references/balmy.png';
        if (! Reference::withTrashed()->where('import_key', $source)->exists()) {
            $file = base_path('dist'.$source);
            [$width, $height] = getimagesize($file);
            $uuid = 'reference-'.hash('sha256', $source).'.png';
            Storage::disk(config('twill.media_library.disk'))->put($uuid, file_get_contents($file));
            DB::transaction(function () use ($source, $uuid, $width, $height) {
                $brand = Reference::create(['published' => true, 'import_key' => $source, 'position' => (int) Reference::max('position') + 1, 'website_url' => 'https://www.balmybeachresort.com/']);
                foreach (['en', 'tr'] as $locale) {
                    $brand->translations()->create(['locale' => $locale, 'active' => true, 'title' => 'Balmy Beach Resort Kemer']);
                }
                $media = Media::firstOrCreate(['uuid' => $uuid], ['filename' => 'balmy.png', 'width' => $width, 'height' => $height]);
                $brand->medias()->attach($media->id, ['role' => 'logo', 'crop' => 'default', 'ratio' => 'default', 'position' => 0, 'crop_x' => 0, 'crop_y' => 0, 'crop_w' => $width, 'crop_h' => $height, 'metadatas' => json_encode(['altText' => 'Balmy Beach Resort Kemer', 'caption' => null, 'video' => null])]);
            });
        }
        if ($brand = Reference::where('import_key', $source)->first()) {
            foreach ($brand->medias as $media) {
                $metadata = json_decode($media->pivot->metadatas ?: '{}', true) ?: [];
                $brand->medias()->updateExistingPivot($media->id, ['metadatas' => json_encode(array_replace(['altText' => $brand->title, 'caption' => null, 'video' => null], $metadata))]);
            }
            $brand->touch();
        }
        $mapping = json_decode(file_get_contents(base_path('data/project-brands.json')), true, 512, JSON_THROW_ON_ERROR);
        $linked = 0;
        foreach ($mapping as $identity => $brandKey) {
            $project = Project::where('import_key', $identity)->first();
            $brand = Reference::where('import_key', $brandKey)->first();
            if ($project && $brand && ! $project->reference_id) {
                $project->update(['reference_id' => $brand->id]);
                $linked++;
            }
        }
        $this->info("Linked $linked project brands. Existing selections preserved.");
        foreach (Project::whereNull('reference_id')->get() as $project) {
            $this->line('No verified brand: '.$project->title);
        }

        return self::SUCCESS;
    }
}
