<?php

namespace App\Services;

use App\Models\Page;

class EditorialLinks
{
    public function html(?string $html, string $locale): string
    {
        $html = RichText::clean($html);
        $routes = json_decode(file_get_contents(base_path('data/language-routes.json')), true);
        foreach (Page::with('translations')->get() as $page) {
            $original = $locale === 'en' ? $page->import_key : ($routes[$page->import_key] ?? null);
            if ($original && ($path = $page->publicPath($locale))) {
                $html = str_replace('href="'.$original.'"', 'href="'.e($path).'"', $html);
            }
        }

        return $html;
    }

}
