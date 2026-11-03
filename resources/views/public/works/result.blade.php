@extends('public.layout')
@section('title', __('Delivery').' · Charlot Crea')
@section('content')
<div class="portal-card">
@if($action === 'approved')
<p class="portal-result-icon">{{ __('Thank you') }}</p>
<h1>{{ __('You approved the delivery') }}</h1>
<p class="portal-copy">{{ __('Thank you for approving :title.',['title'=>$work->title]) }}</p>
@else
<p class="portal-result-icon">{{ __('Received') }}</p>
<h1>{{ __('Your revision request has been sent') }}</h1>
<p class="portal-copy">{{ __('We received your request and will review it and get back to you.') }}</p>
@endif
</div>
@endsection
