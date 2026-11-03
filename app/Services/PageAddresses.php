<?php

namespace App\Services;

use App\Models\LegacyRedirect;
use App\Models\Page;
use Illuminate\Support\Str;

class PageAddresses
{
    public function prepare(Page $page, array $fields): array
    {
        $parentId = array_key_exists('parent_id', $fields) ? ($fields['parent_id'] ?: null) : $page->parent_id;
        $parent = $parentId ? Page::find($parentId) : null;
        $fallbackTitle = trim((string) ($fields['title']['tr'] ?? '')) ?: (trim((string) ($fields['title']['en'] ?? '')) ?: ($page->title ?: ''));
        foreach (['en', 'tr'] as $locale) {
            $existing = $page->translate($locale);
            $title = trim((string) ($fields['title'][$locale] ?? '')) ?: ($existing?->title ?: $fallbackTitle);
            $fields['title'][$locale] = $title;
            $path = trim((string) ($fields['path'][$locale] ?? '')) ?: $existing?->path;
            if (! $path && $title) {
                $prefix = $parent?->translate($locale)?->path ?: '/'.$locale.'/';
                $slug = Str::slug($title, '-', $locale) ?: 'page';
                for ($suffix = 1; ; $suffix++) {
                    $candidate = rtrim($prefix, '/').'/'.$slug.($suffix === 1 ? '' : '-'.$suffix).'/';
                    if ($this->available($candidate, $page)) {
                        $path = $candidate;
                        break;
                    }
                }
            }
            $fields['path'][$locale] = $path;
        }

        return $fields;
    }

    private function available(string $path, Page $page): bool
    {
        if (LegacyRedirect::where('source', $path)->exists()) {
            return false;
        }
        foreach (ContentTypes::MODELS as $class) {
            $query = $class::withTrashed()->whereHas('translations', fn ($q) => $q->where('path', $path));
            if ($class === Page::class && $page->exists) {
                $query->whereKeyNot($page->id);
            }
            if ($query->exists()) {
                return false;
            }
        }

        return true;
    }
}
