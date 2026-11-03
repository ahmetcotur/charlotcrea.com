@if ($film->video_url)
<button type="button" class="film-poster {{ $feature ?? false ? 'film-feature-poster' : '' }}" @if($driveId = $film->driveId()) data-drive-id="{{ $driveId }}" @else data-video-src="{{ $film->video_url }}" @endif data-film-title="{{ $film->title }}" aria-label="{{ __('Play film') }}: {{ $film->title }}">
@if ($image = $film->coverImage(1200))<img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?: $film->title }}" loading="{{ ($feature ?? false) ? 'eager' : 'lazy' }}" decoding="async">@endif
<span class="film-play" aria-hidden="true">@include('site.partials.icon', ['icon' => 'play'])</span><span class="film-watch-label">{{ __('Watch film') }}</span>
</button>
@endif
