@component('mail::message')
# Teklifiniz hazır

Merhaba {{ $quote->customer->title }},

**{{ $quote->title }}** için hazırladığımız teklifi aşağıdaki bağlantıdan görüntüleyebilir, kabul edebilir veya reddedebilirsiniz.

- Teklif no: **{{ $quote->quote_number }}**
@if($quote->valid_until)
- Geçerlilik tarihi: **{{ $quote->valid_until->translatedFormat('d F Y') }}**
@endif

@if($quote->items->isNotEmpty())
Kalem | Adet | Birim fiyat | Tutar
:- | -: | -: | -:
@foreach($quote->items as $item)
{{ $item->title }} | {{ rtrim(rtrim(number_format((float) $item->quantity, 2), '0'), '.') }} | {{ number_format((float) $item->unit_price, 2) }} {{ $quote->currency }} | {{ number_format($item->line_total, 2) }} {{ $quote->currency }}
@endforeach
**Toplam** | | | **{{ number_format((float) $quote->amount, 2) }} {{ $quote->currency }}**
@else
- Tutar: **{{ number_format((float) $quote->amount, 2) }} {{ $quote->currency }}**
@endif

@component('mail::button', ['url' => $signedUrl])
Teklifi görüntüle
@endcomponent

Herhangi bir sorunuz olursa bu e-postayı yanıtlamanız yeterli.

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
