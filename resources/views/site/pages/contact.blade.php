@extends('site.layout')
@section('content')
@include('site.partials.intro', ['label' => __('Start a conversation')])
<section class="section contact-details"><div><span class="label">{{ __('Contact details') }}</span><h2>{{ __('Let’s start a conversation.') }}</h2></div><div class="contact-channels">
<div><span class="label">{{ __('Phone') }}</span><a href="tel:{{ preg_replace('/[^+0-9]/', '', config('site.telephone')) }}">{{ config('site.telephone') }}</a></div>
<div><span class="label">{{ __('Email') }}</span><a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a></div>
@if(config('site.whatsapp_number'))<div><span class="label">WhatsApp</span><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('site.whatsapp_number')) }}" target="_blank" rel="noopener noreferrer">{{ config('site.whatsapp_number') }}</a></div>@endif
@if(config('site.address'))<div><span class="label">{{ __('Address') }}</span><address>{{ config('site.address') }}</address>@if($directions = app(\App\Services\SiteSettings::class)->directionsUrl())<a class="text-link" href="{{ $directions }}" target="_blank" rel="noopener noreferrer">{{ __('Get directions') }} @include('site.partials.icon')</a>@endif</div>@endif
</div>
@if($map = app(\App\Services\SiteSettings::class)->mapEmbedUrl($locale))<div class="contact-map"><iframe title="{{ __('Location map') }} — {{ config('site.name') }}" src="{{ $map }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe><p>{{ __('Map shown using an address search.') }}</p></div>@endif
</section>
@include('site.partials.contact-form')
@endsection
