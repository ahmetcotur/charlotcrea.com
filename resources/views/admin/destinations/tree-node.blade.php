<div class="list-row" style="padding-left:{{ $depth * 22 }}px">
<span><strong>{{ $node->title }}</strong> <span class="badge badge-{{ $node->published ? 'accepted' : 'draft' }}">{{ $node->published ? 'Yayında' : 'Taslak' }}</span></span>
<span style="display:flex;gap:12px">
<a href="{{ route('admin.destinations.create', ['parent_id' => $node->id]) }}" class="muted">+ Alt ekle</a>
<a href="{{ route('admin.destinations.edit',$node) }}" class="muted">Aç →</a>
</span>
</div>
@php($children = $destinations->where('parent_id', $node->id))
@foreach($children as $child)
@include('admin.destinations.tree-node', ['node' => $child, 'destinations' => $destinations, 'depth' => $depth + 1])
@endforeach
