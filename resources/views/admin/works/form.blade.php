@extends('admin.layout')
@section('title',$work->exists ? 'İş' : 'Yeni iş')
@section('content')
@php($statusLabels = ['planned'=>'Planlandı','in_progress'=>'Devam ediyor','on_hold'=>'Beklemede','completed'=>'Tamamlandı','cancelled'=>'İptal edildi'])
@php($revisionLabels = ['requested'=>'Talep edildi','in_review'=>'İnceleniyor','approved'=>'Kabul edildi','rejected'=>'Reddedildi'])
<div class="admin-heading"><div><span class="eyebrow">Prodüksiyon</span><h1>{{ $work->exists ? $work->title : 'Yeni iş' }} @if($work->exists)<span class="badge badge-{{ $work->status }}">{{ $statusLabels[$work->status] ?? $work->status }}</span>@endif</h1><p>İşi bir müşteriyle eşleştirin; varsa kabul edilen teklifini bağlayın.</p></div><a class="button secondary" href="{{ route('admin.works.index') }}">← İşlere dön</a></div>

<div @if($work->exists) x-data="{tab:'{{ request('tab')==='delivery' || old('_delivery_tab') ? 'delivery' : 'details' }}'}" @endif>
@if($work->exists)
<div class="admin-tabs">
<button type="button" :class="{'is-active': tab==='details'}" @click="tab='details'">Detaylar</button>
<button type="button" :class="{'is-active': tab==='history'}" @click="tab='history'">Durum geçmişi</button>
<button type="button" :class="{'is-active': tab==='delivery'}" @click="tab='delivery'">Teslimat &amp; revizyonlar</button>
</div>
@endif

<div @if($work->exists) x-show="tab==='details'" @endif>
<form class="panel" method="POST" action="{{ $work->exists ? route('admin.works.update',$work) : route('admin.works.store') }}">@csrf @if($work->exists)@method('PUT')@endif
<div class="form-grid"><div class="field"><label>İş / prodüksiyon adı *</label><input name="title" required value="{{ old('title',$work->title) }}"></div><div class="field"><label>Müşteri *</label><select name="customer_id" required><option value="">Müşteri seçin</option>@foreach($customers as $customer)<option value="{{ $customer->id }}" @selected(old('customer_id',$work->customer_id)==$customer->id)>{{ $customer->title }}{{ $customer->company_name ? ' · '.$customer->company_name : '' }}</option>@endforeach</select></div><div class="field"><label>Bağlı teklif</label><select name="quote_id"><option value="">Teklif seçmeden devam et</option>@foreach($quotes as $quote)<option value="{{ $quote->id }}" @selected(old('quote_id',$work->quote_id)==$quote->id)>{{ $quote->quote_number }} · {{ $quote->title }} — {{ $quote->customer?->title }}</option>@endforeach</select></div><div class="field"><label>Durum *</label><select name="status">@foreach($statusLabels as $value=>$label)<option value="{{ $value }}" @selected(old('status',$work->status ?: 'planned')===$value)>{{ $label }}</option>@endforeach</select></div><div class="field"><label>Başlangıç</label><input type="date" name="starts_at" value="{{ old('starts_at',$work->starts_at?->format('Y-m-d')) }}"></div><div class="field"><label>Hedef teslim</label><input type="date" name="due_at" value="{{ old('due_at',$work->due_at?->format('Y-m-d')) }}"></div></div>
<div class="field"><label>İş özeti</label><textarea name="brief" rows="5">{{ old('brief',$work->brief) }}</textarea></div><div class="field"><label>Ekip içi notlar</label><textarea name="internal_notes" rows="4">{{ old('internal_notes',$work->internal_notes) }}</textarea></div>
<div class="form-actions">
<button class="button">{{ $work->exists ? 'İşi kaydet' : 'İş oluştur' }}</button>
@if($work->exists)<button class="button danger" type="submit" form="archive-work">İşi arşivle</button>@endif
</div>
</form>
</div>

@if($work->exists)
<div x-show="tab==='history'" x-cloak>
<ul class="timeline">
@forelse($work->statusEvents as $event)
<li class="timeline-item @if(!$event->changed_by) is-customer @endif @if($event->from_status===$event->to_status) is-delivery @endif">
<span class="timeline-dot"></span>
<div class="timeline-row"><span>@if($event->from_status===$event->to_status)<span class="timeline-tag">Teslimat</span>@else{{ $statusLabels[$event->from_status] ?? ($event->from_status ?: 'Yeni kayıt') }} → {{ $statusLabels[$event->to_status] ?? $event->to_status }}@endif</span><span class="muted">{{ $event->created_at->format('d.m.Y H:i') }}</span></div>
@if($event->note)<div class="timeline-note">{{ $event->note }}</div>@endif
</li>
@empty
<p class="muted">Henüz durum değişikliği yok.</p>
@endforelse
</ul>
</div>

