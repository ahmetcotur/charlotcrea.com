<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;

class RichText
{
    // CMS content is text formatting, never executable markup.
    public static function clean(?string $html): string
    {
        $doc = new DOMDocument;
        $before = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div>'.($html ?: '').'</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);
        $root = $doc->getElementsByTagName('div')->item(0);
        self::cleanChildren($root);

        return implode('', array_map(fn ($node) => $doc->saveHTML($node), iterator_to_array($root->childNodes)));
    }

    /** Split formatted CMS text into safe sections without depending on the legacy DOM. */
    public static function sections(?string $html): array
    {
        $clean = self::clean($html);
        $doc = new DOMDocument('1.0', 'UTF-8');
        $before = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="content">'.$clean.'</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);
        $sections = [];
        foreach ($doc->getElementById('content')->childNodes as $node) {
            if ($node instanceof DOMElement && in_array($node->tagName, ['h2', 'h3', 'h4'])) {
                $sections[] = ['heading' => $node->textContent, 'body' => ''];
            } else {
                if (! $sections) {
                    $sections[] = ['heading' => '', 'body' => ''];
                }
                $sections[array_key_last($sections)]['body'] .= $doc->saveHTML($node);
            }
        }

        return array_values(array_filter($sections, fn ($part) => $part['heading'] || trim($part['body'])));
    }

    private static function cleanChildren(DOMNode $root): void
    {
        foreach (iterator_to_array($root->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            if (in_array($node->tagName, ['script', 'style', 'iframe', 'object', 'svg', 'form', 'img'])) {
                $node->parentNode->removeChild($node);

                continue;
            }
            self::cleanChildren($node);
            if (! in_array($node->tagName, ['p', 'h2', 'h3', 'h4', 'strong', 'em', 'b', 'i', 'u', 's', 'br', 'ul', 'ol', 'li', 'blockquote', 'a'])) {
                while ($node->firstChild) {
                    $node->parentNode->insertBefore($node->firstChild, $node);
                }
                $node->parentNode->removeChild($node);

                continue;
            }
            $href = $node->getAttribute('href');
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $node->removeAttribute($attribute->name);
            }
            if ($node->tagName === 'a' && preg_match('~^(?:https?://|/(?!/)|#|mailto:|tel:)~i', trim($href))) {
                $node->setAttribute('href', trim($href));
            }
        }
    }
}
