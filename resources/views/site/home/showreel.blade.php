<section class="section home-showreel" data-home-section="showreel"><div class="section-head"><div><span class="label">{{ $plain(0) }}</span><h2>{!! $text(1) !!}</h2></div><a class="text-link" href="{{ $site->route('/en/film/') }}">{{ __('All films') }} @include('site.partials.icon')</a></div>
@if($featured=$homeManager->featuredFilm($model,$locale))
<div class="home-motion">
@if($featured->video_url && !$featured->driveId())
<video class="home-motion-video" muted loop playsinline preload="none" poster="{{ $featured->coverImage(1600)['src'] ?? '' }}" data-src="{{ app(\App\Services\Site\PublicSite::class)->videoUrl($featured->video_url) }}" aria-label="{{ $featured->title }}"></video><div class="home-motion-controls"><button class="motion-toggle" type="button" aria-pressed="false">{{ __('Play preview') }}</button><button class="motion-full" type="button" data-video-src="{{ app(\App\Services\Site\PublicSite::class)->videoUrl($featured->video_url) }}" data-film-title="{{ $featured->title }}">{{ __('Watch with sound') }} @include('site.partials.icon')</button></div>
@else
@include('site.partials.film-button',['film'=>$featured])
@endif
</div>
@endif
<div class="home-film-selection">@include('site.partials.home-items',['key'=>'showreel'])</div></section>
