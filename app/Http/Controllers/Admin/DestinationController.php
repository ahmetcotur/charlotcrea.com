<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Services\Admin\ContentAddresses;
use App\Services\Admin\MediaAttacher;
use App\Services\Admin\PathGuard;
use App\Services\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DestinationController extends Controller
{
    public function index(): View
    {
        $destinations = Destination::with('translations')->orderBy('_lft')->get();

        return view('admin.destinations.index', compact('destinations'));
    }

    public function create(): View
    {
        return view('admin.destinations.form', ['destination' => new Destination, 'parents' => $this->parentOptions(null)]);
    }

    public function store(Request $request, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request);
        $parent = ! empty($data['parent_id']) ? Destination::find($data['parent_id']) : null;

        $destination = DB::transaction(function () use ($data, $parent): Destination {
            $destination = Destination::create([
                'published' => $data['published'] ?? false,
                'parent_id' => $parent?->id,
                'source_data' => ['seo_keywords' => $this->keywordValues($data)],
                'position' => (int) Destination::where('parent_id', $parent?->id)->max('position') + 1,
                ...$this->deriveLabels($parent, $data),
            ]);
            $this->saveTranslations($destination, $data);

            return $destination;
        });

        $this->syncMedia($request, $destination, $mediaAttacher);

        return redirect()->route('admin.destinations.edit', $destination)->with('success', 'Destinasyon oluşturuldu.');
    }

    public function edit(Destination $destination): View
    {
        $destination->load(['translations', 'medias']);

        return view('admin.destinations.form', ['destination' => $destination, 'parents' => $this->parentOptions($destination)]);
    }

    public function update(Request $request, Destination $destination, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request, $destination);
        $parent = ! empty($data['parent_id']) ? Destination::find($data['parent_id']) : null;

        DB::transaction(function () use ($destination, $data, $parent): void {
            $destination->update([
                'published' => $data['published'] ?? false,
                'parent_id' => $parent?->id,
                'source_data' => array_replace($destination->source_data ?? [], ['seo_keywords' => $this->keywordValues($data, $destination)]),
                ...$this->deriveLabels($parent, $data, $destination),
            ]);
            $this->saveTranslations($destination, $data);
        });

        $this->syncMedia($request, $destination, $mediaAttacher);

        return back()->with('success', 'Destinasyon güncellendi.');
    }

    public function destroy(Destination $destination): RedirectResponse
    {
        try {
            $destination->delete();
        } catch (ValidationException $e) {
            return back()->with('error', $e->validator->errors()->first());
        }

        return redirect()->route('admin.destinations.index')->with('success', 'Destinasyon arşivlendi.');
    }

    private function syncMedia(Request $request, Destination $destination, MediaAttacher $mediaAttacher): void
    {
        if ($request->hasFile('cover')) {
            $mediaAttacher->replace($destination, $request->file('cover'), 'cover');
        }
        if ($request->boolean('remove_cover') && $destination->hasImage('cover')) {
            $media = $destination->medias()->wherePivot('role', 'cover')->first();
            if ($media) {
                $mediaAttacher->remove($destination, $media->id, 'cover');
            }
        }
        foreach ($request->file('gallery', []) as $file) {
            $mediaAttacher->append($destination, $file, 'gallery');
        }
        foreach ($request->input('remove_gallery', []) as $mediaId) {
            $mediaAttacher->remove($destination, (int) $mediaId, 'gallery');
        }
        if ($request->has('gallery_order')) {
            $mediaAttacher->reorder($destination, array_values(array_diff($request->input('gallery_order', []), $request->input('remove_gallery', []))));
        }
    }

    /** Options for the parent <select>, excluding the node itself and its own descendants (prevents cycles). */
    private function parentOptions(?Destination $destination)
    {
        $all = Destination::with('translations')->orderBy('_lft')->get();
        if (! $destination?->exists) {
            return $all;
        }
        $excluded = $destination->descendants->pluck('id')->push($destination->id);

        return $all->reject(fn ($d) => $excluded->contains($d->id));
    }

    /** Mirrors App\Http\Controllers\Twill\PortfolioLocationController's country/city derivation. */
    private function deriveLabels(?Destination $parent, array $data, ?Destination $existing = null): array
    {
        $title = $data['title']['en'] ?? $data['title']['tr'] ?? $existing?->title ?? '';

        return [
            'country' => $parent ? ($parent->country ?: $parent->translate('en')?->title) : $title,
            'city' => $parent ? $title : null,
        ];
    }

    private function validated(Request $request, ?Destination $destination = null): array
    {
        app(ContentAddresses::class)->prepare($request, Destination::class, $destination);
        $pathGuard = app(PathGuard::class);
        $rules = [
            'published' => ['sometimes', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'gallery.*' => ['nullable', 'image', 'max:8192'],
            'gallery_order' => ['sometimes', 'array'],
            'gallery_order.*' => ['integer', 'distinct'],
        ];
        foreach (['en', 'tr'] as $locale) {
            $rules["title.$locale"] = ['required', 'string', 'max:200'];
            $rules["path.$locale"] = [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($pathGuard, $destination) {
                    if ($pathGuard->isTaken($value, Destination::class, $destination?->id)) {
                        $fail('Bu adres başka bir içerik tarafından kullanılıyor.');
                    }
                },
            ];
            $rules["heading.$locale"] = ['nullable', 'string', 'max:500'];
            $rules["intro.$locale"] = ['nullable', 'string', 'max:2000'];
            $rules["body.$locale"] = ['nullable', 'string', 'max:60000'];
            $rules["background.$locale"] = ['nullable', 'string', 'max:200'];
            $rules["story_heading.$locale"] = ['nullable', 'string', 'max:500'];
            $rules["story_intro.$locale"] = ['nullable', 'string', 'max:2000'];
            $rules["faq_body.$locale"] = ['nullable', 'string', 'max:60000'];
            $rules["seo_title.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["seo_keywords.$locale"] = ['nullable', 'string', 'max:1000'];
            $rules["description.$locale"] = ['nullable', 'string', 'max:2000'];
        }

        $data = $request->validate($rules);

        if (! empty($data['parent_id'])) {
            $parent = Destination::findOrFail($data['parent_id']);
            if ($parent->ancestors()->count() >= 8) {
                throw ValidationException::withMessages(['parent_id' => 'En fazla 9 seviyeli bir yapı kurulabilir.']);
            }
        }

        foreach (['body', 'faq_body'] as $field) {
            foreach (['en', 'tr'] as $locale) {
                if (isset($data[$field][$locale])) {
                    $data[$field][$locale] = RichText::clean($data[$field][$locale]);
                }
            }
        }

        return $data;
    }

    private function keywordValues(array $data, ?Destination $destination = null): array
    {
        $keywords = $destination?->source_data['seo_keywords'] ?? [];
        foreach ($data['seo_keywords'] ?? [] as $locale => $value) {
            $keywords[$locale] = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) $value)))));
        }

        return $keywords;
    }

    private function saveTranslations(Destination $destination, array $data): void
    {
        foreach (['en', 'tr'] as $locale) {
            $translation = $destination->translations()->firstOrNew(['locale' => $locale]);
            foreach (['title', 'path', 'heading', 'intro', 'body', 'background', 'story_heading', 'story_intro', 'faq_body', 'seo_title', 'description'] as $field) {
                if (array_key_exists($locale, $data[$field] ?? [])) {
                    $translation->{$field} = $data[$field][$locale];
                }
            }
            $translation->active = true;
            $translation->save();
        }
    }
}
