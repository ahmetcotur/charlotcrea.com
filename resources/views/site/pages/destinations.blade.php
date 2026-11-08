@extends('site.layout')
@section('content')
@include('site.partials.intro', ['label' => __('A place. A point of view.')])
@php($allDestinations = $site->catalog(\App\Models\Destination::class))
<section class="section destination-index-grid">
@foreach($destinations as $destination)
@php($destinationData = $site->data($destination, $locale))
@php($collage = $site->destinationCollage($destination, $destinationData['projects']))
<article class="destination-country-card">
@if($collage)
<a href="{{ $destination->publicPath($locale) }}" class="destination-cover destination-collage destination-collage--{{ count($collage) }}" aria-label="{{ $destination->title }}">
@foreach($collage as $image)
<span class="destination-collage-tile"><img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?: $destination->title }}" loading="lazy" decoding="async"></span>
@endforeach
</a>
@endif
<div class="section-head"><h2><a href="{{ $destination->publicPath($locale) }}">{{ $destination->title }} @include('site.partials.icon')</a></h2></div>
<p>{{ $destinationData['projects']->count() }} {{ __('photography collections') }} · {{ $destinationData['films']->count() }} {{ __('films') }}</p>
<ul class="destination-locations">
@foreach($allDestinations->filter(fn ($node) => $node->_lft > $destination->_lft && $node->_rgt < $destination->_rgt) as $location)
<li><a href="{{ $location->publicPath($locale) }}">{{ $location->title }}</a></li>
@endforeach
</ul>
</article>
@endforeach
</section>
@include('site.partials.portfolio-editorial')
@include('site.partials.cta')
@endsection
