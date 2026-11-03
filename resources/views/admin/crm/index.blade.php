@extends('admin.layout')
@section('title','Müşteri İlişkileri')
@section('content')
@php($quoteStatusLabels = ['draft'=>'Taslak','sent'=>'Gönderildi','accepted'=>'Kabul edildi','declined'=>'Reddedildi','expired'=>'Süresi doldu'])
@php($workStatusLabels = ['planned'=>'Planlandı','in_progress'=>'Devam ediyor','on_hold'=>'Beklemede','completed'=>'Tamamlandı','cancelled'=>'İptal edildi'])
@php($revisionLabels = ['requested'=>'Talep edildi','in_review'=>'İnceleniyor'])

<div class="admin-heading"><div><span class="eyebrow">Müşteri ilişkileri</span><h1>Satış ve prodüksiyon panosu</h1><p>Teklifleri ve işleri sürükleyip durumlarını güncelleyin; müşteriler ve açık revizyonları aynı sayfadan takip edin.</p></div></div>

<section class="panel crm-filter-panel" aria-label="CRM filtreleri">
<form method="GET" action="{{ route('admin.crm.index') }}" class="crm-filters">
<label>Görünüm<select name="view"><option value="all" @selected($filters['view']==='all')>Tüm teklif ve işler</option><option value="upcoming" @selected($filters['view']==='upcoming')>Yaklaşan çekimler</option><option value="approved" @selected($filters['view']==='approved')>Onaylanan işler</option></select></label>
<label>İşlerde tarih alanı<select name="date_field">@foreach(['starts_at'=>'Çekim / başlangıç tarihi','due_at'=>'Teslim tarihi','created_at'=>'Kayıt tarihi'] as $field=>$label)<option value="{{ $field }}" @selected($filters['date_field']===$field)>{{ $label }}</option>@endforeach</select></label>
<label>Yıl<select name="year"><option value="">Tüm yıllar</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string)$filters['year']===(string)$year)>{{ $year }}</option>@endforeach</select></label>
<label>Başlangıç<input type="date" name="from" value="{{ $filters['from'] }}"></label>
<label>Bitiş<input type="date" name="to" value="{{ $filters['to'] }}"></label>
<div class="crm-filter-actions"><button class="button" type="submit">Filtrele</button><a class="button secondary" href="{{ route('admin.crm.index') }}">Temizle</a></div>
</form>
<div class="crm-filter-shortcuts"><a href="{{ route('admin.crm.index',['view'=>'upcoming','date_field'=>'starts_at','from'=>today()->toDateString(),'to'=>today()->addDays(30)->toDateString()]) }}">Önümüzdeki 30 gün</a><a href="{{ route('admin.crm.index',['view'=>'approved','year'=>now()->year]) }}">Bu yıl onaylanan işler</a><strong>{{ $worksByStatus->flatten(1)->count() }} iş @if($filters['view']!=='upcoming') · {{ $quotesByStatus->flatten(1)->count() }} teklif @endif</strong></div>
<p class="muted crm-filter-note">İşler seçilen tarih alanına göre süzülür. Tekliflerde gönderim tarihi (yoksa kayıt tarihi), onaylanan görünümünde kabul tarihi kullanılır. Yıl ve tarih aralığı birlikte uygulanır; tarih filtresinde tarihsiz işler gösterilmez. Müşteriler ve açık revizyonlar genel listedir.</p>
</section>

