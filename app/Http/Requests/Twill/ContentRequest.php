<?php

namespace App\Http\Requests\Twill;

use A17\Twill\Http\Requests\Admin\Request;
use App\Models\Destination;
use App\Models\LegacyRedirect;
use App\Services\ContentTypes;

abstract class ContentRequest extends Request
{
    private function uniquePath(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $name = str_replace('Request', '', class_basename($this));
            $currentClass = 'App\\Models\\'.$name;
            $currentId = ($this->route(strtolower($name)) ?? $this->route('id'));
            foreach (ContentTypes::MODELS as $class) {
                $query = $class::withTrashed()->whereHas('translations', fn ($q) => $q->where('path', $value));
                if ($class === $currentClass && $currentId) {
                    $query->where('id', '!=', $currentId);
                }
                if ($query->exists()) {
                    $fail('Bu adres başka bir içerik tarafından kullanılıyor.');

                    return;
                }
            }
            if (LegacyRedirect::where('source', $value)->exists()) {
                $fail('Bu adres eski bir yönlendirmeye ayrılmış.');
            }
        };
    }

    private function validParent(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $name = str_replace('Request', '', class_basename($this));
            if (! in_array($name, ['Page', 'Destination'])) {
                return;
            }
            $class = 'App\\Models\\'.$name;
            $current = $class::find(($this->route(strtolower($name)) ?? $this->route('id')));
            $parentId = $value[0]['id'] ?? null;
            if (! $parentId) {
                return;
            }
            $parent = $class::find($parentId);
            if (! $parent || ($current && ($parent->id === $current->id || $parent->isDescendantOf($current)))) {
                $fail('Üst kayıt kendisi veya altındaki bir kayıt olamaz.');
            }
        };
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $name = str_replace('Request', '', class_basename($this));
            if (! in_array($name, ['Project', 'Film'])) {
                return;
            }
            if (! $this->has('browsers.countries') && ! $this->has('browsers.locations')) {
                return;
            }
            $class = 'App\\Models\\'.$name;
            $model = $class::find(($this->route(strtolower($name)) ?? $this->route('id')));
            $countryIds = $this->has('browsers.countries') ? collect($this->input('browsers.countries'))->pluck('id')->unique()->all() : ($model?->countries()->pluck('destinations.id')->all() ?? []);
            $locationIds = $this->has('browsers.locations') ? collect($this->input('browsers.locations'))->pluck('id')->unique()->all() : ($model?->locations()->pluck('destinations.id')->all() ?? []);
            $countries = Destination::whereNull('parent_id')->whereIn('id', $countryIds)->get();
            if ($countries->count() !== count($countryIds)) {
                $validator->errors()->add('browsers.countries', 'Ülke alanından yalnızca ülkeler seçilebilir.');
            }
            $locations = Destination::with('ancestors')->whereNotNull('parent_id')->whereIn('id', $locationIds)->get();
            if ($locations->count() !== count($locationIds)) {
                $validator->errors()->add('browsers.locations', 'Çekim noktası geçersiz.');
            }
            foreach ($locations as $location) {
                $root = $location->ancestors->sortBy('_lft')->first();
                if (! $root || ! $countries->contains('id', $root->id)) {
                    $validator->errors()->add('browsers.locations', 'Çekim noktası seçilen ülkeye ait olmalı. Önce ülkeyi, sonra o ülkenin noktasını seçin.');
                    break;
                }
            }
        });
    }

    public function rulesForCreate(): array
    {
        return ['title.en' => ['required', 'string', 'max:200']];
    }

    public function rulesForUpdate(): array
    {
        return [
            'browsers.countries' => ['sometimes', 'array', 'max:20'],
            'browsers.countries.*.id' => ['required', 'integer'],
            'browsers.locations' => ['sometimes', 'array', 'max:100'],
            'browsers.locations.*.id' => ['required', 'integer'],
            'browsers.parent' => ['sometimes', 'array', 'max:1', $this->validParent()],
            'title.en' => ['required', 'string', 'max:200'],
            'path.en' => ['nullable', 'string', 'max:255', 'regex:~^/en/(?:[a-z0-9-]+/)*$~', $this->uniquePath()],
            'path.tr' => ['nullable', 'string', 'max:255', 'regex:~^/tr/(?:[a-z0-9-]+/)*$~', $this->uniquePath()],
            'video_url' => ['nullable', 'string', 'max:1000', 'regex:~^(?:/assets/[^\s]+\.mp4|https://[^\s]+)$~'],
            'published_at' => ['nullable', 'date'],
            'country' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'seo_title.*' => ['nullable', 'string', 'max:255'],
            'description.*' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
