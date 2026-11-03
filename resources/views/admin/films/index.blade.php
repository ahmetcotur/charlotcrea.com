@extends('admin.layout')
@section('title','Filmler')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>Filmler</h1><p>Film kayıtlarını, video bağlantılarını ve destinasyon etiketlerini yönetin.</p></div><a class="button" href="{{ route('admin.films.create') }}">+ Film ekle</a></div>
<form class="toolbar" method="GET"><div class="search"><input name="q" value="{{ request('q') }}" placeholder="Film adı ara"><button class="button secondary">Ara</button></div><span class="muted">{{ $films->total() }} kayıt</span></form>
<div class="table-wrap"><table><thead><tr><th>Kapak</th><th>Film</th><th>Bağlı proje</th><th>Durum</th><th></th></tr></thead><tbody>
@forelse($films as $film)
<tr>
<td>@if($film->hasImage('cover'))<img src="{{ $film->image('cover','default',['w'=>100]) }}" alt="" style="width:70px;height:46px;object-fit:cover;display:block">@else<span class="muted">—</span>@endif</td>
<td><strong>{{ $film->title }}</strong></td>
<td>{{ $film->project?->title ?: '—' }}</td>
<td><span class="badge badge-{{ $film->published ? 'accepted' : 'draft' }}">{{ $film->published ? 'Yayında' : 'Taslak' }}</span></td>
<td><a class="button secondary" href="{{ route('admin.films.edit',$film) }}">Aç</a></td>
</tr>
@empty
<tr><td colspan="5" class="muted">Henüz film yok.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $films->links() }}</div>
@endsection
