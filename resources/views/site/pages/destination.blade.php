@extends('site.layout')
@section('content')
<section class="destination-opening"><span class="destination-bg-text" aria-hidden="true">{{ $translation->background ?: mb_strtoupper($translation->title) }}</span><div class="destination-opening-content"><span class="label">{{ $parent?->title ?? __('Photography / Destinations') }}</span><h1>{{ $translation->heading ?: $translation->title }}</h1><p>{{ $translation->intro }}</p></div></section>
@if($children->isNotEmpty())
<section class="section city-navigation"><span class="label">{{ __('Explore the region') }}</span><div class="city-link-grid">@foreach($children as $child)<a href="{{ $child->publicPath($locale) }}"><span>{{ $child->title }}</span><span>@include('site.partials.icon')</span></a>@endforeach</div></section>
@endif
@if($projects->isNotEmpty())<section class="section destination-work"><div class="section-head"><div><span class="label">{{ $translation->title }}</span><h2>{{ __('Photography collections') }}</h2></div><span class="work-count">{{ $projects->count() }} {{ __('collections') }}</span></div><div class="project-grid">@foreach($projects as $project)@include('site.partials.project-card')@endforeach</div></section>@endif
@if($films->isNotEmpty())<section class="section"><div class="section-head"><h2>{{ __('Selected films') }}</h2></div><div class="film-grid">@foreach($films as $film)@include('site.partials.film-card')@endforeach</div></section>@endif
@if($translation->body || $translation->story_intro || $translation->faq_body)
<section class="section destination-story">
@if($translation->story_heading || $translation->story_intro)<div class="destination-story-intro"><div><span class="label">{{ __('Place & perspective') }}</span><h2>{{ $translation->story_heading }}</h2></div><p>{{ $translation->story_intro }}</p></div>@endif
<div class="destination-topic-grid">@foreach(\App\Services\RichText::sections(app(\App\Services\EditorialLinks::class)->html($translation->body, $locale)) as $part)<article><span class="label">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>@if($part['heading'])<h3>{{ $part['heading'] }}</h3>@endif{!! $part['body'] !!}</article>@endforeach</div>
@if($translation->faq_body)<div class="destination-faq"><h2>{{ __('Questions & answers') }}</h2>@foreach(\App\Services\RichText::sections($translation->faq_body) as $part)@if($part['heading'])<details><summary>{{ $part['heading'] }}</summary>{!! $part['body'] !!}</details>@endif @endforeach</div>@endif
</section>
@endif
@include('site.partials.cta')
@include('site.partials.dialogs', ['video' => $films->isNotEmpty()])
@endsection
