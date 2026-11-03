<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\ContentTypes;
use App\Services\Site\PublicSite;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke()
    {
        $version = Cache::get('charlot:content-version', 'initial');
        $xml = Cache::remember('charlot:sitemap:'.$version.':'.md5(config('app.url')), 300, function () {
            $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
            foreach (ContentTypes::MODELS as $class) {
                foreach ($class::published()->with(['translations', 'medias'])->get() as $model) {
                    if ($model instanceof Page && config('site.homepage_variant', 'home-1') !== 'home-1'
                        && $model->import_key === '/en/'.config('site.homepage_variant').'/'
                        && $model->publicPath('en') && $model->publicPath('tr')) {
                        continue;
                    }
                    foreach ($model->translations as $translation) {
                        if (! $model->publicPath($translation->locale)) {
                            continue;
                        }
                        $xml .= '<url><loc>'.e(rtrim(config('app.url'), '/').$translation->path).'</loc>';
                        foreach (['en', 'tr', 'x-default'] as $locale) {
                            $alternate = $model->translate($locale === 'x-default' ? 'en' : $locale);
                            if ($model->publicPath($locale === 'x-default' ? 'en' : $locale)) {
                                $xml .= '<xhtml:link rel="alternate" hreflang="'.$locale.'" href="'.e(rtrim(config('app.url'), '/').$alternate->path).'"/>';
                            }
                        }
                        if ($image = $model->coverImage()) {
                            $src = (new PublicSite)->url($image['src']);
                            $xml .= '<image:image><image:loc>'.e($src).'</image:loc></image:image>';
                        }
                        $xml .= '</url>';
                    }
                }
            }

            return $xml.'</urlset>';
        });

        return response($xml)->header('Content-Type', 'application/xml');
    }
}
