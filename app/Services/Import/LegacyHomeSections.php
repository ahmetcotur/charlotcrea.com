<?php

namespace App\Services\Import;

use App\Models\Destination;
use App\Models\Film;
use App\Models\Page;
use App\Models\Project;
use App\Models\Reference;
use App\Services\PageCopy;
use DOMDocument;
use DOMElement;
use DOMXPath;

class LegacyHomeSections
{
    public const HOMES = ['/en/', '/en/home-2/', '/en/home-3/'];

    private const LABELS = ['hero' => 'Açılış / kapak', 'ticker' => 'Kayan yazı', 'showreel' => 'Film seçkisi', 'disciplines' => 'Fotoğraf ve film hizmetleri', 'references' => 'Referanslar', 'projects' => 'Seçili projeler', 'intro' => 'Stüdyo tanıtımı', 'destinations' => 'Destinasyonlar', 'cta' => 'İletişim çağrısı', 'story' => 'Tanıtım / deneyim'];

    public const SOURCES = ['projects' => Project::class, 'showreel' => Film::class, 'destinations' => Destination::class, 'references' => Reference::class];

    public function supports(Page $page): bool
    {
        return in_array($page->import_key, self::HOMES);
    }

    private function parse(string $html): array
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $before = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($before);

        return [$doc, new DOMXPath($doc)];
    }

    private function nodes(DOMXPath $xp): array
    {
        $nodes = [];
        foreach ($xp->query('//main/section | //main/div[contains(@class,"editorial-ticker")]') as $node) {
            $class = $node->getAttribute('class');
            $key = match (true) {
                str_contains($class, 'hero') => 'hero', str_contains($class, 'editorial-ticker') => 'ticker',
                str_contains($class, 'home-showreel') => 'showreel', str_contains($class, 'discipline-section'), str_contains($class, 'about-services') => 'disciplines',
                str_contains($class, 'references-section') => 'references', str_contains($class, 'destinations') => 'destinations',
                str_contains($class, 'intro') => 'intro', str_contains($class, 'cta') => 'cta',
                $xp->query('.//*[contains(@class,"project-grid")]', $node)->length > 0 => 'projects', default => 'story',
            };
            $nodes[$key] = $node;
        }

        return $nodes;
    }

    private function textNodes(DOMXPath $xp, DOMElement $node, string $key): array
    {
        if ($key === 'ticker') {
            return iterator_to_array($xp->query('.//span[not(*)]', $node));
        }
        $query = isset(self::SOURCES[$key])
            ? './/*[contains(@class,"section-head")]//*[self::h2 or self::p or (self::span and @class="label")]'
            : './/*[self::h1 or self::h2 or self::h3 or self::h4 or self::p or self::li or (self::span and @class="label")]';

        return iterator_to_array($xp->query($query, $node));
    }

    private function textValue(DOMElement $node): string
    {
        $copy = $node->cloneNode(true);
        foreach (iterator_to_array($copy->getElementsByTagName('br')) as $br) {
            $br->parentNode->replaceChild($copy->ownerDocument->createTextNode("\n"), $br);
        }

        return trim($copy->textContent);
    }

    public function editor(Page $page): array
    {
        if (! $this->supports($page)) {
            return [];
        }
        $saved = $page->source_data['home_sections'] ?? [];
        $sections = [];
        foreach (['tr', 'en'] as $locale) {
            $translation = $page->translate($locale);
            if (! $translation?->legacy_html) {
                continue;
            }
            [$doc, $xp] = $this->parse($translation->legacy_html);
            PageCopy::apply($doc, $xp, $translation->body);
            if ($translation->heading && ($heading = $xp->query('//main//h1')->item(0))) {
                $this->html($doc, $heading, nl2br(e($translation->heading)));
            }
            if ($translation->intro && ($intro = $xp->query('(//main//h1/following-sibling::p | //main//h1/parent::div/following-sibling::p)[1]')->item(0))) {
                $intro->textContent = $translation->intro;
            }
            foreach ($this->nodes($xp) as $key => $node) {
                $sections[$key] ??= [
                    'label' => self::LABELS[$key], 'visible' => true, 'position' => count($sections) + 1,
                    'mode' => 'automatic', 'limit' => $key === 'references' ? 12 : 4, 'items' => [], 'featured_id' => null,
                ];
                foreach ($this->textNodes($xp, $node, $key) as $index => $text) {
                    $sections[$key]['texts'][$locale][$index] = ['tag' => $text->nodeName, 'value' => $this->textValue($text)];
                }
            }
        }
        foreach ($sections as $key => &$section) {
            $config = $saved[$key] ?? [];
            foreach (['visible', 'position', 'mode', 'limit', 'items', 'featured_id'] as $field) {
                if (array_key_exists($field, $config)) {
                    $section[$field] = $config[$field];
                }
            }
            foreach (array_keys($section['texts'] ?? []) as $locale) {
                foreach ($section['texts'][$locale] as $index => $text) {
                    if (array_key_exists($index, $config['texts'][$locale] ?? [])) {
                        $section['texts'][$locale][$index]['value'] = $config['texts'][$locale][$index];
                    }
                }
            }
        }
        unset($section);
        uasort($sections, fn ($a, $b) => $a['position'] <=> $b['position']);

        return $sections;
    }

    private function html(DOMDocument $doc, \DOMNode $target, string $html): void
    {
        [$fragment] = $this->parse('<div id="section-fragment">'.$html.'</div>');
        $root = $fragment->getElementById('section-fragment');
        while ($target->firstChild) {
            $target->removeChild($target->firstChild);
        }
        foreach (iterator_to_array($root->childNodes) as $child) {
            $target->appendChild($doc->importNode($child, true));
        }
    }
}
