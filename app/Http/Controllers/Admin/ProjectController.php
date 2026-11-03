<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\TagsDestinations;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Project;
use App\Models\Reference;
use App\Services\Admin\MediaAttacher;
use App\Services\Admin\PathGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProjectController extends Controller
{
    use TagsDestinations;

    public function index(Request $request): View
    {
        $projects = Project::with('translations')
            ->when($request->string('q'), fn ($query, $term) => $query->whereHas('translations', fn ($q) => $q->where('title', 'like', '%'.$term.'%')))
            ->orderBy('position')->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('admin.projects.form', ['project' => new Project, 'destinations' => $this->destinationTree(), 'selectedDestinations' => [], 'references' => Reference::with('translations')->orderBy('position')->get()]);
    }

    public function store(Request $request, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request);

        $project = DB::transaction(function () use ($data): Project {
            $project = Project::create([
                'published' => $data['published'] ?? false,
                'reference_id' => $data['reference_id'] ?? null,
                'position' => (int) Project::max('position') + 1,
            ]);
            $this->saveTranslations($project, $data);
            $this->syncDestinationTags($project, $data['destinations'] ?? []);

            return $project;
        });

        $this->syncMedia($request, $project, $mediaAttacher);

        return redirect()->route('admin.projects.edit', $project)->with('success', 'Portföy kaydı oluşturuldu.');
    }

    public function edit(Project $project): View
    {
        $project->load(['translations', 'countries', 'locations', 'films.translations', 'medias']);

        return view('admin.projects.form', [
            'project' => $project,
            'destinations' => $this->destinationTree(),
            'references' => Reference::with('translations')->orderBy('position')->get(),
            'selectedDestinations' => $project->countries->pluck('id')->merge($project->locations->pluck('id'))->all(),
        ]);
    }

    public function update(Request $request, Project $project, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request, $project);

        DB::transaction(function () use ($project, $data): void {
            $project->update(['published' => $data['published'] ?? false, 'reference_id' => $data['reference_id'] ?? null]);
            $this->saveTranslations($project, $data);
            $this->syncDestinationTags($project, $data['destinations'] ?? []);
        });

        $this->syncMedia($request, $project, $mediaAttacher);

        return back()->with('success', 'Portföy kaydı güncellendi.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Portföy kaydı arşivlendi.');
    }

    private function syncMedia(Request $request, Project $project, MediaAttacher $mediaAttacher): void
    {
        if ($request->hasFile('cover')) {
            $mediaAttacher->replace($project, $request->file('cover'), 'cover');
        }
        if ($request->boolean('remove_cover') && $project->hasImage('cover')) {
            $media = $project->medias()->wherePivot('role', 'cover')->first();
            if ($media) {
                $mediaAttacher->remove($project, $media->id, 'cover');
            }
        }
        foreach ($request->file('gallery', []) as $file) {
            $mediaAttacher->append($project, $file, 'gallery');
        }
        foreach ($request->input('remove_gallery', []) as $mediaId) {
            $mediaAttacher->remove($project, (int) $mediaId, 'gallery');
        }
        if ($request->has('gallery_order')) {
            $mediaAttacher->reorder($project, array_values(array_diff($request->input('gallery_order', []), $request->input('remove_gallery', []))));
        }
    }

    private function destinationTree()
    {
        return Destination::with('translations')->orderBy('_lft')->get();
    }

    private function validated(Request $request, ?Project $project = null): array
    {
        app(\App\Services\Admin\ContentAddresses::class)->prepare($request, Project::class, $project);
        $pathGuard = app(PathGuard::class);
        $rules = [
            'published' => ['sometimes', 'boolean'],
            'reference_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('references', 'id')->whereNull('deleted_at')],
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
                function ($attribute, $value, $fail) use ($pathGuard, $project) {
                    if ($pathGuard->isTaken($value, Project::class, $project?->id)) {
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

    private function saveTranslations(Project $project, array $data): void
    {
        foreach (['en', 'tr'] as $locale) {
            $translation = $project->translations()->firstOrNew(['locale' => $locale]);
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
