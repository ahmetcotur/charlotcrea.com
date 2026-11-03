<?php

namespace App\Services;

use App\Models\Page;

class PagePreview
{
    public function render(Page $page, string $locale): string
    {
        $previousIndexing = config('charlot.indexable');
        config(['charlot.indexable' => false]);
        try {
            // Hydrated preview models only; never save or populate the public cache.
            $page->published = true;

            return app(PageRenderer::class)->render($page, $locale);
        } finally {
            config(['charlot.indexable' => $previousIndexing]);
        }
    }
}
