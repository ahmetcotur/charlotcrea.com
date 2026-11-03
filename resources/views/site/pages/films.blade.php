@extends('site.layout')
@section('content')
@include('site.partials.intro', ['label' => __('Film')])
@php($featuredFilm = $films->firstWhere('import_key', '/en/films/charlot-crea-showreel/'))
@if($featuredFilm)<section class="section film-feature">@include('site.partials.film-button', ['film' => $featuredFilm, 'feature' => true])<div class="work-meta"><span>{{ $featuredFilm->title }}</span><a class="text-link" href="{{ $featuredFilm->publicPath($locale) }}">{{ __('Explore the film') }} @include('site.partials.icon')</a></div></section>@endif
<section class="section"><div class="section-head"><h2>{{ __('Selected films') }}</h2></div><div class="film-grid">@foreach($films->reject(fn($film) => $film->id === $featuredFilm?->id) as $film)@include('site.partials.film-card')@endforeach</div></section>
@include('site.partials.portfolio-editorial')
@include('site.partials.cta')
@include('site.partials.dialogs', ['video' => true])
@endsection
