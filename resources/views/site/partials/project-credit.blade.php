@php
$brand = $model->reference;
$brandVisible = $brand && $brand->published && $brand->translate($locale)?->active;
$countries = $model->countries->filter(fn($country) => $country->publicPath($locale));
@endphp
@if($brandVisible || $countries->isNotEmpty() || $model->country)
<section class="section project-credit" aria-label="{{ __('Brand') }} / {{ __('Project country') }}">
@if($brandVisible)
<div class="project-brand" data-logo-theme="{{ $brand->logo_theme }}">
@if($logo = $brand->imageAsArray('logo', 'default', ['w' => 360]))<img src="{{ $logo['src'] }}" alt="{{ $brand->title }}" width="180" height="100" decoding="async">@endif
<div><span class="label">{{ __('Brand') }}</span><strong>{{ $brand->title }}</strong></div>
</div>
@endif
<div class="project-country"><span class="label">{{ __('Project country') }}</span>
@if($countries->isNotEmpty())
@foreach($countries as $country)<a class="text-link" href="{{ $country->publicPath($locale) }}">{{ $country->title }} @include('site.partials.icon')</a>@endforeach
@else<strong>{{ $site->countryName($model->country) }}</strong>@endif
</div>
</section>
@endif
