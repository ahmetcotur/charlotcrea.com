@extends('public.layout')
@section('title', 'Teslimat · Charlot Crea')
@section('content')
<div class="portal-card">
@if($action === 'approved')
<p class="portal-result-icon">Teşekkürler</p>
<h1>Teslimatı onayladınız</h1>
<p class="portal-copy">{{ $work->title }} işini onayladığınız için teşekkür ederiz.</p>
@else
<p class="portal-result-icon">Alındı</p>
<h1>Revizyon talebiniz gönderildi</h1>
<p class="portal-copy">Talebinizi aldık, en kısa sürede değerlendirip size dönüş yapacağız.</p>
@endif
</div>
@endsection
