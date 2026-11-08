<!doctype html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ config('charlot.indexable') ? 'index,follow' : 'noindex,nofollow' }}">
<link rel="canonical" href="{{ $seo['url'] }}">
<meta property="og:type" content="{{ $kind === 'post' ? 'article' : 'website' }}">
<meta property="og:site_name" content="{{ config('site.name') }}">
<meta property="og:locale" content="{{ $locale === 'tr' ? 'tr_TR' : 'en_US' }}">
<meta property="og:locale:alternate" content="{{ $locale === 'tr' ? 'en_US' : 'tr_TR' }}">
<meta property="og:title" content="{{ $seo['title'] }}"><meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['url'] }}">
<meta property="og:image" content="{{ $site->url(config('site.social_image')) }}">
<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Charlot Crea logo"><meta property="og:image:type" content="image/png">
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}"><meta name="twitter:image" content="{{ $site->url(config('site.social_image')) }}">
@foreach (['en', 'tr', 'x-default'] as $language)
@if ($alternate = $model->publicPath($language === 'x-default' ? 'en' : $language))
<link rel="alternate" hreflang="{{ $language }}" href="{{ $site->url($alternate) }}">
@endif
@endforeach
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
@foreach (['style','brand','portfolio','editorial','work','icons'] as $sheet)
<link rel="stylesheet" href="/{{ $sheet }}.css">
@endforeach
<link rel="stylesheet" href="/site-content.css?v=4">
<script type="application/ld+json">{!! json_encode($seo['graph'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
</head>
<body>
@include('site.partials.header')
<main @class(['destination-page' => $kind === 'destination', 'home-page' => $kind === 'home' && $model->import_key === '/en/'])>
@unless($kind === 'home')
@include('site.partials.breadcrumb')
@endunless
@yield('content')
</main>
@include('site.partials.footer')
@php($isContactPage = $model instanceof \App\Models\Page && in_array($model->import_key, ['/en/contact/', '/en/request-a-quote/', '/tr/iletisim/']))
@foreach (['app','assets/vendor/gsap.min','assets/vendor/ScrollTrigger.min','motion','gallery','site','work','icons','back-to-top'] as $script)
@if($isContactPage && in_array($script, ['app', 'site'])) @continue @endif
<script src="/{{ $script }}.js{{ $script === 'work' ? '?v=3' : '' }}"></script>
@endforeach
@if($isContactPage)<script src="/contact-form.js" defer></script>@endif
@stack('site_scripts')
</body></html>
