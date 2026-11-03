<?php

namespace App\Services\Admin;

use App\Models\Destination;
use App\Models\Film;
use App\Models\Post;
use App\Models\Project;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ContentAddresses
{
    public function prepare(Request $request, string $class, ?Model $existing = null): void
    {
        $request->validate([
            'title' => ['required', 'array'], 'title.en' => ['required', 'string', 'max:200'],
            'title.tr' => ['required', 'string', 'max:200'], 'path' => ['nullable', 'array'],
            'path.en' => ['nullable', 'string', 'max:255'], 'path.tr' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer'],
        ]);
        $sections = match ($class) {
            Project::class => ['en' => 'projects', 'tr' => 'projeler'],
            Film::class => ['en' => 'films', 'tr' => 'filmler'],
            Post::class => ['en' => 'blog', 'tr' => 'blog'],
            Destination::class => ['en' => 'destinations', 'tr' => 'destinasyonlar'],
        };
        $parent = $class === Destination::class && $request->input('parent_id')
            ? Destination::find($request->input('parent_id')) : null;
        $paths = [];
        foreach ($sections as $locale => $section) {
            $path = trim((string) $request->input('path.'.$locale));
            if (! $path) {
                $path = $existing?->translate($locale)?->path;
            }
            if (! $path) {
                $prefix = $parent?->translate($locale)?->path ?: '/'.$locale.'/'.$section.'/';
                $slug = Str::slug($request->input('title.'.$locale), '-', $locale) ?: 'item';
                for ($suffix = 1; ; $suffix++) {
                    $candidate = rtrim($prefix, '/').'/'.$slug.($suffix === 1 ? '' : '-'.$suffix).'/';
                    if (! app(PathGuard::class)->isTaken($candidate, $class, $existing?->id)) {
                        $path = $candidate;
                        break;
                    }
                }
            }
            $paths[$locale] = $path;
        }
        $request->merge(['path' => $paths]);
    }
}
