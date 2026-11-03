@foreach($items as $item)
@if($key === 'projects')
@include('site.partials.project-card', ['project' => $item])
@elseif($key === 'showreel')
@include('site.partials.film-card', ['film' => $item])
@elseif($key === 'references')
@php
$logo = $item->imageAsArray('logo', 'default', ['w' => 500]);
@endphp
<div class="reference-logo" data-logo-theme="{{ $item->logo_theme }}">
@if($item->website_url)<a href="{{ $item->website_url }}" target="_blank" rel="noopener noreferrer">@endif
@if($logo)<img src="{{ $logo['src'] }}" alt="{{ $item->title }}" loading="lazy" decoding="async">@endif
<span>{{ $item->title }}</span>
@if($item->website_url)</a>@endif
</div>
@elseif($key === 'destinations')
@php
$locationNames = $item->children()->forPublic($locale)->with('translations')->orderBy('position')->orderBy('id')->get()->map(fn($child) => $child->translate($locale)->title);
$locationSummary = $locationNames->isNotEmpty() ? $locationNames->take(3)->join(' · ').($locationNames->count() > 3 ? ' +'.($locationNames->count()-3) : '') : $item->parent?->translate($locale)?->title;
@endphp
<a class="destination-row" href="{{ $item->publicPath($locale) }}"><span class="label">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $item->title }}</h3><p>{{ $locationSummary }}</p><span class="arrow">@include('site.partials.icon')</span></a>
@endif
@endforeach
