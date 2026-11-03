@extends('public.layout')
@section('title', $work->title.' · Charlot Crea')
@section('content')
@php($labels = ['planned'=>'Planlandı','in_progress'=>'Devam ediyor','on_hold'=>'Beklemede','completed'=>'Tamamlandı','cancelled'=>'İptal edildi'])
<div class="portal-card">
<p class="portal-eyebrow">Teslimat</p>
<span class="portal-status portal-status-{{ $work->status }}">{{ $labels[$work->status] ?? $work->status }}</span>
<h1>{{ $work->title }}</h1>

<div class="portal-meta">
<div class="portal-meta-row"><span>Müşteri</span><strong>{{ $work->customer->title }}</strong></div>
@if($work->delivered_at)
<div class="portal-meta-row"><span>Teslim tarihi</span><strong>{{ $work->delivered_at->locale('tr')->translatedFormat('d F Y') }}</strong></div>
@endif
</div>

@if($work->delivery_note)
<div class="portal-copy">{{ $work->delivery_note }}</div>
@endif

@if($work->delivered_at && $work->status !== 'cancelled' && $work->published)
@if($work->customer->account && $work->deliveryLinks->isNotEmpty())
<p class="portal-note">Google Drive teslimat bağlantılarınızı müşteri panelinize giriş yaparak açabilirsiniz.</p><a class="portal-btn" href="{{ route('customer.works.show', $work->id) }}">Müşteri panelini aç</a>
@else
<div class="portal-files">@foreach($work->deliveryLinks as $link)
<a class="portal-file" href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"><strong>{{ $link->title }}</strong><span>Google Drive'da aç ↗</span></a>
@endforeach</div>
@endif
@endif
@if($openRevision)
@php($revLabels = ['requested'=>'Talep edildi','in_review'=>'İnceleniyor'])
<p class="portal-note"><span class="portal-status portal-status-{{ $openRevision->status }}">{{ $revLabels[$openRevision->status] ?? $openRevision->status }}</span><br>Revizyon talebiniz değerlendiriliyor, sonucu size e-posta ile bildireceğiz.</p>
@elseif($actionable)
<div class="portal-actions">
<form method="POST" action="{{ $approveUrl }}">@csrf<button class="portal-btn" type="submit">Teslimatı onayla</button></form>
</div>
<form class="portal-field" method="POST" action="{{ $revisionUrl }}">
@csrf
<label for="customer_note">Revizyon talep et</label>
<textarea name="customer_note" id="customer_note" required placeholder="Lütfen talep ettiğiniz değişiklikleri açıklayın"></textarea>
<div class="portal-actions"><button class="portal-btn ghost" type="submit">Revizyon talebi gönder</button></div>
</form>
@elseif(! $work->delivered_at)
<p class="portal-note">Bu iş henüz teslim edilmedi.</p>
@else
<p class="portal-note">Bu teslimat için yanıtınız kaydedildi.</p>
@endif
</div>
@endsection
