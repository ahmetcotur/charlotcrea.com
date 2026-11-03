@extends('public.layout')
@section('title', $work->title.' · Charlot Crea')
@section('content')
@php($labels = ['planned'=>__('Planned'),'in_progress'=>__('In progress'),'on_hold'=>__('On hold'),'completed'=>__('Completed'),'cancelled'=>__('Cancelled')])
<div class="portal-card">
<p class="portal-eyebrow">{{ __("Delivery") }}</p>
<span class="portal-status portal-status-{{ $work->status }}">{{ $labels[$work->status] ?? $work->status }}</span>
<h1>{{ $work->title }}</h1>

<div class="portal-meta">
<div class="portal-meta-row"><span>{{ __("Customer") }}</span><strong>{{ $work->customer->title }}</strong></div>
@if($work->delivered_at)
<div class="portal-meta-row"><span>{{ __("Delivery date") }}</span><strong>{{ $work->delivered_at->locale(app()->getLocale())->translatedFormat('d F Y') }}</strong></div>
@endif
</div>

@if($work->delivery_note)
<div class="portal-copy">{{ $work->delivery_note }}</div>
@endif

@if($work->delivered_at && $work->status !== 'cancelled' && $work->published)
@if($work->customer->account && $work->deliveryLinks->isNotEmpty())
<p class="portal-note">{{ __("Sign in to your client portal to open your Google Drive delivery links.") }}</p><a class="portal-btn" href="{{ route('customer.works.show', ['id'=>$work->id,'lang'=>app()->getLocale()]) }}">{{ __("Open client portal") }}</a>
@else
<div class="portal-files">@foreach($work->deliveryLinks as $link)
<a class="portal-file" href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"><strong>{{ $link->title }}</strong><span>{{ __("Open in Google Drive") }} ↗</span></a>
@endforeach</div>
@endif
@endif
@if($openRevision)
@php($revLabels = ['requested'=>__('Requested'),'in_review'=>__('In review')])
<p class="portal-note"><span class="portal-status portal-status-{{ $openRevision->status }}">{{ $revLabels[$openRevision->status] ?? $openRevision->status }}</span><br>{{ __("Your revision request is being reviewed. We will notify you by email.") }}</p>
@elseif($actionable)
<div class="portal-actions">
<form method="POST" action="{{ $approveUrl }}">@csrf<button class="portal-btn" type="submit">{{ __("Approve delivery") }}</button></form>
</div>
<form class="portal-field" method="POST" action="{{ $revisionUrl }}">
@csrf
<label for="customer_note">{{ __("Request a revision") }}</label>
<textarea name="customer_note" id="customer_note" required placeholder="{{ __("Please describe the changes you would like.") }}"></textarea>
<div class="portal-actions"><button class="portal-btn ghost" type="submit">{{ __("Send revision request") }}</button></div>
</form>
@elseif(! $work->delivered_at)
<p class="portal-note">{{ __("This project has not been delivered yet.") }}</p>
@else
<p class="portal-note">{{ __("Your response to this delivery has been recorded.") }}</p>
@endif
</div>
@endsection
