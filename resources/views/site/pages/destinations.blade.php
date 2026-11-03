@extends('site.layout')
@section('content')
@include('site.partials.intro', ['label' => __('A place. A point of view.')])
@php($allDestinations = $site->catalog(\App\Models\Destination::class))
<section class="section destination-index-grid">
@foreach($destinations as $destination)
@php($destinationData = $site->data($destination, $locale))
@php($image = $destination->coverImage() ?: $destinationData['projects']->first()?->coverImage())
<article class="destination-country-card">@if($image)<a href="{{ $destination->publicPath($locale) }}" class="destination-cover"><img src="{{ $image['src'] }}" alt="{{ $destination->title }}" loading="lazy" decoding="async"></a>@endif<div class="section-head"><h2><a href="{{ $destination->publicPath($locale) }}">{{ $destination->title }} @include('site.partials.icon')</a></h2></div><p>{{ $destinationData['projects']->count() }} {{ __('photography collections') }} · {{ $destinationData['films']->count() }} {{ __('films') }}</p>
@include('site.partials.destination-tree', ['nodes' => $allDestinations->where('parent_id', $destination->id)])
</article>
@endforeach
</section>
@include('site.partials.portfolio-editorial')
@include('site.partials.cta')
@endsection
