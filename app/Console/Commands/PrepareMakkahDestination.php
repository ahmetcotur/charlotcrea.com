<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Models\Project;
use App\Services\Admin\PathGuard;
use App\Services\PortfolioLocations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PrepareMakkahDestination extends Command
{
    protected $signature = 'charlot:prepare-makkah';

    protected $description = 'Initialize the bilingual Makkah destination and Fairmont project link without replacing edits';

    public function handle(): int
    {
        $country = Destination::where('import_key', '/en/destinations/saudi-arabia/')->first();
        $project = Project::where('import_key', '/en/projects/fairmont-makkah/')->first();
        if (! $country || ! $project) {
            $this->info('Makkah source country/project not present; skipped.');

            return self::SUCCESS;
        }
        DB::transaction(function () use ($country, $project) {
            $identity = '/en/destinations/saudi-arabia/makkah/';
            $paths = ['en' => $identity, 'tr' => '/tr/destinasyonlar/suudi-arabistan/mekke/'];
            $city = Destination::withTrashed()->where('import_key', $identity)->first()
                ?? Destination::withTrashed()->where('parent_id', $country->id)->whereHas('translations', fn ($q) => $q->whereIn('title', ['Makkah', 'Mecca', 'Mekke']))->first();
            if ($city?->trashed()) {
                return;
            }
            if (! $city) {
                foreach ($paths as $path) {
                    if (app(PathGuard::class)->isTaken($path, Destination::class)) {
                        throw new \RuntimeException('Makkah destination path is already in use.');
                    }
                }
                $city = Destination::create(['parent_id' => $country->id, 'import_key' => $identity, 'published' => true, 'country' => 'Saudi Arabia', 'city' => 'Makkah', 'position' => (int) Destination::where('parent_id', $country->id)->max('position') + 1, 'source_data' => ['cover' => $project->source_data['cover'] ?? '/assets/fairmont-makkah-cover.jpg']]);
                foreach (['en' => 'Makkah', 'tr' => 'Mekke'] as $locale => $name) {
                    $city->translations()->create(['locale' => $locale, 'active' => true, 'path' => $paths[$locale], 'title' => $name, 'heading' => $name, 'seo_title' => $name.' Photography & Film | Charlot Crea', 'description' => $locale === 'tr' ? 'Mekke’deki Charlot Crea fotoğraf ve film çalışmalarını, Fairmont Makkah projesini keşfedin.' : 'Explore Charlot Crea photography and films in Makkah, including Fairmont Makkah.']);
                }
            }
            if (blank($city->import_key)) {
                $city->import_key = $identity;
                $city->save();
            }
            if (! ($city->source_data['makkah_project_link_initialized'] ?? false)) {
                $project->countries()->syncWithoutDetaching([$country->id]);
                $project->destinations()->syncWithoutDetaching([$city->id]);
                app(PortfolioLocations::class)->syncLabels($project);
                $city->source_data = array_merge($city->source_data ?? [], ['makkah_project_link_initialized' => true]);
                $city->save();
            }
        });
        $this->info('Makkah destination and Fairmont project initialized; existing edits preserved.');

        return self::SUCCESS;
    }
}
