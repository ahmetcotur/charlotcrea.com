<?php

namespace App\Console\Commands;

use App\Models\Destination;
use App\Models\Film;
use App\Models\LegacyRedirect;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Services\EditorialHeading;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportLegacyContent extends Command
{
    protected $signature = 'charlot:import {--overwrite : Replace existing CMS edits with the legacy source}';

    protected $description = 'Import the bilingual static website and original catalog into Twill';

    public function handle(): int
    {
        $routes = $this->json('data/language-routes.json');
        $portfolio = $this->json('data/portfolio.json');
        $projects = collect($portfolio['projects'])->keyBy('slug');
        $films = collect($portfolio['films'])->keyBy('slug');
        $posts = collect($this->json('data/blog.json'))->keyBy('en.slug');
        $destinations = collect($this->json('data/destinations.json'))->keyBy('slug');
        $cities = collect($this->json('data/portfolio-cities.json'))->keyBy(fn ($city) => $city['country_slug'].'/'.$city['slug']);
        foreach ($routes as $en => $tr) {
            foreach ([$en, $tr] as $path) {
                if (! is_file(base_path('dist/'.trim($path, '/').'/index.html'))) {
                    $this->error("Missing source page: $path");

                    return self::FAILURE;
                }
            }
        }
        $count = 0;
        DB::transaction(function () use ($routes, $projects, $films, $posts, $destinations, $cities, &$count) {
            foreach ($routes as $en => $tr) {
                $class = Page::class;
                $source = [];
                if (preg_match('~^/en/projects/([^/]+)/$~', $en, $m)) {
                    $class = Project::class;
                    $source = $projects->get($m[1], []);
                } elseif (preg_match('~^/en/films/([^/]+)/$~', $en, $m)) {
                    $class = Film::class;
                    $source = $films->get($m[1], []);
                } elseif (preg_match('~^/en/blog/([^/]+)/$~', $en, $m)) {
                    $class = Post::class;
                    $source = $posts->get($m[1], []);
                } elseif (preg_match('~^/en/destinations/(.+)/$~', $en, $m)) {
                    $class = Destination::class;
                    $source = $destinations->get($m[1], $cities->get($m[1], []));
                }
                $model = $class::withTrashed()->where('import_key', $en)->first();
                if ($model && ! $this->option('overwrite')) {
                    foreach (['en' => $en, 'tr' => $tr] as $lang => $path) {
                        $translation = $model->translate($lang);
                        if ($translation && $class === Page::class && in_array($en, ['/en/photography/', '/en/film/', '/en/blog/', '/en/destinations/', '/en/references/'])) {
                            [$plain, $formatted] = EditorialHeading::read($translation->legacy_html ?: file_get_contents(base_path('dist/'.trim($path, '/').'/index.html')));
                            if ($translation->heading === $plain && $plain !== $formatted) {
                                $translation->heading = $formatted;
                                $translation->save();
                            }
                        }
                        if ($translation && ! $translation->legacy_html) {
                            $translation->legacy_html = file_get_contents(base_path('dist/'.trim($path, '/').'/index.html'));
                            $translation->save();
                        }
                    }

                    continue;
                }
                $model ??= new $class;
                if ($model->trashed()) {
                    $model->restore();
                }
                $model->fill([
                    'import_key' => $en, 'published' => true, 'position' => $count + 1,
                    'country' => $source['country'] ?? null, 'city' => $source['city'] ?? null,
                    'video_url' => $source['file'] ?? $source['embed_url'] ?? null,
                    'source_data' => $source,
                ]);
                foreach (['en' => $en, 'tr' => $tr] as $lang => $path) {
                    $html = file_get_contents(base_path('dist/'.trim($path, '/').'/index.html'));
                    $dom = new DOMDocument;
                    $before = libxml_use_internal_errors(true);
                    $dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
                    libxml_clear_errors();
                    libxml_use_internal_errors($before);
                    $xp = new DOMXPath($dom);
                    $text = fn ($query) => trim($xp->query($query)->item(0)?->textContent ?? '');
                    $translation = $model->translateOrNew($lang);
                    $translation->fill([
                        'title' => $source[$lang]['title'] ?? $source[$lang]['name'] ?? $source['name'] ?? $text('//h1'),
                        'heading' => ($class === Page::class && in_array($en, ['/en/photography/', '/en/film/', '/en/blog/', '/en/destinations/', '/en/references/'])) ? EditorialHeading::read($html)[1] : $text('//h1'),
                        'seo_title' => $text('//title'),
                        'description' => $xp->query('//meta[@name="description"]')->item(0)?->getAttribute('content') ?? '',
                        'path' => $path, 'active' => true,
                        'intro' => $text('(//main//h1/following-sibling::p | //main//h1/parent::div/following-sibling::p)[1]'),
                        'body' => $this->sections($source[$lang]['sections'] ?? []),

                    ]);
                    $translation->legacy_html = $html;
                }
                $model->save();
                $count++;
            }
        });
        foreach ($this->json('dist/redirects.json') as $source => $target) {
            LegacyRedirect::firstOrCreate(['source' => $source], ['target' => $target]);
        }
        $this->info("Imported $count bilingual records. Existing edits are preserved without --overwrite.");

        $this->call('charlot:upgrade-content');
        $this->call('charlot:prepare-page-editor');
        $this->call('charlot:import-references');
        $this->call('charlot:seed-settings');
        $this->call('charlot:prepare-native-pages');

        return self::SUCCESS;
    }

    private function json(string $file): array
    {
        return json_decode(file_get_contents(base_path($file)), true, flags: JSON_THROW_ON_ERROR);
    }

    private function sections(array $sections): string
    {
        return implode('', array_map(fn ($section) => '<h2>'.e($section[0]).'</h2><p>'.e($section[1]).'</p>', $sections));
    }
}
