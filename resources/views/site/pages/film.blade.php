@extends('site.layout')
@section('content')
@include('site.partials.intro', ['label' => __('Film')])
<section class="section film-feature">@include('site.partials.film-button', ['film' => $model, 'feature' => true])</section>
<section class="section film-project-links">
@if($photoProject)<a class="text-link" href="{{ $photoProject->publicPath($locale) }}">{{ __('Explore photography') }}: {{ $photoProject->title }} @include('site.partials.icon')</a>@endif
@foreach ($destinations as $destination)<a class="text-link" href="{{ $destination->publicPath($locale) }}">{{ $destination->title }} @include('site.partials.icon')</a>@endforeach
</section>
@include('site.partials.cta')
@include('site.partials.dialogs', ['video' => true])
@endsection
