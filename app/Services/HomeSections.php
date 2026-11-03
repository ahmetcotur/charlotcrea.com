<?php

namespace App\Services;

use App\Models\Destination;
use App\Models\Film;
use App\Models\Page;
use App\Models\Project;
use App\Models\Reference;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/** Native homepage section settings; no layout HTML is parsed during rendering. */
class HomeSections
{
    public const HOMES = ['/en/', '/en/home-2/', '/en/home-3/'];

    public const SOURCES = ['projects' => Project::class, 'showreel' => Film::class, 'destinations' => Destination::class, 'references' => Reference::class];

    public function supports(Page $page): bool
    {
        return in_array($page->import_key, self::HOMES);
    }

    public function editor(Page $page): array
    {
        if (! $this->supports($page)) {
            return [];
        }
        $sections = $page->source_data['native_home_sections'] ?? app(NativePageData::class)->defaults($page)['sections'] ?? [];
        $saved = $page->source_data['home_sections'] ?? [];
        foreach ($sections as $key => &$section) {
            $config = $saved[$key] ?? [];
            // Hero title and intro remain live translations unless explicitly overridden in section settings.
            if ($key === 'hero') {
                foreach (array_keys($section['texts'] ?? []) as $locale) {
                    foreach ($section['texts'][$locale] as &$text) {
                        if ($text['tag'] === 'h1' && filled($page->translate($locale)?->heading)) {
                            $text['value'] = $page->translate($locale)->heading;
                        }
                        if ($text['tag'] === 'p' && $page->translate($locale)?->intro !== null) {
                            $text['value'] = preg_replace('/(?<=[.!?])(?:Discover our work|Çalışmalarımızı keşfedin|Watch the full film|Filmin tamamını izleyin)$/u', '', $page->translate($locale)->intro);
                        }
                    }
                    unset($text);
                }
            }
            foreach (['visible', 'position', 'mode', 'limit', 'items', 'featured_id'] as $field) {
                if (array_key_exists($field, $config)) {
                    $section[$field] = $config[$field];
                }
            }
            foreach (array_keys($section['texts'] ?? []) as $locale) {
                foreach ($section['texts'][$locale] as $index => $text) {
                    if (array_key_exists($index, $config['texts'][$locale] ?? [])) {
                        $section['texts'][$locale][$index]['value'] = $config['texts'][$locale][$index];
                    }
                }
            }
        }
        unset($section);
        uasort($sections, fn ($a, $b) => $a['position'] <=> $b['position']);

        return $sections;
    }

    public function visible(Page $page): array
    {
        return array_filter($this->editor($page), fn ($section) => (bool) $section['visible']);
    }

    public function choices(): array
    {
        $choices = [];
        foreach (self::SOURCES as $key => $class) {
            $choices[$key] = $class::with(['translations', 'medias'])->orderBy('position')->orderBy('id')->get();
        }

        return $choices;
    }

    public function items(Page $page, string $key, string $locale): Collection
    {
        $class = self::SOURCES[$key] ?? null;
        if (! $class) {
            return collect();
        }
        $config = $this->editor($page)[$key] ?? [];
        $query = $class === Reference::class ? $class::published()->whereHas('translations', fn ($q) => $q->where('locale', $locale)->where('active', true)) : $class::forPublic($locale);
        if ($key === 'destinations' && ($config['mode'] ?? 'automatic') !== 'manual') {
            $query->whereNull('parent_id');
        }
        $items = $query->with(['translations', 'medias'])->orderBy('position')->orderBy('id')->get();
        if (($config['mode'] ?? 'automatic') === 'manual') {
            $ids = array_map('intval', $config['items'] ?? []);
            $items = $items->whereIn('id', $ids)->sortBy(fn ($item) => array_search($item->id, $ids))->values();
        }

        return $items->take((int) ($config['limit'] ?? ($key === 'references' ? 12 : 4)));
    }

    public function featuredFilm(Page $page, string $locale): ?Film
    {
        $config = $this->editor($page)['showreel'] ?? [];

        return Film::forPublic($locale)->with(['translations', 'medias'])
            ->when(! empty($config['featured_id']), fn ($q) => $q->whereKey($config['featured_id']))
            ->when(empty($config['featured_id']), fn ($q) => $q->where('import_key', '/en/films/charlot-crea-showreel/'))->first();
    }

    public function validated(Request $request, Page $page): ?array
    {
        if (! $this->supports($page) || ! $request->has('home_sections')) {
            return null;
        }
        $catalog = $this->editor($page);
        $rules = ['home_sections' => ['array:'.implode(',', array_keys($catalog))]];
        foreach ($catalog as $key => $section) {
            $base = 'home_sections.'.$key;
            $rules[$base] = ['sometimes', 'array:visible,position,mode,limit,items,featured_id,texts'];
            $rules[$base.'.visible'] = ['required_with:'.$base, 'boolean'];
            $rules[$base.'.position'] = ['required_with:'.$base, 'integer', 'min:1', 'max:20'];
            $rules[$base.'.texts'] = ['sometimes', 'array:tr,en'];
            foreach ($section['texts'] ?? [] as $locale => $texts) {
                $rules[$base.'.texts.'.$locale] = ['sometimes', 'array:'.implode(',', array_keys($texts))];
                foreach ($texts as $index => $text) {
                    $rules[$base.'.texts.'.$locale.'.'.$index] = ['nullable', 'string', 'max:5000'];
                }
            }
            if ($class = self::SOURCES[$key] ?? null) {
                $rules[$base.'.mode'] = ['required_with:'.$base, 'in:automatic,manual'];
                $rules[$base.'.limit'] = ['required_with:'.$base, 'integer', 'min:1', 'max:24'];
                $rules[$base.'.items'] = ['sometimes', 'array', 'max:24'];
                $rules[$base.'.items.*'] = ['integer', 'distinct', Rule::exists((new $class)->getTable(), 'id')->whereNull('deleted_at')];
                if ($key === 'showreel') {
                    $rules[$base.'.featured_id'] = ['nullable', 'integer', Rule::exists('films', 'id')->whereNull('deleted_at')];
                }
            }
        }
        $data = $request->validate($rules)['home_sections'];
        foreach ($data as &$section) {
            $section['visible'] = (bool) $section['visible'];
            foreach ($section['texts'] ?? [] as $locale => $texts) {
                foreach ($texts as $index => $text) {
                    $section['texts'][$locale][$index] = strip_tags((string) $text);
                }
            }
        }
        unset($section);

        return array_replace($page->source_data['home_sections'] ?? [], $data);
    }
}
