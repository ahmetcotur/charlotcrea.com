<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TagsDestinations;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Film;
use App\Models\Project;
use App\Services\Admin\MediaAttacher;
use App\Services\Admin\PathGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FilmController extends Controller
{
    use TagsDestinations;

    public function index(Request $request): View
    {
        $films = Film::with(['translations', 'project'])
            ->when($request->string('q'), fn ($query, $term) => $query->whereHas('translations', fn ($q) => $q->where('title', 'like', '%'.$term.'%')))
            ->orderBy('position')->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.films.index', compact('films'));
    }

    public function create(): View
    {
        return view('admin.films.form', ['film' => new Film, 'destinations' => $this->destinationTree(), 'projects' => Project::with('translations')->orderBy('position')->get(), 'selectedDestinations' => []]);
    }

    public function store(Request $request, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request);

        $film = DB::transaction(function () use ($data): Film {
            $film = Film::create([
                'published' => $data['published'] ?? false,
                'project_id' => $data['project_id'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'position' => (int) Film::max('position') + 1,
            ]);
            $this->saveTranslations($film, $data);
            $this->syncDestinationTags($film, $data['destinations'] ?? []);

            return $film;
        });

        $this->syncMedia($request, $film, $mediaAttacher);

        return redirect()->route('admin.films.edit', $film)->with('success', 'Film kaydı oluşturuldu.');
    }

    public function edit(Film $film): View
    {
        $film->load(['translations', 'countries', 'locations', 'medias']);

        return view('admin.films.form', [
            'film' => $film,
            'destinations' => $this->destinationTree(),
            'projects' => Project::with('translations')->orderBy('position')->get(),
            'selectedDestinations' => $film->countries->pluck('id')->merge($film->locations->pluck('id'))->all(),
        ]);
    }

    public function update(Request $request, Film $film, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request, $film);

        DB::transaction(function () use ($film, $data): void {
            $film->update([
                'published' => $data['published'] ?? false,
                'project_id' => $data['project_id'] ?? null,
                'video_url' => $data['video_url'] ?? null,
            ]);
            $this->saveTranslations($film, $data);
            $this->syncDestinationTags($film, $data['destinations'] ?? []);
        });

        $this->syncMedia($request, $film, $mediaAttacher);

        return back()->with('success', 'Film kaydı güncellendi.');
    }

    public function destroy(Film $film): RedirectResponse
    {
        $film->delete();

        return redirect()->route('admin.films.index')->with('success', 'Film kaydı arşivlendi.');
    }

    private function syncMedia(Request $request, Film $film, MediaAttacher $mediaAttacher): void
    {
        if ($request->hasFile('cover')) {
            $mediaAttacher->replace($film, $request->file('cover'), 'cover');
        }
        if ($request->boolean('remove_cover') && $film->hasImage('cover')) {
            $media = $film->medias()->wherePivot('role', 'cover')->first();
            if ($media) {
                $mediaAttacher->remove($film, $media->id, 'cover');
            }
        }
        foreach ($request->file('gallery', []) as $file) {
            $mediaAttacher->append($film, $file, 'gallery');
        }
        foreach ($request->input('remove_gallery', []) as $mediaId) {
            $mediaAttacher->remove($film, (int) $mediaId, 'gallery');
        }
        if ($request->has('gallery_order')) {
            $mediaAttacher->reorder($film, array_values(array_diff($request->input('gallery_order', []), $request->input('remove_gallery', []))));
        }
    }

    private function destinationTree()
    {
        return Destination::with('translations')->orderBy('_lft')->get();
    }

    private function validated(Request $request, ?Film $film = null): array
    {
        app(\App\Services\Admin\ContentAddresses::class)->prepare($request, Film::class, $film);
        $pathGuard = app(PathGuard::class);
        $rules = [
            'published' => ['sometimes', 'boolean'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'video_url' => ['nullable', 'string', 'max:1000'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'gallery.*' => ['nullable', 'image', 'max:8192'],
            'gallery_order' => ['sometimes', 'array'],
            'gallery_order.*' => ['integer', 'distinct'],
            'destinations' => ['nullable', 'array'],
            'destinations.*' => ['integer', 'exists:destinations,id'],
        ];
        foreach (['en', 'tr'] as $locale) {
            $rules["title.$locale"] = ['required', 'string', 'max:200'];
            $rules["path.$locale"] = [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($pathGuard, $film) {
                    if ($pathGuard->isTaken($value, Film::class, $film?->id)) {
                        $fail('Bu adres başka bir içerik tarafından kullanılıyor.');
                    }
                },
            ];
            $rules["heading.$locale"] = ['nullable', 'string', 'max:500'];
            $rules["intro.$locale"] = ['nullable', 'string', 'max:2000'];
            $rules["seo_title.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["description.$locale"] = ['nullable', 'string', 'max:2000'];
        }

        return $request->validate($rules);
    }

    private function saveTranslations(Film $film, array $data): void
    {
        foreach (['en', 'tr'] as $locale) {
            $translation = $film->translations()->firstOrNew(['locale' => $locale]);
            foreach (['title', 'path', 'heading', 'intro', 'seo_title', 'description'] as $field) {
                if (array_key_exists($locale, $data[$field] ?? [])) {
                    $translation->{$field} = $data[$field][$locale];
                }
            }
            $translation->active = true;
            $translation->save();
        }
    }
}
