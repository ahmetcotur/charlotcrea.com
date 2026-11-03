@extends('site.layout')
@section('content')
@php($sections = \App\Services\RichText::sections($translation->body))
<article class="journal-article"><header class="article-opening"><span class="label">{{ $translation->category }}</span><h1>{{ $translation->heading ?: $translation->title }}</h1><p class="article-lead">{{ $translation->intro }}</p><div class="article-meta"><span>{{ config('site.name') }}</span>@if($model->published_at)<time datetime="{{ $model->published_at->toDateString() }}">{{ $model->published_at->locale($locale)->isoFormat('D MMMM YYYY') }}</time>@endif</div></header>
@if($image = $model->coverImage(1600))<figure class="article-image"><img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?: $translation->title }}" fetchpriority="high"><figcaption>{{ __('From the Charlot Crea portfolio.') }}</figcaption></figure>@endif
<div class="article-layout"><aside class="article-toc" aria-label="{{ __('Contents') }}"><span class="label">{{ __('In this article') }}</span>@foreach($sections as $part)@if($part['heading'])<a href="#section-{{ $loop->iteration }}">{{ $part['heading'] }}</a>@endif @endforeach</aside><div class="article-body">
@foreach($sections as $part)<section id="section-{{ $loop->iteration }}">@if($part['heading'])<h2>{{ $part['heading'] }}</h2>@endif{!! $part['body'] !!}</section>@endforeach
</div></div></article>
@if($relatedPosts->isNotEmpty())<section class="section related-articles"><div class="section-head"><h2>{{ __('More articles') }}</h2></div><div class="journal-grid">@foreach($relatedPosts as $post)@include('site.partials.post-card')@endforeach</div></section>@endif
@endsection
