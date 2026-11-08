<section @class(['hero','hero-filmstrip'=>$variant==='filmstrip','hero-video'=>$variant==='video']) data-home-section="hero">
@if($variant === 'video')
<div class="hero-image"><video class="hero-bg-video" autoplay muted loop playsinline preload="metadata" @if($poster=$model->coverImage(1600)) poster="{{ $poster['src'] }}" @endif aria-hidden="true">@if($model->video_url)<source src="{{ app(\App\Services\Site\PublicSite::class)->videoUrl($model->video_url) }}" type="video/mp4">@endif</video></div>
@endif
<div class="hero-top"><div><span class="label">{{ $plain(0) }}</span><h1>{!! $text(1) !!}</h1></div><p>{{ $plain(2) }} <a href="{{ $site->route($variant==='video'?'/en/film/':'/en/photography/') }}">{{ $variant==='video' ? __('Watch the full film') : __('Discover our work') }} @include('site.partials.icon')</a></p></div>
@if($variant === 'classic')
<div class="home-contact-sheet">
@foreach($blueprint['hero_projects'] ?? [] as $frame)
@php
$project = $site->catalog(\App\Models\Project::class)->firstWhere('import_key',$frame['identity']);
$cover = $loop->first && $model->hasImage('cover') ? $model->coverImage(1600) : null;
@endphp
@if($project)
<a class="home-frame home-frame-{{ $loop->iteration }}" href="{{ $project->publicPath($locale) }}"><img src="{{ $cover['src'] ?? $frame['image'] }}" alt="{{ $project->title }}" loading="{{ $loop->first?'eager':'lazy' }}" decoding="async"><span><small>{{ $project->city }}</small>{{ $project->title }} @include('site.partials.icon')</span></a>
@endif
@endforeach
</div>
<div class="home-opening-note"><span class="label">{{ $plain(3) }}</span>@if($featured=$homeManager->featuredFilm($model,$locale))<a class="text-link home-reel-link" href="{{ $featured->publicPath($locale) }}">{{ __('Watch the showreel') }} @include('site.partials.icon')</a>@endif</div>
@elseif($variant === 'filmstrip')
<div class="hero-filmstrip-viewport"><div class="hero-filmstrip-track">@include('site.partials.hero-filmstrip-items',['images'=>$model->galleryImages()])</div></div>
<div class="below-image"><span>{{ __('Based in Türkiye. Working internationally.') }}</span><span>{{ __('Scroll to explore') }} @include('site.partials.icon',['icon'=>'up'])</span></div>
@else
<div class="below-image"><span>{{ __('Based in Türkiye. Working internationally.') }}</span><span>{{ __('Scroll to explore') }} @include('site.partials.icon',['icon'=>'up'])</span></div>
@endif
</section>
