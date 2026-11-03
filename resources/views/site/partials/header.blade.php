<header><a href="{{ $site->route('/en/') ?? '/'.$locale.'/' }}" aria-label="{{ config('site.name') }}"><img class="logo" src="/assets/logo.png" alt="{{ config('site.name') }}" width="1000" height="636" decoding="async"></a>
<nav id="nav">
@foreach (['/en/photography/' => 'Photography', '/en/film/' => 'Film', '/en/destinations/' => 'Destinations', '/en/references/' => 'References', '/en/about/' => 'About'] as $identity => $label)
@if ($navPath = $site->route($identity))<a href="{{ $navPath }}">{{ __($label) }}</a>@endif
@endforeach
@if ($contact = $site->route('/en/request-a-quote/') ?: $site->route('/en/contact/'))<a class="btn" href="{{ $contact }}">{{ $site->route('/en/request-a-quote/') ? __('Request a quote') : __('Discuss a project') }} @include('site.partials.icon')</a>@endif
</nav>
<div class="language-switch" role="group" aria-label="{{ __('Choose language') }}">
@foreach (['tr', 'en'] as $language)
@if ($target = $model->publicPath($language))<a href="{{ $target }}" lang="{{ $language }}" hreflang="{{ $language }}" @if ($language === $locale) aria-current="page" @endif>{{ strtoupper($language) }}</a>@endif
@endforeach
</div>
<button class="mobile-toggle" aria-label="{{ __('Toggle navigation') }}" aria-expanded="false" onclick="let n=document.getElementById('nav');n.classList.toggle('open');this.setAttribute('aria-expanded',n.classList.contains('open'))">@include('site.partials.icon', ['icon' => 'menu'])</button>
</header>
