<?php

namespace App\Services\Site;

use App\Services\RichText;
use Illuminate\Support\Str;

class Seo
{
    public function build(array $data): array
    {
        extract($data);
        $base = rtrim(config('app.url'), '/');
        $url = $site->url($translation->path);
        $title = $translation->seo_title ?: $translation->title.' | '.config('site.name');
        $description = $translation->description ?: ($translation->intro ?: Str::limit(trim(preg_replace('/\s+/u', ' ', strip_tags(RichText::clean($translation->body)))), 160));
        $description = $description ?: $translation->title.' — '.config('site.name');
        $pageType = match ($kind) {
            'project' => 'ImageGallery', 'post' => 'WebPage', 'film' => 'ItemPage', 'page', 'home', 'about', 'services' => 'WebPage', 'contact', 'quote-request' => 'ContactPage', default => 'CollectionPage'
        };
        $graph = [
            ['@type' => 'Organization', '@id' => $base.'/#organization', 'name' => config('site.name'), 'url' => $site->url('/'.$locale.'/'), 'logo' => ['@type' => 'ImageObject', 'url' => $site->url('/assets/logo.png')], 'email' => config('site.email'), 'telephone' => config('site.telephone'), 'address' => ['@type' => 'PostalAddress', 'streetAddress' => config('site.street_address'), 'addressLocality' => config('site.district'), 'addressRegion' => config('site.city'), 'addressCountry' => config('site.country')]],
            ['@type' => 'WebSite', '@id' => $base.'/#website', 'url' => $site->url('/en/'), 'name' => config('site.name'), 'inLanguage' => ['en', 'tr'], 'publisher' => ['@id' => $base.'/#organization']],
        ];
        $page = ['@type' => $pageType, '@id' => $url.'#webpage', 'url' => $url, 'name' => $title, 'description' => $description, 'inLanguage' => $locale, 'isPartOf' => ['@id' => $base.'/#website'], 'publisher' => ['@id' => $base.'/#organization'], 'breadcrumb' => ['@id' => $url.'#breadcrumb']];
        $cover = $model->coverImage(1600);
        if ($cover) {
            $page['primaryImageOfPage'] = ['@type' => 'ImageObject', 'url' => $site->url($cover['src'])];
        }
        $graph[] = $page;
        $graph[] = ['@type' => 'BreadcrumbList', '@id' => $url.'#breadcrumb', 'itemListElement' => collect($crumbs)->filter(fn ($crumb) => $crumb[1])->values()->map(fn ($crumb, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb[0], 'item' => $site->url($crumb[1])])->all()];
        if ($kind === 'project') {
            foreach ($model->galleryImages() as $i => $image) {
                $id = $url.'#photo-'.($i + 1);
                $graph[2]['hasPart'][] = ['@id' => $id];
                $graph[] = ['@type' => 'ImageObject', '@id' => $id, 'contentUrl' => $site->url($image['src']), 'name' => $image['alt'] ?: $translation->title, 'creator' => ['@id' => $base.'/#organization']];
            }
        }
        if ($kind === 'post') {
            $article = ['@type' => 'BlogPosting', '@id' => $url.'#article', 'headline' => $translation->heading ?: $translation->title, 'description' => $description, 'inLanguage' => $locale, 'mainEntityOfPage' => ['@id' => $url.'#webpage'], 'author' => ['@id' => $base.'/#organization'], 'publisher' => ['@id' => $base.'/#organization']];
            if ($model->published_at) {
                $article['datePublished'] = $model->published_at->toIso8601String();
            }
            if ($cover) {
                $article['image'] = $site->url($cover['src']);
            }
            $graph[] = $article;
        }
        if ($kind === 'film' && $model->video_url && $cover && $model->published_at) {
            $video = ['@type' => 'VideoObject', '@id' => $url.'#video', 'name' => $translation->title, 'description' => $translation->intro ?: $description, 'thumbnailUrl' => $site->url($cover['src']), 'uploadDate' => $model->published_at->toIso8601String()];
            $video[$model->driveId() ? 'embedUrl' : 'contentUrl'] = $site->url($model->video_url);
            if ($model->source_data['duration'] ?? null) {
                $video['duration'] = $model->source_data['duration'];
            }
            $graph[] = $video;
        }
        $items = collect()->concat($projects)->concat($films)->concat($posts)->concat($destinations);
        if ($items->isNotEmpty()) {
            $id = $url.'#items';
            $graph[2]['mainEntity'] = ['@id' => $id];
            $graph[] = ['@type' => 'ItemList', '@id' => $id, 'itemListElement' => $items->values()->map(fn ($item, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item->title, 'url' => $site->url($item->publicPath($locale))])->all()];
        }
        if ($kind === 'destination') {
            $placeId = $url.'#place';
            $graph[2]['about'] = ['@id' => $placeId];
            $graph[] = ['@type' => $model->parent_id ? 'Place' : 'Country', '@id' => $placeId, 'name' => $translation->title];
            $faqs = collect(RichText::sections($translation->faq_body))->filter(fn ($part) => $part['heading'] && trim(strip_tags($part['body'])))->map(fn ($part) => ['@type' => 'Question', 'name' => $part['heading'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($part['body']))]])->values()->all();
            if ($faqs) {
                $graph[] = ['@type' => 'FAQPage', '@id' => $url.'#faq', 'mainEntity' => $faqs];
            }
        }

        return compact('title', 'description', 'url') + ['graph' => ['@context' => 'https://schema.org', '@graph' => $graph]];
    }
}
