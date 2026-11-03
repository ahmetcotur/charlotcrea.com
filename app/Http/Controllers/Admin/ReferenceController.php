<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reference;
use App\Services\Admin\MediaAttacher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReferenceController extends Controller
{
    public function index(Request $request): View
    {
        $references = Reference::with('translations')
            ->when($request->string('q'), fn ($query, $term) => $query->whereHas('translations', fn ($q) => $q->where('title', 'like', '%'.$term.'%')))
            ->orderBy('position')->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.references.index', compact('references'));
    }

    public function create(): View
    {
        return view('admin.references.form', ['reference' => new Reference]);
    }

    public function store(Request $request, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request);

        $reference = DB::transaction(function () use ($data): Reference {
            $reference = Reference::create([
                'published' => $data['published'] ?? false,
                'website_url' => $data['website_url'] ?? null,
                'position' => (int) Reference::max('position') + 1,
            ]);
            $this->saveTranslations($reference, $data);

            return $reference;
        });

        if ($request->hasFile('logo')) {
            $mediaAttacher->replace($reference, $request->file('logo'), 'logo');
        }

        return redirect()->route('admin.references.edit', $reference)->with('success', 'Referans oluşturuldu.');
    }

    public function edit(Reference $reference): View
    {
        $reference->load('translations');

        return view('admin.references.form', ['reference' => $reference]);
    }

    public function update(Request $request, Reference $reference, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request);

        DB::transaction(function () use ($reference, $data): void {
            $reference->update([
                'published' => $data['published'] ?? false,
                'website_url' => $data['website_url'] ?? null,
            ]);
            $this->saveTranslations($reference, $data);
        });

        if ($request->hasFile('logo')) {
            $mediaAttacher->replace($reference, $request->file('logo'), 'logo');
        }

        if ($request->boolean('remove_logo') && $reference->hasImage('logo')) {
            $media = $reference->medias()->wherePivot('role', 'logo')->first();
            if ($media) {
                $mediaAttacher->remove($reference, $media->id, 'logo');
            }
        }

        return back()->with('success', 'Referans güncellendi.');
    }

    public function destroy(Reference $reference): RedirectResponse
    {
        $reference->delete();

        return redirect()->route('admin.references.index')->with('success', 'Referans arşivlendi.');
    }

    private function validated(Request $request): array
    {
        $rules = ['published' => ['sometimes', 'boolean'], 'website_url' => ['nullable', 'url', 'max:1000'], 'logo' => ['nullable', 'image', 'max:5120']];
        foreach (['en', 'tr'] as $locale) {
            $rules["title.$locale"] = ['required', 'string', 'max:200'];
            $rules["description.$locale"] = ['nullable', 'string', 'max:2000'];
        }

        return $request->validate($rules);
    }

    private function saveTranslations(Reference $reference, array $data): void
    {
        foreach (['en', 'tr'] as $locale) {
            $translation = $reference->translations()->firstOrNew(['locale' => $locale]);
            $translation->title = $data['title'][$locale] ?? $translation->title;
            $translation->description = $data['description'][$locale] ?? null;
            $translation->active = true;
            $translation->save();
        }
    }
}
