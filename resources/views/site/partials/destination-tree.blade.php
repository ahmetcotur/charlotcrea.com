<ul class="destination-tree">
@foreach ($nodes as $node)
<li><a href="{{ $node->publicPath($locale) }}">{{ $node->title }}</a>
@php($descendants = $allDestinations->where('parent_id', $node->id))
@if($descendants->isNotEmpty())@include('site.partials.destination-tree', ['nodes' => $descendants])@endif
</li>
@endforeach
</ul>
