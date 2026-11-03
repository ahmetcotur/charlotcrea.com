@extends('admin.layout')
@section('title',$quote->exists ? 'Teklif' : 'Yeni teklif')
@section('content')
@if($quote->exists && $quote->works->isNotEmpty())
<div class="notice">Bu teklife bağlı iş:
@foreach($quote->works as $linkedWork)
<a href="{{ route('admin.works.edit', $linkedWork) }}" style="text-decoration:underline">{{ $linkedWork->title }} →</a>
@endforeach
</div>
@endif
@php($statusLabels = ['draft'=>'Taslak','sent'=>'Gönderildi','accepted'=>'Kabul edildi','declined'=>'Reddedildi','expired'=>'Süresi doldu'])
<div class="admin-heading"><div><span class="eyebrow">Satış süreci</span><h1>{{ $quote->exists ? $quote->title : 'Yeni teklif' }} @if($quote->exists)<span class="badge badge-{{ $quote->status }}">{{ $statusLabels[$quote->status] ?? $quote->status }}</span>@endif</h1><p>Müşteriyi seçin; kalemleri ve birim fiyatları girin, toplam otomatik hesaplanır.</p></div><a class="button secondary" href="{{ route('admin.quotes.index') }}">← Tekliflere dön</a></div>

@if($quote->exists)
<div class="panel">
<h2 class="panel-title">Müşteriye ilet</h2>
<div class="link-row">
<input type="text" readonly value="{{ $customerLink }}" onclick="this.select()">
<button type="button" class="button secondary copy-link-btn" x-data="copyLink('{{ route('admin.quotes.link',$quote) }}')" @click="copy()" :disabled="loading"><span x-show="!copied">Kopyala</span><span x-show="copied" style="display:none">Kopyalandı ✓</span></button>
<a class="button secondary" href="{{ $customerLink }}" target="_blank" rel="noopener">Müşteri sayfasını aç</a>
@if($quote->customer->phone)
<a class="button" href="https://wa.me/{{ preg_replace('/\D/','',$quote->customer->phone) }}?text={{ urlencode('Merhaba '.$quote->customer->title.', '.$quote->title.' teklifinizi görüntülemek için: '.$customerLink) }}" target="_blank" rel="noopener">WhatsApp'ta gönder</a>
@endif
</div>
@if(!$quote->customer->phone)<p class="muted" style="margin-top:8px">WhatsApp ile göndermek için müşteri kaydına telefon numarası ekleyin.</p>@endif
</div>
@endif

<div @if($quote->exists) x-data="{tab:'details'}" @endif>
@if($quote->exists)
<div class="admin-tabs">
<button type="button" :class="{'is-active': tab==='details'}" @click="tab='details'">Detaylar</button>
<button type="button" :class="{'is-active': tab==='history'}" @click="tab='history'">Durum geçmişi</button>
</div>
@endif

<div @if($quote->exists) x-show="tab==='details'" @endif>
<form class="panel" method="POST" action="{{ $quote->exists ? route('admin.quotes.update',$quote) : route('admin.quotes.store') }}" x-data="{
    items: @js(collect($defaultItems)->map(fn ($i) => ['title' => $i['title'], 'description' => $i['description'] ?? '', 'quantity' => (float) $i['quantity'], 'unit_price' => (float) $i['unit_price']])->all()),
    currency: @js(old('currency', $quote->currency ?: 'EUR')),
    get total() { return this.items.reduce((sum, i) => sum + ((Number(i.quantity) || 0) * (Number(i.unit_price) || 0)), 0) },
    money(n) { return (Number(n) || 0).toLocaleString('tr-TR', {minimumFractionDigits: 2, maximumFractionDigits: 2}) },
    addItem() { this.items.push({title: '', description: '', quantity: 1, unit_price: 0}) },
    removeItem(i) { this.items.splice(i, 1) }
}">@csrf @if($quote->exists)@method('PUT')@endif
<div class="form-grid"><div class="field"><label>Teklif başlığı *</label><input name="title" required value="{{ old('title',$quote->title) }}"></div><div class="field"><label>Müşteri *</label><select name="customer_id" required><option value="">Müşteri seçin</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id',$quote->customer_id)==$customer->id)>{{ $customer->title }}{{ $customer->company_name ? ' · '.$customer->company_name : '' }}</option>@endforeach</select><div class="help"><a href="{{ route('admin.customers.create') }}">Yeni müşteri ekle →</a></div></div><div class="field"><label>Teklif numarası</label><input name="quote_number" value="{{ old('quote_number',$quote->quote_number) }}" placeholder="Boş bırakılırsa otomatik oluşturulur"></div><div class="field"><label>Durum *</label><select name="status" required>@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(old('status',$quote->status ?: 'draft')===$value)>{{ $label }}</option>@endforeach</select></div><div class="field"><label>Para birimi *</label><select name="currency" x-model="currency">@foreach(['EUR','USD','TRY'] as $currency)<option @selected(old('currency',$quote->currency ?: 'EUR')===$currency)>{{ $currency }}</option>@endforeach</select></div><div class="field"><label>Son geçerlilik tarihi</label><input type="date" name="valid_until" value="{{ old('valid_until',$quote->valid_until?->format('Y-m-d')) }}"></div></div>

