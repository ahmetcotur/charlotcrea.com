@extends('customer.layout')
@section('title',$quote->title)
@section('content')
<a class="customer-back" href="{{ route('customer.dashboard') }}">← {{ __('Overview') }}</a><section class="portal-card"><p class="portal-eyebrow">{{ $quote->quote_number }}</p><span class="portal-status">{{ \App\Services\CustomerPortalLabels::status($quote->status) }}</span><h1>{{ $quote->title }}</h1>
<div class="portal-actions"><a class="portal-btn ghost" href="{{ route('customer.quotes.pdf',$quote->id) }}">{{ app()->getLocale()==='tr' ? 'PDF indir' : 'Download PDF' }} ↓</a></div><div class="customer-table"><table><thead><tr><th>{{ __('Description') }}</th><th>{{ __('Quantity') }}</th><th>{{ __('Unit price') }}</th><th>{{ __('Total') }}</th></tr></thead><tbody>@foreach($quote->items as $item)<tr><td><strong>{{ $item->title }}</strong>@if($item->description)<small>{{ $item->description }}</small>@endif</td><td>{{ $item->quantity }}</td><td>{{ number_format((float)$item->unit_price,2) }}</td><td>{{ number_format((float)$item->quantity*(float)$item->unit_price,2) }}</td></tr>@endforeach</tbody></table></div><p class="customer-total">{{ __('Total') }}: <strong>{{ number_format((float)$quote->amount,2) }} {{ $quote->currency }}</strong></p>
@if($quote->valid_until)<p class="portal-note">{{ __('Valid until') }}: {{ $quote->valid_until->format('d.m.Y') }}</p>@endif
@if($quote->scope)<h2>{{ __('Project scope') }}</h2><div class="portal-copy preserve-lines">{{ $quote->scope }}</div>@endif
@if($quote->status==='sent')<div class="portal-actions"><form method="POST" action="{{ route('customer.quotes.accept',$quote->id) }}">@csrf<button class="portal-btn">{{ __('Accept quote') }}</button></form><form method="POST" action="{{ route('customer.quotes.decline',$quote->id) }}">@csrf<button class="portal-btn ghost">{{ __('Decline quote') }}</button></form></div><p class="portal-note">{{ __('Accepting creates your project automatically.') }}</p>@endif
</section>
@endsection
