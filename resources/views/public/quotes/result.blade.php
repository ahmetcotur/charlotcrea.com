@extends('public.layout')
@section('title', __('Quote').' · Charlot Crea')
@section('content')
<div class="portal-card">
@if($quote->status === 'accepted')
<p class="portal-result-icon">{{ __('Thank you') }}</p>
<h1>{{ __('You accepted the quote') }}</h1>
<p class="portal-copy">{{ __('Thank you for accepting :title. Our team will contact you soon.',['title'=>$quote->title]) }}</p>
@elseif($quote->status === 'declined')
<p class="portal-result-icon">{{ __('Recorded') }}</p>
<h1>{{ __('You declined the quote') }}</h1>
<p class="portal-copy">{{ __('We received your feedback. Contact us if you would like to discuss the quote.') }}</p>
@else
<h1>{{ $quote->title }}</h1>
<p class="portal-copy">{{ __('Your response to this quote has already been recorded.') }}</p>
@endif
</div>
@endsection
