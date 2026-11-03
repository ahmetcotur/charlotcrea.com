@extends('admin.layout')
@section('title','Destinasyonlar')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>Destinasyonlar</h1><p>Ülke / bölge / nokta hiyerarşisini yönetin (en fazla 9 seviye).</p></div><a class="button" href="{{ route('admin.destinations.create') }}">+ Ülke ekle</a></div>
<div class="panel">
@php($roots = $destinations->where('parent_id', null))
@forelse($roots as $root)
@include('admin.destinations.tree-node', ['node' => $root, 'destinations' => $destinations, 'depth' => 0])
@empty
<p class="muted">Henüz destinasyon yok.</p>
@endforelse
</div>
@endsection
