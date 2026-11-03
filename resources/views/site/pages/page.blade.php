@extends('site.layout')
@section('content')
@include('site.partials.intro')
@if($image = $model->coverImage(1600))<section class="section page-cover"><img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?: $translation->title }}" @if($image['width']) width="{{ $image['width'] }}" height="{{ $image['height'] }}" @endif decoding="async"></section>@endif
@if($translation->body)<section class="section page-body"><div class="bodytext">{!! \App\Services\RichText::clean($translation->body) !!}</div></section>@endif
@endsection
