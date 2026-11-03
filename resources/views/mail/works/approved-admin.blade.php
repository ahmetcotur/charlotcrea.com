@component('mail::message')
# Teslimat onaylandı

**{{ $work->customer->title }}**, **{{ $work->title }}** teslimatını onayladı. İş tamamlandı olarak işaretlendi.

@component('mail::button', ['url' => route('admin.works.edit', $work)])
İşi admin panelinde aç
@endcomponent

{{ config('app.name') }}
@endcomponent
