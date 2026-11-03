@extends('admin.layout')
@section('title','Blog')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>Blog</h1><p>Yazıları, kategorilerini ve yayın tarihlerini yönetin.</p></div><a class="button" href="{{ route('admin.posts.create') }}">+ Yazı ekle</a></div>
<form class="toolbar" method="GET"><div class="search"><input name="q" value="{{ request('q') }}" placeholder="Yazı başlığı ara"><button class="button secondary">Ara</button></div><span class="muted">{{ $posts->total() }} kayıt</span></form>
<div class="table-wrap"><table><thead><tr><th>Yazı</th><th>Kategori</th><th>Yayın tarihi</th><th>Durum</th><th></th></tr></thead><tbody>
@forelse($posts as $post)
<tr>
<td><strong>{{ $post->title }}</strong><br><small class="muted">{{ $post->translate('en')?->path }}</small></td>
<td>{{ $post->translate('en')?->category ?: '—' }}</td>
<td>{{ $post->published_at?->format('d.m.Y') ?: '—' }}</td>
<td><span class="badge badge-{{ $post->published ? 'accepted' : 'draft' }}">{{ $post->published ? 'Yayında' : 'Taslak' }}</span></td>
<td><a class="button secondary" href="{{ route('admin.posts.edit',$post) }}">Aç</a></td>
</tr>
@empty
<tr><td colspan="5" class="muted">Henüz yazı yok.</td></tr>
@endforelse
</tbody></table></div><div class="pagination">{{ $posts->links() }}</div>
@endsection
