<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Models\Project;
use App\Services\Admin\PathGuard;
use App\Services\PortfolioLocations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedMardanPalace extends Command
{
    protected $signature = 'charlot:seed-mardan-palace';

    protected $description = 'Add Mardan Palace Antalya and its five photographs once, preserving CMS edits';

    public function handle(): int
    {
        $identity = '/en/projects/mardan-palace-antalya/';
        if (Project::withTrashed()->where('import_key', $identity)->exists()) {
            $this->info('Mardan Palace Antalya already exists; CMS edits and publication state preserved.');

            return self::SUCCESS;
        }
        $country = Destination::where('import_key', '/en/destinations/turkey/')->first();
        $city = Destination::where('import_key', '/en/destinations/turkey/antalya/')->first();
        if (! $country || ! $city) {
            $this->error('Türkiye and Antalya destinations must exist before seeding Mardan Palace.');

            return self::FAILURE;
        }
        $paths = ['en' => $identity, 'tr' => '/tr/projeler/mardan-palace-antalya/'];
        foreach ($paths as $path) {
            if (app(PathGuard::class)->isTaken($path, Project::class)) {
                $this->error('Mardan Palace project path is already in use: '.$path);

                return self::FAILURE;
            }
        }
        $source = json_decode(file_get_contents(base_path('data/mardan-palace-antalya.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($source['images'] as $image) {
            $path = base_path('dist'.$image['src']);
            if (! is_file($path) || ! hash_equals($image['asset_sha256'], hash_file('sha256', $path))) {
                $this->error('Mardan Palace photograph missing or changed: '.$image['src']);

                return self::FAILURE;
            }
        }
        DB::transaction(function () use ($country, $city, $source, $identity, $paths) {
            $project = Project::create([
                'import_key' => $identity, 'published' => true, 'published_at' => now(),
                'position' => (int) Project::max('position') + 1,
                'country' => 'Türkiye', 'city' => 'Antalya',
                'source_data' => $source + ['mardan_palace_seed_version' => 1, 'location_picker_initialized' => true],
            ]);
            $copy = [
                'en' => [
                    'seo_title' => 'Mardan Palace Antalya Photography | Charlot Crea',
                    'description' => 'Explore five Mardan Palace Antalya photographs by Charlot Crea: the grand lobby, poolside terrace, lounge and bar, with architecture and hospitality lifestyle.',
                    'intro' => 'A five-photograph collection from Mardan Palace in Antalya, Türkiye. Grand interiors, the poolside terrace and hospitality moments through the Charlot Crea lens.',
                    'category' => 'Hotel photography',
                ],
                'tr' => [
                    'seo_title' => 'Mardan Palace Antalya Fotoğraf Çekimi | Charlot Crea',
                    'description' => 'Charlot Crea’nın Mardan Palace Antalya fotoğraf seçkisini keşfedin: görkemli lobi, havuz terası, salon ve barda mimari ve otel yaşam tarzı çekimlerinden beş kare.',
                    'intro' => 'Antalya’daki Mardan Palace’tan beş fotoğraflık bir seçki. Görkemli iç mekânlar, havuz terası ve otel yaşamından anlar, Charlot Crea’nın bakış açısıyla.',
                    'category' => 'Otel fotoğrafçılığı',
                ],
            ];
            foreach ($copy as $locale => $fields) {
                $project->translations()->create($fields + [
                    'locale' => $locale, 'active' => true, 'path' => $paths[$locale],
                    'title' => 'Mardan Palace Antalya', 'heading' => 'Mardan Palace Antalya',
                ]);
            }
            $project->countries()->syncWithoutDetaching([$country->id => ['position' => 0]]);
            $project->destinations()->syncWithoutDetaching([$city->id => ['position' => 0]]);
            app(PortfolioLocations::class)->syncLabels($project);
        });
        $this->info('Mardan Palace Antalya seeded with five photographs, Turkish/English copy, Türkiye and Antalya links.');

        return self::SUCCESS;
    }
}
