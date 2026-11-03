@extends('site.layout')
@section('content')
@include('site.partials.intro', ['label' => __('Editorial notes')])
<section class="section journal-grid">@foreach($posts as $post)@include('site.partials.post-card')@endforeach</section>
@endsection
