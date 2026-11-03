<label class="destination-picker-item" style="padding-left:{{ $depth * 18 }}px">
<input type="checkbox" name="destinations[]" value="{{ $node->id }}" @checked(in_array($node->id, $selected))>
{{ $node->title }}
</label>
@php($children = $destinations->where('parent_id', $node->id))
@foreach($children as $child)
@include('admin.partials.destination-picker-node', ['node' => $child, 'destinations' => $destinations, 'selected' => $selected, 'depth' => $depth + 1])
@endforeach
