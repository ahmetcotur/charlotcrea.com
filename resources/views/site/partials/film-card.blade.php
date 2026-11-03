<article class="film-card" data-country="{{ $film->country }}">
@include('site.partials.film-button', ['film' => $film])
<div class="project-info"><div><h3><a href="{{ $film->publicPath($locale) }}">{{ $film->title }}</a></h3><p>{{ $film->city }}</p></div><a class="arrow" href="{{ $film->publicPath($locale) }}" aria-label="{{ $film->title }}">@include('site.partials.icon')</a></div>
</article>
