<?php

namespace App\Services;

use App\Models\Page;
use App\Services\Site\LiveRenderer;

/** All public pages are rendered directly from Blade templates and structured CMS data. */
class PageRenderer
{
    public function render($model, string $locale, bool $asHomepage = false): string
    {
        if ($asHomepage && $model instanceof Page) {
            foreach ($model->translations as $translation) {
                $translation->path = '/'.$translation->locale.'/';
            }
        }

        return app(LiveRenderer::class)->render($model, $locale);
    }
}
