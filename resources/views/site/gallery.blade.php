@foreach ($images as $image)
<a class="gallery-open" href="{{ $image['src'] }}"><img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?? '' }}" loading="lazy"></a>
@endforeach
