<!doctype html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $translation->seo_title ?: $translation->title.' | Charlot Crea' }}</title>
<meta name="description" content="{{ $translation->description }}">
<meta name="robots" content="noindex,nofollow">
<link rel="canonical" href="{{ rtrim(config('app.url'), '/').$translation->path }}">
@foreach (['en', 'tr', 'x-default'] as $language)
@if ($model->translate($language === 'x-default' ? 'en' : $language)?->path)
<link rel="alternate" hreflang="{{ $language }}" href="{{ rtrim(config('app.url'), '/').$model->translate($language === 'x-default' ? 'en' : $language)->path }}">
@endif
@endforeach
<link rel="stylesheet" href="/style.css"><link rel="stylesheet" href="/work.css">
</head>
<body>
<header><a href="/{{ $locale }}/"><img class="logo" src="/assets/logo.png" alt="Charlot Crea"></a><nav id="nav"><a href="/{{ $locale }}/">{{ $locale === 'tr' ? 'Ana sayfa' : 'Home' }}</a><div class="language-switch">@foreach (['en', 'tr'] as $language)<a hreflang="{{ $language }}" href="{{ $model->translate($language)?->path }}">{{ strtoupper($language) }}</a>@endforeach</div></nav></header>
<main><section class="page-intro"><h1>{{ $translation->heading ?: $translation->title }}</h1><p>{{ $translation->intro }}</p></section>
@if ($model instanceof \App\Models\Project)
<section class="section full-project work-gallery-section"><div class="photo-gallery"></div></section>
<dialog class="gallery-dialog" aria-label="Photo viewer"><button class="gallery-close">×</button><button class="gallery-prev">←</button><img alt=""><button class="gallery-next">→</button><p class="gallery-count" aria-live="polite"></p></dialog>
@elseif ($model instanceof \App\Models\Film)
<section class="section"><button class="film-poster" data-video-src="{{ app(\App\Services\Site\PublicSite::class)->videoUrl($model->video_url) }}">{{ $locale === 'tr' ? 'Filmi izle' : 'Watch film' }}</button></section>
@elseif ($model instanceof \App\Models\Post)
<article class="journal-article"><div class="article-body">{!! \App\Services\RichText::clean($translation->body) !!}</div></article>
@elseif ($model instanceof \App\Models\Destination)
<section class="section destination-story"><div class="destination-topic-grid">{!! \App\Services\RichText::clean($translation->body) !!}</div></section>
@endif
</main><footer>© Charlot Crea</footer><script src="/gallery.js"></script><script src="/work.js"></script>
</body></html>
