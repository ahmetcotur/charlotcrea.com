<?php

namespace App\Services\Site;

class LiveRenderer
{
    public function supports($model): bool
    {
        return true;
    }

    public function render($model, string $locale): string
    {
        $site = new PublicSite;
        $data = $site->data($model, $locale);
        $data['seo'] = (new Seo)->build($data);

        return view('site.pages.'.$data['kind'], $data)->render();
    }
}
