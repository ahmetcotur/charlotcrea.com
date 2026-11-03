@extends('site.layout')
@section('content')
@include('site.partials.intro', ['label' => __('A shared perspective')])
<section class="section" style="padding-top:10px"><div class="reference-grid full">
@foreach(\App\Models\Reference::published()->whereHas('translations', fn($q) => $q->where('locale', $locale)->where('active', true))->with(['translations','medias'])->orderBy('position')->orderBy('id')->get() as $reference)
@php($logo = $reference->imageAsArray('logo', 'default', ['w' => 500]))
<div class="reference-logo" data-logo-theme="{{ $reference->logo_theme }}">
@if($reference->website_url)<a href="{{ $reference->website_url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ $reference->title }}">@endif
@if($logo)<img src="{{ $logo['src'] }}" alt="{{ $logo['alt'] ?: $reference->title }}" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" loading="lazy" decoding="async">@endif
<span>{{ $reference->title }}</span>
@if($reference->website_url)</a>@endif
@if($reference->description)<p>{{ $reference->description }}</p>@endif
</div>
@endforeach
</div><div class="reference-bottom"><p>{{ __('Explore the properties and perspectives behind the portfolio.') }}</p>@if($path = $site->route('/en/photography/'))<a class="text-link" href="{{ $path }}">{{ __('Discover selected work') }} @include('site.partials.icon')</a>@endif</div></section>
@include('site.partials.cta')
@endsection
