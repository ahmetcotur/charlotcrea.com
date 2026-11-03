@extends('site.layout')
@section('content')
@include('site.partials.intro', ['label' => __('Photography / Selected projects')])
<section class="section" style="padding-top:10px"><div class="filters" aria-label="{{ __('Filter projects') }}">
@foreach (collect([__('All')])->concat($projects->pluck('country')->filter()->unique()) as $filter)
<button class="{{ $loop->first ? 'selected' : '' }}" data-filter="{{ $loop->first ? 'All' : $filter }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $loop->first ? __('All') : $site->countryName($filter) }}</button>
@endforeach
</div><div class="project-grid">@foreach($projects as $project)@include('site.partials.project-card')@endforeach</div></section>
@include('site.partials.portfolio-editorial')
@include('site.partials.cta')
@endsection
