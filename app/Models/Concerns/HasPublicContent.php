<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasPublicContent
{
    public function scopeForPublic(Builder $query, string $locale): Builder
    {
        $query->published()->whereHas('translations', fn ($q) => $q->where('locale', $locale)->where('active', true)->whereNotNull('path')->where('path', '!=', ''));
        if (method_exists($this, 'ancestors')) {
            $query->whereDoesntHave('ancestors', fn ($q) => $q->where(function ($q) use ($locale) {
                $q->where('published', false)->orWhereDoesntHave('translations', fn ($t) => $t->where('locale', $locale)->where('active', true)->whereNotNull('path')->where('path', '!=', ''));
            }));
        }

        return $query;
    }

    public function publicEagerLoads(): array
    {
        $foreignKey = $this->getForeignKey();
        $columns = array_merge(['id', $foreignKey, 'locale', 'active'], $this->translatedAttributes);
        $relations = ['translations' => fn ($q) => $q->select($columns), 'medias'];
        if (method_exists($this, 'ancestors')) {
            $relations['ancestors.translations'] = fn ($q) => $q->select($columns);
        }

        return $relations;
    }

    public function publicPath(?string $locale = null): ?string
    {
        $translation = $this->translate($locale ?? app()->getLocale());
        if (! $this->published || ! $translation?->active || ! $translation->path) {
            return null;
        }
        if (method_exists($this, 'ancestors')) {
            foreach ($this->ancestors as $ancestor) {
                $ancestorTranslation = $ancestor->translate($locale ?? app()->getLocale());
                if (! $ancestor->published || ! $ancestorTranslation?->active || ! $ancestorTranslation->path) {
                    return null;
                }
            }
        }

        return $translation->path;
    }

    public function coverImage(int $width = 800): ?array
    {
        $image = $this->imageAsArray('cover', 'default', ['w' => $width]);
        if ($image) {
            return $image;
        }
        if ($this->source_data['media_imported_cover'] ?? false) {
            return null;
        }
        $src = $this->source_data['cover_small'] ?? $this->source_data['poster'] ?? $this->source_data['image'] ?? null;

        return $src ? ['src' => $src, 'alt' => $this->title, 'width' => null, 'height' => null] : null;
    }

    public function galleryImages(int $width = 1800): array
    {
        $images = $this->imagesAsArrays('gallery', 'default', ['w' => $width]);
        if ($images || ($this->source_data['media_imported_gallery'] ?? false)) {
            return $images;
        }

        return array_map(function ($image) {
            foreach (['alt', 'caption'] as $field) {
                if (is_array($image[$field] ?? null)) {
                    $image[$field] = $image[$field][app()->getLocale()] ?? '';
                }
            }

            return $image + ['alt' => $this->title];
        }, $this->source_data['images'] ?? []);
    }
}
