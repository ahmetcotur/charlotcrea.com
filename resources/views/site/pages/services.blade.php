@extends('site.layout')
@section('content')
@php
$native = app(\App\Services\NativePageData::class);
$defaults = $native->defaults($model);
$groups = $native->groups($model,$locale);
$labels = $defaults['labels'][$locale] ?? [];
$cta = array_pop($groups);
@endphp
@include('site.partials.intro',['label'=>$labels[0] ?? ''])
<section class="section intro"><div><span class="label">{{ $labels[1] ?? '' }}</span></div><div class="bodytext">@foreach($groups as $group)<section class="native-service-copy"><h2 @if($loop->index<2) id="{{ $loop->first?'photography':'film' }}" @endif>@if($loop->index<2)<a href="{{ $site->route($loop->first?'/en/photography/':'/en/film/') }}">{!! $group['heading'] !!}</a>@else{!! $group['heading'] !!}@endif</h2>{!! $group['body'] !!}@if($loop->index<2)<a class="text-link" href="{{ $site->route($loop->first?'/en/photography/':'/en/film/') }}">{{ $loop->first?__('Explore photography'):__('Explore films') }} @include('site.partials.icon')</a>@endif</section>@endforeach</div></section>
@if($cta)<section class="cta"><span class="label">{{ $labels[2] ?? '' }}</span><h2>{!! $cta['heading'] !!}</h2><div class="bodytext">{!! $cta['body'] !!}</div><a class="btn" href="{{ $site->route('/en/request-a-quote/') ?: $site->route('/en/contact/') }}">{{ __('Discuss a project') }} @include('site.partials.icon')</a></section>@endif
@endsection