@if($filters['view']!=='upcoming')
<section class="kanban-section">
<div class="kanban-section-head">
<h2>Teklifler</h2>
<div class="links"><a href="{{ route('admin.quotes.index') }}">Liste görünümü →</a><a href="{{ route('admin.quotes.create') }}">+ Teklif oluştur</a></div>
</div>
<div class="kanban-board" data-kanban="quotes" data-status-url="{{ route('admin.quotes.update-status', ['quote' => '__id__']) }}">
@foreach($quoteStatusLabels as $status => $label)
<div class="kanban-column" data-status="{{ $status }}">
<div class="kanban-column-head"><span>{{ $label }}</span><span class="count">{{ $quotesByStatus->get($status, collect())->count() }}</span></div>
<div class="kanban-list">
@forelse($quotesByStatus->get($status, collect()) as $quote)
<a class="kanban-card" draggable="true" data-id="{{ $quote->id }}" data-status="{{ $status }}" href="{{ route('admin.quotes.edit', $quote) }}">
<strong>{{ $quote->title }}</strong>
{{ $quote->customer?->title }}
<small class="muted">{{ $filters['view']==='approved' ? 'Kabul' : 'Gönderim / kayıt' }}: {{ ($filters['view']==='approved' ? ($quote->accepted_at ?? $quote->created_at) : ($quote->sent_at ?? $quote->created_at))?->format('d.m.Y') }}</small><div class="kanban-meta"><span>{{ $quote->quote_number }}</span><span>{{ number_format((float) $quote->amount, 0) }} {{ $quote->currency }}</span></div>
</a>
@empty
<div class="kanban-empty">Boş</div>
@endforelse
</div>
</div>
@endforeach
</div>
</section>

@endif

<section class="kanban-section">
<div class="kanban-section-head">
<h2>{{ ['all'=>'İşler','upcoming'=>'Yaklaşan çekimler','approved'=>'Onaylanan işler'][$filters['view']] }}</h2>
<div class="links"><a href="{{ route('admin.works.index') }}">Liste görünümü →</a><a href="{{ route('admin.works.create') }}">+ İş oluştur</a></div>
</div>
<div class="kanban-board" data-kanban="works" data-status-url="{{ route('admin.works.update-status', ['work' => '__id__']) }}">
@foreach($workStatusLabels as $status => $label)
<div class="kanban-column" data-status="{{ $status }}">
<div class="kanban-column-head"><span>{{ $label }}</span><span class="count">{{ $worksByStatus->get($status, collect())->count() }}</span></div>
<div class="kanban-list">
@forelse($worksByStatus->get($status, collect()) as $work)
<a class="kanban-card" draggable="true" data-id="{{ $work->id }}" data-status="{{ $status }}" href="{{ route('admin.works.edit', $work) }}">
<strong>{{ $work->title }}</strong>
{{ $work->customer?->title }}
<div class="kanban-meta"><span>Çekim: {{ $work->starts_at?->format('d.m.Y') ?: 'Belirlenmedi' }}<br>Teslim: {{ $work->due_at?->format('d.m.Y') ?: 'Belirlenmedi' }}</span>@if($work->delivered_at)<span>Teslim edildi</span>@endif</div>
</a>
@empty
<div class="kanban-empty">Boş</div>
@endforelse
</div>
</div>
@endforeach
</div>
</section>

<div class="content-panels">
<section class="panel">
<div class="toolbar"><h2 class="panel-title">Müşteriler</h2><div style="display:flex;gap:12px"><a class="muted" href="{{ route('admin.customers.create') }}">+ Ekle</a><a class="muted" href="{{ route('admin.customers.index') }}">Tümünü gör →</a></div></div>
@forelse($customers as $customer)
<a class="list-row" href="{{ route('admin.customers.edit', $customer) }}"><span><strong>{{ $customer->title }}</strong><br><small class="muted">{{ $customer->company_name }}</small></span><span class="muted">{{ $customer->quotes_count }} teklif · {{ $customer->works_count }} iş</span></a>
@empty
<p class="muted">Henüz müşteri yok.</p>
@endforelse
</section>

<section class="panel">
<div class="toolbar"><h2 class="panel-title">Açık revizyon talepleri</h2><a class="muted" href="{{ route('admin.revision-requests.index') }}">Tümünü gör →</a></div>
@forelse($revisionRequests as $revision)
<a class="list-row" href="{{ route('admin.works.edit', $revision->work) }}"><span><strong>{{ $revision->work->title }}</strong><br><small class="muted">{{ \Illuminate\Support\Str::limit($revision->customer_note, 60) }}</small></span><span class="badge badge-{{ $revision->status }}">{{ $revisionLabels[$revision->status] ?? $revision->status }}</span></a>
@empty
<p class="muted">Açık revizyon talebi yok.</p>
@endforelse
</section>
</div>
@endsection
