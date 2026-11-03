@if(filled($translation->body))
<section class="section portfolio-editorial" aria-label="{{ $locale === 'tr' ? 'Çalışma yaklaşımımız' : 'Our approach' }}"><div class="bodytext">{!! app(\App\Services\EditorialLinks::class)->html($translation->body, $locale) !!}</div></section>
@endif
