<a class="project-card" href="{{ $project->publicPath($locale) }}" data-country="{{ $project->country }}">
<div class="picture">@if ($image = $project->coverImage())<img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?: $project->title }}" @if($image['width']) width="{{ $image['width'] }}" height="{{ $image['height'] }}" @endif loading="lazy" decoding="async">@endif</div>
<div class="project-info"><div><h3>{{ $project->title }}</h3><p>{{ $project->city }}@if($project->city && $project->country) · @endif{{ $site->countryName($project->country) }}</p></div><span class="arrow">@include('site.partials.icon')</span></div>
</a>
