<?php

namespace App\Services;

use DOMDocument;
use DOMXPath;

class EditorialHeading
{
    public static function read(string $html): array
    {
        $doc = new DOMDocument;
        $before = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);
        $node = (new DOMXPath($doc))->query('//h1')->item(0);
        if (! $node) {
            return ['', ''];
        }
        $original = trim($node->textContent);
        foreach (iterator_to_array($node->getElementsByTagName('br')) as $br) {
            $br->parentNode->replaceChild($doc->createTextNode("\n"), $br);
        }

        return [$original, trim($node->textContent)];
    }
}
