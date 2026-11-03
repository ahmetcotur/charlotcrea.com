@if ($contact = $site->route('/en/request-a-quote/') ?: $site->route('/en/contact/'))
<section class="cta"><span class="label">{{ __('Your next project') }}</span><h2>{{ __('Let’s shape your next story.') }}</h2><p>{{ __('Share the location, vision and timing for your photography or film project.') }}</p><a class="btn" href="{{ $contact }}">{{ $site->route('/en/request-a-quote/') ? __('Request a quote') : __('Discuss a project') }} @include('site.partials.icon')</a></section>
@endif
