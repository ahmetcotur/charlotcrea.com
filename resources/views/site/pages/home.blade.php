@extends('site.layout')
@section('content')
@php
$homeManager = app(\App\Services\HomeSections::class);
$homeSections = $homeManager->visible($model);
$blueprint = app(\App\Services\NativePageData::class)->defaults($model);
$variant = $blueprint['variant'] ?? 'classic';
@endphp
@if(!isset($homeSections['hero']))<h1 class="native-visually-hidden">{{ $translation->heading ?: $translation->title }}</h1>@endif
@foreach($homeSections as $sectionKey => $section)
@php
$plain = fn(int $index) => $section['texts'][$locale][$index]['value'] ?? '';
$text = fn(int $index) => nl2br(e($plain($index)));
$items = $homeManager->items($model, $sectionKey, $locale);
@endphp
@include('site.home.'.$sectionKey)
@endforeach
@include('site.partials.dialogs', ['gallery' => $variant === 'filmstrip', 'video' => true])
@endsection
@push('site_scripts')
<script src="/home.js"></script>
@if(($variant ?? null) === 'filmstrip')<script src="/hero-filmstrip.js"></script>@endif
@if(($variant ?? null) === 'video')<script src="/hero-video.js?v=1"></script>@endif
@endpush
