<?php

namespace App\Services;

use App\Models\Page;
use DOMDocument;

class NativePageData
{
    private ?array $blueprints = null;

    public function defaults(Page $page): array
    {
        $this->blueprints ??= json_decode(file_get_contents(base_path('data/native-pages.json')), true, 512, JSON_THROW_ON_ERROR);

        return $this->blueprints[$page->import_key] ?? [];
    }

    public function groups(Page $page, string $locale): array
    {
        $body = $page->translate($locale)?->body;
        if (blank($body)) {
            $body = $this->defaults($page)['body'][$locale] ?? '';
        }
        $html = app(EditorialLinks::class)->html($body, $locale);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $before = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="editorial-body">'.$html.'</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);
        $groups = [];
        foreach ($doc->getElementById('editorial-body')->childNodes as $node) {
            if ($node->nodeName === 'h2') {
                $groups[] = ['heading' => $this->inner($doc, $node), 'body' => ''];
            } else {
                if (! $groups && trim($node->textContent) === '') {
                    continue;
                }
                if (! $groups) {
                    $groups[] = ['heading' => '', 'body' => ''];
                }
                $groups[array_key_last($groups)]['body'] .= $doc->saveHTML($node);
            }
        }
        foreach ($groups as &$group) {
            $group['parts'] = RichText::sections($group['body']);
        }
        unset($group);

        return $groups;
    }

    private function inner(DOMDocument $doc, \DOMNode $node): string
    {
        return implode('', array_map(fn ($child) => $doc->saveHTML($child), iterator_to_array($node->childNodes)));
    }
}
