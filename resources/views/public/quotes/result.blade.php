@extends('public.layout')
@section('title', 'Teklif · Charlot Crea')
@section('content')
<div class="portal-card">
@if($quote->status === 'accepted')
<p class="portal-result-icon">Teşekkürler</p>
<h1>Teklifi kabul ettiniz</h1>
<p class="portal-copy">{{ $quote->title }} teklifini onayladığınız için teşekkür ederiz. Ekibimiz en kısa sürede sizinle iletişime geçecek.</p>
@elseif($quote->status === 'declined')
<p class="portal-result-icon">Kaydedildi</p>
<h1>Teklifi reddettiniz</h1>
<p class="portal-copy">Geri bildiriminiz bize ulaştı. Dilerseniz bu konuda bizimle iletişime geçebilirsiniz.</p>
@else
<h1>{{ $quote->title }}</h1>
<p class="portal-copy">Bu teklif için yanıtınız zaten kaydedilmiş.</p>
@endif
</div>
@endsection
