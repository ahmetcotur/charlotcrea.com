@extends('admin.layout')
@section('title','Revizyonlar')
@section('content')
@php($labels = ['requested'=>'Talep edildi','in_review'=>'İnceleniyor','approved'=>'Kabul edildi','rejected'=>'Reddedildi'])
<div class="admin-heading"><div><span class="eyebrow">Prodüksiyon</span><h1>Açık revizyon talepleri</h1><p>Müşterilerin teslimatlar üzerinde talep ettiği revizyonları buradan takip edin.</p></div></div>
<div class="table-wrap"><table>
<thead><tr><th>İş</th><th>Müşteri</th><th>Durum</th><th>Talep tarihi</th><th></th></tr></thead>
<tbody>
@forelse($revisionRequests as $revision)
<tr>
<td><strong>{{ $revision->work->title }}</strong><br><small class="muted">{{ \Illuminate\Support\Str::limit($revision->customer_note, 80) }}</small></td>
<td>{{ $revision->work->customer->title }}</td>
<td><span class="badge badge-{{ $revision->status }}">{{ $labels[$revision->status] ?? $revision->status }}</span></td>
<td>{{ $revision->requested_at->format('d.m.Y H:i') }}</td>
<td><a href="{{ route('admin.works.edit',$revision->work) }}">İşi aç →</a></td>
</tr>
@empty
<tr><td colspan="5" class="muted">Açık revizyon talebi yok.</td></tr>
@endforelse
</tbody>
</table></div>
@endsection
