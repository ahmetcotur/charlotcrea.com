<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/** Keeps existing page layouts intact while exposing all headings, paragraphs and list copy for editing. */
class PageCopy
{
    public static function extract(string $html): string
    {
        [$document, $xpath] = self::parse($html);
        $main = $xpath->query('//main')->item(0);
        if (! $main) {
            return '';
        }

        $content = [];
        foreach (self::copyNodes($xpath) as $node) {
            $content[] = '<'.$node->nodeName.'>'.self::innerHtml($document, $node).'</'.$node->nodeName.'>';
        }

        return implode("\n", $content);
    }

    public static function apply(DOMDocument $document, DOMXPath $xpath, ?string $html): void
    {
        if (blank($html)) {
            return;
        }

        $copy = new DOMDocument('1.0', 'UTF-8');
        $before = libxml_use_internal_errors(true);
        $copy->loadHTML('<?xml encoding="UTF-8"><div id="copy">'.RichText::clean($html).'</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);
        $sourceNodes = (new DOMXPath($copy))->query('//*[@id="copy"]//*[self::h2 or self::h3 or self::h4 or self::p or self::li]');
        $targetNodes = self::copyNodes($xpath);

        // Keep the approved layout intact while allowing rich inline/block formatting in the editor.
        if ($sourceNodes->length !== count($targetNodes)) {
            return;
        }

        foreach ($targetNodes as $index => $target) {
            if (! $target instanceof DOMElement) {
                continue;
            }
            $source = $sourceNodes->item($index);
            $fragment = new DOMDocument;
            $fragment->loadHTML('<?xml encoding="UTF-8"><div id="copy-fragment">'.self::innerHtml($copy, $source).'</div>', LIBXML_NONET);
            $root = $fragment->getElementById('copy-fragment');
            while ($target->firstChild) {
                $target->removeChild($target->firstChild);
            }
            if ($root) {
                foreach (iterator_to_array($root->childNodes) as $child) {
                    $target->appendChild($document->importNode($child, true));
                }
            }
        }
    }

    /** @return list<DOMElement> */
    private static function copyNodes(DOMXPath $xpath): array
    {
        $main = $xpath->query('//main')->item(0);
        if (! $main) {
            return [];
        }

        $nodes = $xpath->query('//main//*[self::h2 or self::h3 or self::h4 or self::p or self::li]');
        $intro = $xpath->query('(//main//h1/following-sibling::p | //main//h1/parent::div/following-sibling::p)[1]')->item(0);

        return array_values(array_filter(iterator_to_array($nodes), fn ($node) => $node !== $intro));
    }

    private static function innerHtml(DOMDocument $document, DOMNode $node): string
    {
        return implode('', array_map(fn ($child) => $document->saveHTML($child), iterator_to_array($node->childNodes)));
    }

    /** @return array{DOMDocument, DOMXPath} */
    private static function parse(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $before = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);

        return [$document, new DOMXPath($document)];
    }
}
