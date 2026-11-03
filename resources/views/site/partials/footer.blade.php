@unless($contentOnly ?? false)<footer class="site-footer">@endunless
<div class="footer-main">
<div class="footer-brand">
<a href="{{ $site->route('/en/') ?? '/'.$locale.'/' }}" aria-label="{{ config('site.name') }}"><img src="/assets/logo.png" alt="{{ config('site.name') }}" width="104" height="76" loading="lazy"></a>
<p>{{ __('Photography & Film') }}<br>{{ __('Based in Türkiye. Working internationally.') }}</p>
<div class="footer-social" aria-label="{{ __('Social media') }}">
@foreach(['instagram_url' => 'Instagram', 'vimeo_url' => 'Vimeo', 'linkedin_url' => 'LinkedIn'] as $key => $label)
@if(config('site.'.$key))<a href="{{ config('site.'.$key) }}" target="_blank" rel="noopener noreferrer">{{ $label }} <span aria-hidden="true">↗</span></a>@endif
@endforeach
</div>
</div>
<div class="footer-contact"><span class="label">{{ __('Contact') }}</span><a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a><a href="tel:{{ preg_replace('/[^+0-9]/', '', config('site.telephone')) }}">{{ config('site.telephone') }}</a>
@if(config('site.address'))<address>{{ config('site.address') }}</address>@endif
@if(config('site.whatsapp_number'))<a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', config('site.whatsapp_number')) }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>@endif
@if($contact = $site->route('/en/contact/'))<a class="text-link" href="{{ $contact }}">{{ __('Address & map') }} @include('site.partials.icon')</a>@endif
</div>
<div class="footer-navigation">
<div class="footer-explore"><span class="label">{{ __('Explore') }}</span>
@foreach(['/en/photography/' => 'Photography', '/en/film/' => 'Film', '/en/about/' => 'About', '/en/blog/' => 'Blog'] as $identity => $label)
@if($footerPath = $site->route($identity))<a href="{{ $footerPath }}">{{ __($label) }}</a>@endif
@endforeach
</div>
<div class="footer-actions">
@if($quotePath = $site->route('/en/request-a-quote/'))
<a class="footer-action footer-action-quote" href="{{ $quotePath }}"><div><span class="footer-action-kicker">{{ __('Have a project in mind?') }}</span><strong>{{ __('Request a quote') }}</strong></div><span class="footer-action-arrow" aria-hidden="true">@include('site.partials.icon')</span></a>
@endif
<a class="footer-action footer-action-portal" href="{{ route('customer.login', ['lang'=>$locale]) }}"><div><span class="footer-action-kicker">{{ __('Your projects & deliveries') }}</span><strong>{{ __('Client portal') }}</strong></div><span class="footer-action-arrow" aria-hidden="true">@include('site.partials.icon')</span></a>
</div>
</div>
</div>
<div class="footer-bottom"><span>© {{ date('Y') }} {{ config('site.name') }}</span><span>{{ $locale === 'tr' ? 'Fotoğraf & Film · Türkiye’den dünyaya' : 'Photography & Film · Türkiye and beyond' }}</span></div>
@unless($contentOnly ?? false)</footer>
<button type="button" class="back-to-top" aria-label="{{ __('Back to top') }}" hidden>@include('site.partials.icon', ['icon'=>'up'])</button>
@endunless
