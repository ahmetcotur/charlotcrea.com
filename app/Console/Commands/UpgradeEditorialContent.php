<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Models\Film;
use App\Models\Project;
use App\Services\ContentTypes;
use App\Services\PortfolioLocations;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpgradeEditorialContent extends Command
{
    protected $signature = 'charlot:upgrade-content';

    protected $description = 'Initialize editorial fields and relationships once, preserving subsequent CMS edits';

    public function handle(): int
    {
        DB::transaction(function () {
            $destinations = Destination::with('translations')->get();
            $countries = ['turkey' => 'Türkiye', 'uae' => 'UAE', 'egypt' => 'Egypt', 'saudi-arabia' => 'Saudi Arabia'];
            $projects = Project::with('translations')->get();
            $films = Film::with('translations')->get();
            foreach (ContentTypes::MODELS as $class) {
                foreach ($class::with('translations')->get() as $model) {
                    if ($model->relations_imported) {
                        continue;
                    }
                    $source = $model->source_data ?? [];
                    $date = $source['published'] ?? null;
                    if ($date) {
                        $model->published_at = Carbon::parse($date);
                    }
                    foreach (['en', 'tr'] as $locale) {
                        $translation = $model->translate($locale);
                        if (! $translation) {
                            continue;
                        }
                        $data = $source[$locale] ?? [];
                        $translation->category ??= $data['category'] ?? null;
                        $translation->background ??= $data['background'] ?? mb_strtoupper($translation->title);
                        $translation->story_heading ??= $data['heading'] ?? null;
                        $translation->story_intro ??= $data['intro'] ?? null;
                        $translation->faq_body ??= implode('', array_map(fn ($pair) => '<h3>'.e($pair[0]).'</h3><p>'.e($pair[1]).'</p>', $data['faq'] ?? []));
                    }
                    if ($model instanceof Destination) {
                        $parts = explode('/', trim($model->import_key, '/'));
                        $countrySlug = $parts[2] ?? '';
                        $model->country = $countries[$countrySlug] ?? $model->country;
                        $cityKey = $source['key'] ?? ($parts[3] ?? null ? ($source['cities'][0] ?? $model->translate('en')->title) : null);
                        if ($cityKey) {
                            $parent = $destinations->first(fn ($d) => ($source['parent'] ?? null) && ($d->source_data['key'] ?? null) === $source['parent'])
                                ?? $destinations->firstWhere('import_key', '/en/destinations/'.$countrySlug.'/');
                            $model->parent_id = $parent?->id;
                            $model->city = $cityKey;
                        }
                        $cities = $source['includes'] ?? ($cityKey ? [$cityKey] : null);
                        foreach (['projects' => $projects, 'films' => $films] as $relation => $items) {
                            $selected = $items->filter(function ($item) use ($model, $cities, $source, $relation) {
                                if ($relation === 'projects' && isset($source['projects'])) {
                                    return in_array($item->source_data['slug'] ?? null, $source['projects']);
                                }
                                if ($item->country !== $model->country) {
                                    return false;
                                }

                                return ! $cities || count(array_intersect($cities, $item->source_data['cities'] ?? [$item->city])) > 0;
                            });
                            $model->$relation()->sync($selected->values()->mapWithKeys(fn ($item, $i) => [$item->id => ['position' => $i]])->all());
                        }
                    }
                    if ($model instanceof Film && ($source['photo_slug'] ?? null)) {
                        $model->project_id = $projects->firstWhere('import_key', '/en/projects/'.$source['photo_slug'].'/')?->id;
                    }
                    $model->relations_imported = true;
                    $model->save();
                }
            }
        });
        foreach ([Project::class, Film::class] as $class) {
            foreach ($class::all() as $model) {
                app(PortfolioLocations::class)->initialize($model);
            }
        }
        $this->info('Editorial fields and content relationships are ready. Existing edits were preserved.');

        return self::SUCCESS;
    }
}
