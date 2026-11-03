@component('mail::message')
# Revizyon talebiniz {{ $revisionRequest->status === 'approved' ? 'kabul edildi' : 'reddedildi' }}

Merhaba {{ $revisionRequest->work->customer->title }},

**{{ $revisionRequest->work->title }}** işi için gönderdiğiniz revizyon talebini değerlendirdik.

@if($revisionRequest->admin_response)
> {{ $revisionRequest->admin_response }}
@endif

@component('mail::button', ['url' => $signedUrl])
İşi görüntüle
@endcomponent

Teşekkürler,<br>
{{ config('app.name') }}
@endcomponent