<div x-show="tab==='delivery'" x-cloak>
@include('admin.partials.work-delivery-links')
<div class="panel">
<div class="toolbar"><h2 class="panel-title">Teslimat</h2>
<div style="display:flex;gap:10px">
<button type="button" class="button secondary copy-link-btn" x-data="copyLink('{{ route('admin.works.link',$work) }}')" @click="copy()" :disabled="loading"><span x-show="!copied">Bağlantıyı kopyala</span><span x-show="copied" style="display:none">Kopyalandı ✓</span></button>
<button type="button" class="button" @click="$refs.deliverDialog.showModal()">{{ $work->delivered_at ? 'Yeniden teslim et' : 'Teslim et' }}</button>
</div>
</div>
@if($work->delivered_at)
<p class="muted">Son teslimat: {{ $work->delivered_at->format('d.m.Y H:i') }}</p><p>{{ $work->delivery_note }}</p>
<div class="link-row">
<input type="text" readonly value="{{ $customerLink }}" onclick="this.select()">
<a class="button secondary" href="{{ $customerLink }}" target="_blank" rel="noopener">Müşteri sayfasını aç</a>
@if($work->customer->phone)
<a class="button" href="https://wa.me/{{ preg_replace('/\D/','',$work->customer->phone) }}?text={{ urlencode('Merhaba '.$work->customer->title.', '.$work->title.' teslimatını görüntülemek için: '.$customerLink) }}" target="_blank" rel="noopener">WhatsApp'ta gönder</a>
@endif
</div>
@if(!$work->customer->phone)<p class="muted" style="margin-top:8px">WhatsApp ile göndermek için müşteri kaydına telefon numarası ekleyin.</p>@endif
@else
<p class="muted">Henüz teslim edilmedi.</p>
@endif
</div>

<div class="panel">
<h2 class="panel-title">Revizyon talepleri</h2>
@forelse($work->revisionRequests as $revision)
<div class="revision-card">
<div class="timeline-row"><span class="badge badge-{{ $revision->status }}">{{ $revisionLabels[$revision->status] ?? $revision->status }}</span><span class="muted">{{ $revision->requested_at->format('d.m.Y H:i') }}</span></div>
<p>{{ $revision->customer_note }}</p>
@if($revision->admin_response)<p class="muted">Yanıt: {{ $revision->admin_response }}</p>@endif
@if(in_array($revision->status, ['requested','in_review']))
<form method="POST" action="{{ route('admin.revision-requests.update',$revision) }}">
@csrf @method('PUT')
<textarea name="admin_response" placeholder="Müşteriye gidecek yanıt" required></textarea>
<div class="confirm-actions" style="margin-top:10px">
<button type="submit" name="status" value="rejected" class="button secondary">Reddet</button>
<button type="submit" name="status" value="approved" class="button">Kabul et</button>
</div>
</form>
@endif
</div>
@empty
<p class="muted">Bu iş için revizyon talebi yok.</p>
@endforelse
</div>
</div>

<dialog x-ref="deliverDialog" class="confirm-dialog">
<form method="POST" action="{{ route('admin.works.deliver',$work) }}" x-data="{sending:false}" @submit="sending=true">
@csrf
<div class="confirm-body">
<h3>İşi teslim et</h3>
<p>
@if($work->customer->email)
Müşteriye ({{ $work->customer->email }}) teslimat bağlantısını içeren bir e-posta gönderilecek.
@else
Müşterinin e-postası tanımlı değil, e-posta gönderilmeyecek — kaydettikten sonra bağlantıyı WhatsApp ile iletebilirsiniz.
@endif
</p>
<textarea name="delivery_note" required placeholder="Teslimat notu (Drive bağlantılarını yukarıdaki alana ekleyin)" style="width:100%;min-height:90px;border:1px solid #d8ddd3;padding:10px;border-radius:2px;margin-bottom:16px">{{ $work->delivery_note }}</textarea>
<div class="confirm-actions">
<button type="button" class="button secondary" @click="$refs.deliverDialog.close()">Vazgeç</button>
<button type="submit" class="button btn-loading" :disabled="sending"><span x-show="!sending">Teslim et</span><span x-show="sending" style="display:none" class="spinner"></span></button>
</div>
</div>
</form>
</dialog>
@endif
</div>

@if($work->exists)<form id="archive-work" method="POST" action="{{ route('admin.works.destroy',$work) }}" onsubmit="return confirm('İşi arşivlemek istiyor musun?')">@csrf @method('DELETE')</form>@endif
@endsection
