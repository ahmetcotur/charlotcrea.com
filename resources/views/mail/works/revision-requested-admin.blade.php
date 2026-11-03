@component('mail::message')
# Revizyon talebi geldi

**{{ $revisionRequest->work->customer->title }}**, **{{ $revisionRequest->work->title }}** işi için revizyon talep etti.

> {{ $revisionRequest->customer_note }}

@component('mail::button', ['url' => route('admin.works.edit', $revisionRequest->work)])
İşi admin panelinde aç
@endcomponent

{{ config('app.name') }}
@endcomponent
