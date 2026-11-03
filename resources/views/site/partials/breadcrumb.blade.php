<nav class="seo-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
@foreach ($crumbs as [$label, $href])
@if (! $loop->first)<span aria-hidden="true"> / </span>@endif
@if ($loop->last || ! $href)<span @if($loop->last) aria-current="page" @endif>{{ $label }}</span>@else<a href="{{ $href }}">{{ $label }}</a>@endif
@endforeach
</nav>
