@extends('site.layout')
@section('content')
@php($images = $model->galleryImages())
@include('site.partials.intro', ['label' => $model->city, 'meta' => '<span>'.e(__('Photography')).'</span><span>'.count($images).' '.e(__('photographs')).'</span>'])
@include('site.partials.project-credit')
<section class="section full-project work-gallery-section"><div class="photo-gallery" aria-label="{{ $translation->title }}">
@foreach ($images as $image)
<a class="gallery-open" data-gallery="{{ $loop->index }}" href="{{ $image['src'] }}" aria-label="{{ $translation->title }} {{ $loop->iteration }}"><img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?: $translation->title }}" @if($image['width'] ?? null) width="{{ $image['width'] }}" height="{{ $image['height'] }}" @endif loading="lazy" decoding="async"></a>
@endforeach
</div></section>
@if ($films->isNotEmpty())<section class="section"><div class="section-head"><h2>{{ __('Selected films') }}</h2></div><div class="film-grid">@foreach($films as $film)@include('site.partials.film-card')@endforeach</div></section>@endif
<section class="section project-navigation">@foreach ($destinations as $destination)<a class="text-link" href="{{ $destination->publicPath($locale) }}">{{ $destination->title }} @include('site.partials.icon')</a>@endforeach</section>
@include('site.partials.cta')
@include('site.partials.dialogs', ['gallery' => true, 'video' => $films->isNotEmpty()])
@endsection
