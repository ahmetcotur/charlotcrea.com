@component('mail::message')
# İşiniz teslim edildi

Merhaba {{ $work->customer->title }},

**{{ $work->title }}** işiniz tamamlandı ve teslim edildi.

@if($work->delivery_note)
{{ $work->delivery_note }}
@endif

Aşağıdaki bağlantıdan teslimatı onaylayabilir veya revizyon talep edebilirsiniz.

@component('mail::button', ['url' => $signedUrl])
Teslimatı görüntüle
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
