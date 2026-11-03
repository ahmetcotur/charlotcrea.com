<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\LegacyRedirect;
use App\Models\Page;
use App\Services\ContentTypes;
use App\Services\PageRenderer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ContentController extends Controller
{
    public function __invoke(Request $request, PageRenderer $renderer, string $path = '')
    {
        $originalPath = $request->getPathInfo();
        $redirect = LegacyRedirect::where('source', $originalPath)->first()
            ?? LegacyRedirect::where('source', rtrim($originalPath, '/').'/')->first();
        if ($redirect) {
            $target = $redirect->target;
            $visited = [$originalPath];
            while ($next = LegacyRedirect::where('source', $target)->first()) {
                abort_if(in_array($target, $visited), 404);
                $visited[] = $target;
                $target = $next->target;
            }

            return redirect($target.($request->getQueryString() ? '?'.$request->getQueryString() : ''), 301);
        }
        $path = '/'.trim($path, '/').'/';
        if ($path === '//') {
            return redirect('/en/', 302);
        }
        $locale = explode('/', trim($path, '/'))[0];
        abort_unless(in_array($locale, ['en', 'tr']), 404);
        app()->setLocale($locale);
        $version = Cache::get('charlot:content-version', 'initial');
        $key = 'charlot:page:'.$version.':'.hash('sha256', config('app.url').$path.(int) config('charlot.indexable'));
        $html = Cache::remember($key, config('charlot.page_cache_seconds'), function () use ($path, $locale, $renderer) {
            if ($path === '/'.$locale.'/' && config('site.homepage_variant', 'home-1') !== 'home-1') {
                $home = Page::forPublic($locale)->with(['translations', 'medias'])
                    ->where('import_key', '/en/'.config('site.homepage_variant').'/')->first();
                if ($home) {
                    return $renderer->render($home, $locale, true);
                }
                // Keep the original homepage available if the selected variant is unpublished.
            }
            foreach (ContentTypes::MODELS as $class) {
                $model = $class::forPublic($locale)->with(['translations', 'medias'])->whereHas('translations', fn ($q) => $q->where('path', $path)->where('locale', $locale)->where('active', true))->first();
                if (! $model) {
                    continue;
                }
                abort_unless($model->published, 404);

                $asHomepage = $model instanceof Page
                    && config('site.homepage_variant', 'home-1') !== 'home-1'
                    && $model->import_key === '/en/'.config('site.homepage_variant').'/';

                return $renderer->render($model, $locale, $asHomepage);
            }
            abort(404);
        });

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8')->header('X-Robots-Tag', config('charlot.indexable') ? 'index, follow' : 'noindex, nofollow');
    }
}
