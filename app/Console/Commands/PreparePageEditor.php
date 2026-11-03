<?php

namespace App\Console\Commands;

use App\Models\Page;
use App\Services\EditorialHeading;
use App\Services\PageCopy;
use App\Services\PageManagement;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;

class PreparePageEditor extends Command
{
    protected $signature = 'charlot:prepare-page-editor';

    protected $description = 'Initialize clear page names and editable hero media without replacing CMS edits';

    public function handle(): int
    {
        $englishNames = ['/en/' => 'Home', '/en/home-2/' => 'Home — Photo strip', '/en/home-3/' => 'Home — Video', '/en/about/' => 'About', '/en/services/' => 'Services', '/en/contact/' => 'Contact', '/en/photography/' => 'Photography', '/en/film/' => 'Film', '/en/destinations/' => 'Destinations', '/en/blog/' => 'Blog', '/en/references/' => 'References'];
        foreach (Page::with('translations')->get() as $page) {
            $source = $page->source_data ?? [];
            if (PageManagement::PAGES[$page->import_key]['copy'] ?? false) {
                foreach (['en', 'tr'] as $locale) {
                    $translation = $page->translate($locale);
                    if ($translation?->legacy_html && blank($translation->body)) {
                        $translation->body = PageCopy::extract($translation->legacy_html);
                        $translation->save();
                    }
                }
            }
            if (($source['page_editor_initialized'] ?? false) || ! isset(PageManagement::PAGES[$page->import_key])) {
                continue;
            }
            $definition = PageManagement::PAGES[$page->import_key];
            $images = [];
            foreach (['en', 'tr'] as $locale) {
                $translation = $page->translate($locale);
                if (! $translation?->legacy_html) {
                    continue;
                }
                [$plain, $formatted] = EditorialHeading::read($translation->legacy_html);
                if (in_array($translation->title, [$plain, $formatted], true)) {
                    $translation->title = $locale === 'tr' ? $definition['label'] : $englishNames[$page->import_key];
                }
                if ($translation->heading === $plain) {
                    $translation->heading = $formatted;
                }
                $dom = new DOMDocument;
                $before = libxml_use_internal_errors(true);
                $dom->loadHTML('<?xml encoding="UTF-8">'.$translation->legacy_html, LIBXML_NONET);
                libxml_clear_errors();
                libxml_use_internal_errors($before);
                $xp = new DOMXPath($dom);
                if (($definition['media'] ?? '') === 'gallery') {
                    foreach ($xp->query('//*[contains(concat(" ",normalize-space(@class)," ")," hero-filmstrip-track ")]/a') as $i => $link) {
                        $image = $link->getElementsByTagName('img')->item(0);
                        if (! $image) {
                            continue;
                        }
                        $images[$i]['src'] = $link->getAttribute('href');
                        $images[$i]['caption'][$locale] = trim($link->getElementsByTagName('span')->item(0)?->textContent ?? '');
                        $images[$i]['alt'][$locale] = $image->getAttribute('alt');
                    }
                } elseif (($definition['media'] ?? '') === 'video') {
                    $video = $xp->query('//main//video')->item(0);
                    if ($video) {
                        $page->video_url ??= $video->getElementsByTagName('source')->item(0)?->getAttribute('src');
                        $source['cover'] ??= $video->getAttribute('poster');
                        $source['cover_small'] ??= $source['cover'];
                    }
                } elseif (($definition['media'] ?? '') === 'cover') {
                    $image = $xp->query('(//main//img)[1]')->item(0);
                    if ($image) {
                        $source['cover'] ??= $image->getAttribute('src');
                        $source['cover_small'] ??= $source['cover'];
                    }
                }
            }
            if ($images) {
                $source['images'] ??= $images;
            }
            $source['name'] ??= $definition['label'];
            $source['page_editor_initialized'] = true;
            $page->source_data = $source;
            $page->save();
        }
        $this->info('Page editor names and hero media initialized. Existing edits were preserved.');

        return self::SUCCESS;
    }
}
