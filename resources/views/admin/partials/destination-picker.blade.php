@php($roots = $destinations->where('parent_id', null))
<div class="destination-picker" data-destination-picker>
<input type="text" class="destination-search" placeholder="Ülke, şehir veya çekim noktası ara…" aria-label="Destinasyon ara" data-destination-search>
<div class="destination-chips" data-destination-chips><span class="destination-chips-empty" data-chips-empty>Henüz seçim yok</span></div>
<div class="destination-tree">
@forelse($roots as $root)
<details class="destination-group" open>
<summary>{{ $root->title }}</summary>
<div class="destination-group-body">
@include('admin.partials.destination-picker-node', ['node' => $root, 'destinations' => $destinations, 'selected' => $selected, 'depth' => 0])
</div>
</details>
@empty
<p class="muted">Henüz destinasyon tanımlı değil.</p>
@endforelse
</div>
<p class="muted" data-destination-empty hidden>Aramanıza uygun destinasyon bulunamadı.</p>
<a class="destination-add" href="{{ route('admin.destinations.create') }}" target="_blank" rel="noopener">+ Yeni destinasyon ekle ↗</a>
<p class="help">Yeni destinasyonu ayrı sekmede kaydedin. Bu formu kaydettikten sonra yeniden açarak seçebilirsiniz.</p>
</div>
