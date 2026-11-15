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
        $baseUrl = rtrim(config('app.url'), '/');

        $xml = Cache::remember('charlot:sitemap:'.$version.':'.md5($baseUrl), 300, function () use ($baseUrl) {
            $publicSite = new PublicSite();
            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';

            foreach (ContentTypes::MODELS as $class) {
                foreach ($class::published()->with(['translations', 'medias'])->get() as $model) {
                    if ($model instanceof Page
                        && config('site.homepage_variant', 'home-1') !== 'home-1'
                        && $model->import_key === '/en/'.config('site.homepage_variant').'/'
                        && $model->publicPath('en') && $model->publicPath('tr')) {
                        continue;
                    }

                    foreach ($model->translations as $translation) {
                        if (! $model->publicPath($translation->locale)) {
                            continue;
                        }

                        $xml .= '<url>';
                        $xml .= '<loc>'.e($baseUrl.$translation->path).'</loc>';

                        $lastmod = ($translation->updated_at ?? $model->updated_at ?? now())->format('Y-m-d');
                        $xml .= '<lastmod>'.e($lastmod).'</lastmod>';

                        foreach (['en', 'tr', 'x-default'] as $locale) {
                            $targetLocale = $locale === 'x-default' ? 'en' : $locale;
                            $alternate = $model->translate($targetLocale);
                            if ($model->publicPath($targetLocale) && $alternate?->path) {
                                $xml .= '<xhtml:link rel="alternate" hreflang="'.$locale.'" href="'.e($baseUrl.$alternate->path).'"/>';
                            }
                        }

                        if ($image = $model->coverImage()) {
                            $src = $publicSite->url($image['src']);
                            $xml .= '<image:image><image:loc>'.e($src).'</image:loc></image:image>';
                        }

                        $xml .= '</url>';
                    }
                }
            }

            return $xml.'</urlset>';
        });

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600',
            'X-Robots-Tag' => config('charlot.indexable') ? 'all' : 'noindex, follow',
        ]);
    }
}
