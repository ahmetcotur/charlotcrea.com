@extends('admin.layout')
@section('title','Referanslar')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>Referanslar</h1><p>Müşteri/marka logolarını ve sıralarını yönetin.</p></div><a class="button" href="{{ route('admin.references.create') }}">+ Referans ekle</a></div>
<form class="toolbar" method="GET"><div class="search"><input name="q" value="{{ request('q') }}" placeholder="Referans adı ara"><button class="button secondary">Ara</button></div><span class="muted">{{ $references->total() }} kayıt</span></form>
<div class="table-wrap"><table><thead><tr><th>Logo</th><th>Ad</th><th>Web adresi</th><th>Durum</th><th>Sıra</th><th></th></tr></thead><tbody>
@forelse($references as $reference)
<tr>
<td>@if($reference->hasImage('logo'))<img src="{{ $reference->image('logo','default',['w'=>80]) }}" alt="" style="height:32px;width:auto">@else<span class="muted">—</span>@endif</td>
<td><strong>{{ $reference->title }}</strong></td>
<td>{{ $reference->website_url ?: '—' }}</td>
<td><span class="badge badge-{{ $reference->published ? 'accepted' : 'draft' }}">{{ $reference->published ? 'Yayında' : 'Taslak' }}</span></td>
<td>{{ $reference->position }}</td>
<td><a class="button secondary" href="{{ route('admin.references.edit',$reference) }}">Aç</a></td>
</tr>
@empty
<tr><td colspan="6" class="muted">Henüz referans yok.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $references->links() }}</div>
@endsection
