@component('mail::message')
# Teklif reddedildi

**{{ $quote->customer->title }}**, **{{ $quote->title }}** ({{ $quote->quote_number }}) teklifini reddetti.

@component('mail::button', ['url' => route('admin.quotes.edit', $quote), 'color' => 'error'])
Teklifi admin panelinde aç
@endcomponent

{{ config('app.name') }}
@endcomponent
