<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\Admin\MediaAttacher;
use App\Services\Admin\PathGuard;
use App\Services\RichText;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = Post::with('translations')
            ->when($request->string('q'), fn ($query, $term) => $query->whereHas('translations', fn ($q) => $q->where('title', 'like', '%'.$term.'%')))
            ->orderBy('position')->orderBy('id')->paginate(20)->withQueryString();

        return view('admin.posts.index', compact('posts'));
    }

    public function create(): View
    {
        return view('admin.posts.form', ['post' => new Post]);
    }

    public function store(Request $request, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request);

        $post = DB::transaction(function () use ($data): Post {
            $post = Post::create([
                'published' => $data['published'] ?? false,
                'published_at' => $data['published_at'] ?? null,
                'position' => (int) Post::max('position') + 1,
            ]);
            $this->saveTranslations($post, $data);

            return $post;
        });

        if ($request->hasFile('cover')) {
            $mediaAttacher->replace($post, $request->file('cover'), 'cover');
        }

        return redirect()->route('admin.posts.edit', $post)->with('success', 'Blog yazısı oluşturuldu.');
    }

    public function edit(Post $post): View
    {
        $post->load('translations');

        return view('admin.posts.form', ['post' => $post]);
    }

    public function update(Request $request, Post $post, MediaAttacher $mediaAttacher): RedirectResponse
    {
        $data = $this->validated($request, $post);

        DB::transaction(function () use ($post, $data): void {
            $post->update([
                'published' => $data['published'] ?? false,
                'published_at' => $data['published_at'] ?? null,
            ]);
            $this->saveTranslations($post, $data);
        });

        if ($request->hasFile('cover')) {
            $mediaAttacher->replace($post, $request->file('cover'), 'cover');
        }

        if ($request->boolean('remove_cover') && $post->hasImage('cover')) {
            $media = $post->medias()->wherePivot('role', 'cover')->first();
            if ($media) {
                $mediaAttacher->remove($post, $media->id, 'cover');
            }
        }

        return back()->with('success', 'Blog yazısı güncellendi.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Blog yazısı arşivlendi.');
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        app(\App\Services\Admin\ContentAddresses::class)->prepare($request, Post::class, $post);
        $pathGuard = app(PathGuard::class);
        $rules = ['published' => ['sometimes', 'boolean'], 'published_at' => ['nullable', 'date'], 'cover' => ['nullable', 'image', 'max:8192']];
        foreach (['en', 'tr'] as $locale) {
            $rules["title.$locale"] = ['required', 'string', 'max:200'];
            $rules["path.$locale"] = [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) use ($pathGuard, $post) {
                    if ($pathGuard->isTaken($value, Post::class, $post?->id)) {
                        $fail('Bu adres başka bir içerik tarafından kullanılıyor.');
                    }
                },
            ];
            $rules["heading.$locale"] = ['nullable', 'string', 'max:500'];
            $rules["intro.$locale"] = ['nullable', 'string', 'max:2000'];
            $rules["body.$locale"] = ['nullable', 'string', 'max:60000'];
            $rules["category.$locale"] = ['nullable', 'string', 'max:120'];
            $rules["seo_title.$locale"] = ['nullable', 'string', 'max:255'];
            $rules["description.$locale"] = ['nullable', 'string', 'max:2000'];
        }

        $data = $request->validate($rules);
        foreach (['en', 'tr'] as $locale) {
            if (isset($data['body'][$locale])) {
                $data['body'][$locale] = RichText::clean($data['body'][$locale]);
            }
        }

        return $data;
    }

    private function saveTranslations(Post $post, array $data): void
    {
        foreach (['en', 'tr'] as $locale) {
            $translation = $post->translations()->firstOrNew(['locale' => $locale]);
            foreach (['title', 'path', 'heading', 'intro', 'body', 'category', 'seo_title', 'description'] as $field) {
                if (array_key_exists($locale, $data[$field] ?? [])) {
                    $translation->{$field} = $data[$field][$locale];
                }
            }
            $translation->active = true;
            $translation->save();
        }
    }
}
