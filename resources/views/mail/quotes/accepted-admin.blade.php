@component('mail::message')
# Teklif kabul edildi

**{{ $quote->customer->title }}**, **{{ $quote->title }}** ({{ $quote->quote_number }}) teklifini kabul etti.

- Tutar: **{{ number_format((float) $quote->amount, 2) }} {{ $quote->currency }}**
- Kabul zamanı: **{{ $quote->accepted_at?->translatedFormat('d F Y H:i') }}**

@component('mail::button', ['url' => route('admin.quotes.edit', $quote)])
Teklifi admin panelinde aç
@endcomponent

{{ config('app.name') }}
@endcomponent
