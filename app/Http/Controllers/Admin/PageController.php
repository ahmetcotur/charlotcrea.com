<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Admin\MediaAttacher;
use App\Services\PageAddresses;
use App\Services\PageManagement;
use App\Services\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(): View
    {
        $pages = Page::with(['translations', 'ancestors.translations'])->orderBy('position')->orderBy('id')->get();
        $groups = $pages->groupBy(fn (Page $page) => PageManagement::describe($page)['group']);

        return view('admin.pages.index', compact('groups', 'pages'));
    }

    public function create(): View
    {
        return view('admin.pages.form', ['page' => new Page, 'info' => null, 'parents' => $this->parentOptions(null)]);
    }

    public function store(Request $request, PageAddresses $addresses, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request, null, true);
        $page = DB::transaction(function () use ($data, $addresses): Page {
            $page = Page::create([
                'published' => false,
                'position' => (int) Page::max('position') + 1,
                'parent_id' => $data['parent_id'] ?? null,
                'video_url' => $data['video_url'] ?? null,
            ]);
            $this->saveTranslations($page, $addresses->prepare($page, $data));

            return $page;
        });

        $this->syncMedia($request, $page, $mediaAttacher);

        return redirect()->route('admin.pages.edit', $page)->with('success', 'Sayfa taslağı oluşturuldu.');
    }

    public function edit(Page $page): View
    {
        $page->load(['translations', 'ancestors.translations', 'medias']);

        return view('admin.pages.form', ['page' => $page, 'info' => PageManagement::describe($page), 'parents' => $this->parentOptions($page), 'homeSections' => app(\App\Services\HomeSections::class)->editor($page), 'sectionChoices' => app(\App\Services\HomeSections::class)->supports($page) ? app(\App\Services\HomeSections::class)->choices() : []]);
    }

    public function update(Request $request, Page $page, PageAddresses $addresses, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request, $page);
        DB::transaction(function () use ($page, $data, $addresses): void {
            $page->published = $data['published'] ?? false;
            $page->parent_id = $data['parent_id'] ?? null;
            $page->video_url = $data['video_url'] ?? null;
            if (isset($data['home_sections'])) {
                $page->source_data = array_replace($page->source_data ?? [], ['home_sections' => $data['home_sections']]);
            }
            $page->save();
            $this->saveTranslations($page, $addresses->prepare($page, $data));
        });

        $this->syncMedia($request, $page, $mediaAttacher);

        return back()->with('success', 'Sayfa güncellendi.');
    }

    private function syncMedia(Request $request, Page $page, MediaAttacher $mediaAttacher): void
    {
        if ($request->hasFile('cover')) {
            $mediaAttacher->replace($page, $request->file('cover'), 'cover');
        }
        if ($request->boolean('remove_cover') && $page->hasImage('cover')) {
            $media = $page->medias()->wherePivot('role', 'cover')->first();
            if ($media) {
                $mediaAttacher->remove($page, $media->id, 'cover');
            }
        }
        foreach ($request->file('gallery', []) as $file) {
            $mediaAttacher->append($page, $file, 'gallery');
        }
        foreach ($request->input('remove_gallery', []) as $mediaId) {
            $mediaAttacher->remove($page, (int) $mediaId, 'gallery');
        }
        if ($request->has('gallery_order')) {
            $mediaAttacher->reorder($page, array_values(array_diff($request->input('gallery_order', []), $request->input('remove_gallery', []))));
        }
    }

    /** Options for the parent <select>, excluding the page itself and its own descendants (prevents cycles). */
    private function parentOptions(?Page $page)
    {
        $all = Page::with('translations')->orderBy('_lft')->get();
        if (! $page?->exists) {
            return $all;
        }
        $excluded = $page->descendants->pluck('id')->push($page->id);

        return $all->reject(fn ($p) => $excluded->contains($p->id));
    }

    private function validated(Request $request, ?Page $page = null, bool $creating = false): array
    {
        $rules = [
            'parent_id' => ['nullable', 'integer', 'exists:pages,id'],
            'video_url' => ['nullable', 'string', 'max:1000'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'gallery.*' => ['nullable', 'image', 'max:8192'],
            'gallery_order' => ['sometimes', 'array'],
            'gallery_order.*' => ['integer', 'distinct'],
        ];
        foreach (['en', 'tr'] as $locale) {
            $rules["title.$locale"] = [$creating && $locale === 'en' ? 'required' : 'nullable', 'string', 'max:200'];
            $rules["path.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["heading.$locale"] = ['nullable', 'string', 'max:500'];
            $rules["intro.$locale"] = ['nullable', 'string', 'max:5000'];
            $rules["body.$locale"] = ['nullable', 'string', 'max:60000'];
            $rules["seo_title.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["description.$locale"] = ['nullable', 'string', 'max:2000'];
        }
        $rules['published'] = ['sometimes', 'boolean'];

        $data = $request->validate($rules);
        if ($page && ($sections = app(\App\Services\HomeSections::class)->validated($request, $page)) !== null) {
            $data['home_sections'] = $sections;
        }

        if (! empty($data['parent_id']) && $page?->exists) {
            $excluded = $page->descendants->pluck('id')->push($page->id);
            if ($excluded->contains((int) $data['parent_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages(['parent_id' => 'Bir sayfa kendi alt sayfasının altına taşınamaz.']);
            }
        }

        foreach (['en', 'tr'] as $locale) {
            if (isset($data['body'][$locale])) {
                $data['body'][$locale] = RichText::clean($data['body'][$locale]);
            }
        }

        return $data;
    }

    private function saveTranslations(Page $page, array $data): void
    {
        foreach (['en', 'tr'] as $locale) {
            $translation = $page->translations()->firstOrNew(['locale' => $locale]);
            foreach (['title', 'path', 'heading', 'intro', 'body', 'seo_title', 'description'] as $field) {
                if (array_key_exists($locale, $data[$field] ?? [])) {
                    $translation->{$field} = $data[$field][$locale];
                }
            }
            if ($translation->title) {
                $translation->active = true;
            }
            $translation->save();
        }
    }
}
