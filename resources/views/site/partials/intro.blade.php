<section class="page-intro">
<span class="label">{{ $label ?? $translation->category ?? '' }}</span>
<h1>{!! nl2br(e($translation->heading ?: $translation->title)) !!}</h1>
@if($translation->intro)
<p>{{ $translation->intro }}</p>
@endif
@isset($meta)
<div class="work-meta">{!! $meta !!}</div>
@endisset
</section>