<div class="field">
<label>Teklif kalemleri *</label>
<div class="items-table">
<div class="items-row items-head"><div>Kalem</div><div>Adet</div><div>Birim fiyat</div><div>Tutar</div><div></div></div>
<template x-for="(item, index) in items" :key="index">
<div class="items-row">
<div class="items-cell items-cell-title">
<input type="text" :name="'items['+index+'][title]'" x-model="item.title" placeholder="Kalem adı (örn. Düğün günü fotoğraf çekimi)" required>
<input type="text" :name="'items['+index+'][description]'" x-model="item.description" class="items-desc" placeholder="Açıklama (opsiyonel)">
</div>
<div class="items-cell"><input type="number" min="0.01" step="0.01" :name="'items['+index+'][quantity]'" x-model.number="item.quantity" required></div>
<div class="items-cell"><input type="number" min="0" step="0.01" :name="'items['+index+'][unit_price]'" x-model.number="item.unit_price" required></div>
<div class="items-cell items-cell-total" x-text="money(item.quantity * item.unit_price)"></div>
<button type="button" class="items-remove" @click="removeItem(index)" x-show="items.length > 1" title="Kalemi sil">×</button>
</div>
</template>
<div class="items-row items-foot">
<button type="button" class="button secondary" @click="addItem()">+ Kalem ekle</button>
<div class="items-total">Toplam: <strong x-text="money(total)"></strong> <span x-text="currency"></span></div>
</div>
</div>
</div>

<div class="field"><label>Teklif kapsamı</label><textarea name="scope" rows="6">{{ old('scope',$quote->scope) }}</textarea></div><div class="field"><label>Ekip içi notlar</label><textarea name="internal_notes" rows="4">{{ old('internal_notes',$quote->internal_notes) }}</textarea></div>
<div class="form-actions">
<div style="display:flex;gap:10px;flex-wrap:wrap">
<button class="button">{{ $quote->exists ? 'Teklifi kaydet' : 'Teklif oluştur' }}</button>
@if($quote->exists)
<button type="button" class="button" @click="$refs.sendDialog.showModal()" @if(!$canSend) disabled title="Teklifin geçerlilik tarihi geçmiş" @endif>Durumu "Gönderildi" yap</button>
@endif
</div>
@if($quote->exists)<button class="button danger" type="submit" form="archive-quote">Teklifi arşivle</button>@endif
</div>
</form>
</div>

@if($quote->exists)
<div x-show="tab==='history'" x-cloak>
<ul class="timeline">
@forelse($quote->statusEvents as $event)
<li class="timeline-item @if(!$event->changed_by) is-customer @endif">
<span class="timeline-dot"></span>
<div class="timeline-row"><span>{{ $statusLabels[$event->from_status] ?? ($event->from_status ?: 'Yeni kayıt') }} → {{ $statusLabels[$event->to_status] ?? $event->to_status }}</span><span class="muted">{{ $event->created_at->format('d.m.Y H:i') }}</span></div>
@if($event->note)<div class="timeline-note">{{ $event->note }}</div>@endif
</li>
@empty
<p class="muted">Henüz durum değişikliği yok.</p>
@endforelse
</ul>
</div>

<dialog x-ref="sendDialog" class="confirm-dialog">
<form method="POST" action="{{ route('admin.quotes.send',$quote) }}" x-data="{sending:false}" @submit="sending=true">
@csrf
<div class="confirm-body">
<h3>Teklifi gönder</h3>
<p>Teklif "Gönderildi" olarak işaretlenecek.
@if($quote->customer->email)
{{ $quote->customer->title }} adlı müşteriye ({{ $quote->customer->email }}) ayrıca e-posta gönderilecek.
@else
Müşterinin e-postası tanımlı değil, e-posta gönderilmeyecek — bağlantıyı yukarıdaki panelden WhatsApp ile iletebilirsiniz.
@endif
</p>
<div class="confirm-actions">
<button type="button" class="button secondary" @click="$refs.sendDialog.close()">Vazgeç</button>
<button type="submit" class="button btn-loading" :disabled="sending"><span x-show="!sending">Gönder</span><span x-show="sending" style="display:none" class="spinner"></span></button>
</div>
</div>
</form>
</dialog>
@endif
</div>

@if($quote->exists)<form id="archive-quote" method="POST" action="{{ route('admin.quotes.destroy',$quote) }}" onsubmit="return confirm('Teklifi arşivlemek istiyor musun?')">@csrf @method('DELETE')</form>@endif
@endsection
