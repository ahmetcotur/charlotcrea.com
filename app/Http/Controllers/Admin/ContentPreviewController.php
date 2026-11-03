<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Film;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Services\HomeSections;
use App\Services\PageRenderer;
use App\Services\RichText;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ContentPreviewController extends Controller
{
    public function __invoke(Request $request, PageRenderer $renderer, string $type, int $id): Response
    {
        $class = ['pages' => Page::class, 'projects' => Project::class, 'films' => Film::class, 'destinations' => Destination::class, 'posts' => Post::class][$type] ?? null;
        abort_unless($class, 404);
        $rules = ['preview_locale' => ['required', 'in:tr,en'], 'video_url' => ['nullable', 'string', 'max:1000']];
        $fields = ['title', 'heading', 'intro', 'body', 'seo_title', 'description', 'category', 'story_heading', 'story_intro', 'faq_body'];
        foreach ($fields as $field) {
            $rules[$field] = ['sometimes', 'array'];
            foreach (['tr', 'en'] as $locale) {
                $rules[$field.'.'.$locale] = ['nullable', 'string', 'max:60000'];
            }
        }
        $data = $request->validate($rules);
        $model = $class::with(['translations', 'medias'])->findOrFail($id);
        foreach ($model->translations as $translation) {
            foreach ($fields as $field) {
                if (array_key_exists($translation->locale, $data[$field] ?? [])) {
                    $translation->{$field} = in_array($field, ['body', 'faq_body'])
                        ? RichText::clean($data[$field][$translation->locale]) : $data[$field][$translation->locale];
                }
            }
        }
        if (array_key_exists('video_url', $data) && in_array($type, ['pages', 'films'])) {
            $model->video_url = $data['video_url'];
        }
        if ($model instanceof Page && ($sections = app(HomeSections::class)->validated($request, $model)) !== null) {
            $model->source_data = array_replace($model->source_data ?? [], ['home_sections' => $sections]);
        }
        $model->published = true;
        $locale = $data['preview_locale'];
        abort_unless($model->translate($locale), 422);
        app()->setLocale($locale);
        config(['charlot.indexable' => false]);
        $html = $renderer->render($model, $locale);
        $banner = '<div style="position:relative;z-index:99999;background:#fff5d9;color:#59420e;padding:12px 20px;text-align:center;font:14px sans-serif">Taslak önizleme · Metinler kaydedilmedi. Görseller ve ilişkiler son kaydedilen haliyle gösterilir.</div>';
        $html = preg_replace('/(<body[^>]*>)/i', '$1'.$banner, $html, 1);

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store, max-age=0')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
