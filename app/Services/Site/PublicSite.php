<?php

namespace App\Services\Site;

use App\Models\Destination;
use App\Models\Film;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Support\Collection;

class PublicSite
{
    private array $catalogs = [];

    public function catalog(string $class): Collection
    {
        $locale = app()->getLocale();

        return $this->catalogs[$class.':'.$locale] ??= $class::forPublic($locale)->with((new $class)->publicEagerLoads())->orderBy('position')->orderBy('id')->get();
    }

    public function route(string $identity, ?string $locale = null): ?string
    {
        return $this->catalog(Page::class)->firstWhere('import_key', $identity)?->publicPath($locale);
    }

    public function url(string $path): string
    {
        return preg_match('~^https?://~', $path) ? $path : rtrim(config('app.url'), '/').'/'.ltrim($path, '/');
    }

    public function videoUrl(?string $url): ?string
    {
        // Refresh only first-party media so old compressed CDN objects are not reused.
        if (! $url || ! str_starts_with($url, '/assets/films/')) {
            return $url;
        }
        [$path, $fragment] = array_pad(explode('#', $url, 2), 2, null);

        return $path.(str_contains($path, '?') ? '&' : '?').'delivery=4'.($fragment !== null ? '#'.$fragment : '');
    }

    public function countryName(?string $country): string
    {
        return __($country ?: 'International');
    }

    public function visibleRelated($relation): Collection
    {
        return $relation->forPublic(app()->getLocale())->with($relation->getRelated()->publicEagerLoads())->get();
    }

    public function data($model, string $locale): array
    {
        if ($model instanceof Project) {
            $model->loadMissing(['reference.translations', 'reference.medias', 'countries.translations']);
        }
        $translation = $model->translate($locale);
        $kind = match (true) {
            $model instanceof Project => 'project', $model instanceof Film => 'film',
            $model instanceof Destination => 'destination', $model instanceof Post => 'post',
            default => match ($model->import_key) {
                '/en/', '/en/home-2/', '/en/home-3/' => 'home', '/en/about/' => 'about', '/en/services/' => 'services',
                '/en/photography/' => 'photography', '/en/film/' => 'films',
                '/en/contact/' => 'contact', '/en/request-a-quote/' => 'quote-request', '/en/blog/' => 'blog', '/en/references/' => 'references', '/en/destinations/' => 'destinations', default => 'page',
            },
        };
        $projects = $films = $posts = $destinations = collect();
        $children = $relatedPosts = collect();
        $parent = $photoProject = null;
        if ($kind === 'photography') {
            $projects = $this->catalog(Project::class);
        }
        if ($kind === 'films') {
            $films = $this->catalog(Film::class);
        }
        if ($kind === 'blog') {
            $posts = $this->catalog(Post::class);
        }
        if ($kind === 'destinations') {
            $destinations = $this->catalog(Destination::class)->whereNull('parent_id');
        }
        if ($kind === 'destination') {
            // Work attached to a city also belongs to its visible region and country.
            $ids = $this->catalog(Destination::class)->filter(fn ($destination) => $destination->id === $model->id || ($destination->_lft > $model->_lft && $destination->_rgt < $model->_rgt))->pluck('id');
            $projects = Project::forPublic($locale)->with((new Project)->publicEagerLoads())->where(fn ($q) => $q->whereHas('destinations', fn ($d) => $d->whereIn('destinations.id', $ids))->orWhereHas('countries', fn ($d) => $d->where('destinations.id', $model->id)))->orderBy('position')->get();
            $films = Film::forPublic($locale)->with((new Film)->publicEagerLoads())->where(fn ($q) => $q->whereHas('destinations', fn ($d) => $d->whereIn('destinations.id', $ids))->orWhereHas('countries', fn ($d) => $d->where('destinations.id', $model->id)))->orderBy('position')->get();
            $children = $this->visibleRelated($model->children());
            $parent = $model->parent?->publicPath($locale) ? $model->parent : null;
        }
        if (in_array($kind, ['project', 'film'])) {
            $destinations = $this->visibleRelated($model->countries())->concat($this->visibleRelated($model->destinations()))->unique('id');
            if ($kind === 'project') {
                $films = $this->visibleRelated($model->films());
            } else {
                $photoProject = $model->project?->publicPath($locale) ? $model->project : null;
            }
        }
        if ($kind === 'post') {
            $relatedPosts = $this->catalog(Post::class)->where('id', '!=', $model->id)->take(2);
        }
        $crumbs = [[__('Home'), $this->route('/en/', $locale)]];
        $listing = match ($kind) {
            'project' => [__('Photography'), '/en/photography/'], 'film' => [__('Film'), '/en/film/'],
            'post' => ['Blog', '/en/blog/'], 'destination' => [__('Destinations'), '/en/destinations/'], default => null,
        };
        if ($listing && $this->route($listing[1], $locale)) {
            $crumbs[] = [$listing[0], $this->route($listing[1], $locale)];
        }
        if (method_exists($model, 'ancestors')) {
            foreach ($model->ancestors()->with('translations')->defaultOrder()->get() as $ancestor) {
                if ($ancestor->publicPath($locale)) {
                    $crumbs[] = [$ancestor->title, $ancestor->publicPath($locale)];
                }
            }
        }
        $crumbs[] = [$translation->title, $translation->path];

        return compact('model', 'translation', 'locale', 'kind', 'projects', 'films', 'posts', 'destinations', 'children', 'relatedPosts', 'parent', 'photoProject', 'crumbs') + ['site' => $this];
    }
}
