@extends('public.layout')
@section('title', $quote->title.' · Charlot Crea')
@section('content')
@php($labels = ['draft'=>'Taslak','sent'=>'Gönderildi','accepted'=>'Kabul edildi','declined'=>'Reddedildi','expired'=>'Süresi doldu'])
<div class="portal-card">
<p class="portal-eyebrow">Teklif &middot; {{ $quote->quote_number }}</p>
<span class="portal-status portal-status-{{ $quote->status }}">{{ $labels[$quote->status] ?? $quote->status }}</span>
<h1>{{ $quote->title }}</h1>

<div class="portal-meta">
<div class="portal-meta-row"><span>Müşteri</span><strong>{{ $quote->customer->title }}</strong></div>
@if($quote->valid_until)
<div class="portal-meta-row"><span>Geçerlilik tarihi</span><strong>{{ $quote->valid_until->translatedFormat('d F Y') }}</strong></div>
@endif
</div>

@if($quote->scope)
<div class="portal-copy">{{ $quote->scope }}</div>
@endif

@if($quote->items->isNotEmpty())
<table class="portal-items">
<thead><tr><th>Kalem</th><th>Adet</th><th>Birim fiyat</th><th>Tutar</th></tr></thead>
<tbody>
@foreach($quote->items as $item)
<tr>
<td><strong>{{ $item->title }}</strong>@if($item->description)<br><span class="muted">{{ $item->description }}</span>@endif</td>
<td>{{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }}</td>
<td>{{ number_format((float) $item->unit_price, 2) }} {{ $quote->currency }}</td>
<td>{{ number_format($item->line_total, 2) }} {{ $quote->currency }}</td>
</tr>
@endforeach
</tbody>
<tfoot><tr><td colspan="3">Toplam</td><td>{{ number_format((float) $quote->amount, 2) }} {{ $quote->currency }}</td></tr></tfoot>
</table>
@else
<div class="portal-meta-row"><span>Tutar</span><strong>{{ number_format((float) $quote->amount, 2) }} {{ $quote->currency }}</strong></div>
@endif

@if($actionable)
<div class="portal-actions">
<form method="POST" action="{{ $acceptUrl }}">@csrf<button class="portal-btn" type="submit">Teklifi kabul et</button></form>
<form method="POST" action="{{ $declineUrl }}">@csrf<button class="portal-btn secondary" type="submit">Teklifi reddet</button></form>
</div>
@elseif($quote->status === 'expired')
<p class="portal-note">Bu teklifin geçerlilik süresi doldu. Güncel bir teklif için bizimle iletişime geçebilirsiniz.</p>
@else
<p class="portal-note">Bu teklif için yanıtınız kaydedildi, tekrar işlem yapmanıza gerek yok.</p>
@endif
</div>
@endsection
