@extends('admin.layout')
@section('title','Portföy')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>Portföy</h1><p>Fotoğraf projelerini, görsellerini ve destinasyon etiketlerini yönetin.</p></div><a class="button" href="{{ route('admin.projects.create') }}">+ Proje ekle</a></div>
<form class="toolbar" method="GET"><div class="search"><input name="q" value="{{ request('q') }}" placeholder="Proje adı ara"><button class="button secondary">Ara</button></div><span class="muted">{{ $projects->total() }} kayıt</span></form>
<div class="table-wrap"><table><thead><tr><th>Kapak</th><th>Proje</th><th>Ülke / Şehir</th><th>Durum</th><th></th></tr></thead><tbody>
@forelse($projects as $project)
<tr>
<td>@if($project->hasImage('cover'))<img src="{{ $project->image('cover','default',['w'=>100]) }}" alt="" style="width:70px;height:46px;object-fit:cover;display:block">@else<span class="muted">—</span>@endif</td>
<td><strong>{{ $project->title }}</strong></td>
<td>{{ $project->country }}{{ $project->city ? ' · '.$project->city : '' }}</td>
<td><span class="badge badge-{{ $project->published ? 'accepted' : 'draft' }}">{{ $project->published ? 'Yayında' : 'Taslak' }}</span></td>
<td><a class="button secondary" href="{{ route('admin.projects.edit',$project) }}">Aç</a></td>
</tr>
@empty
<tr><td colspan="5" class="muted">Henüz proje yok.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $projects->links() }}</div>
@endsection
