@foreach($images as $image)
<a class="gallery-open hero-filmstrip-item" href="{{ $image['src'] }}"><img src="{{ $image['src'] }}" alt="{{ $image['alt'] ?? '' }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">@if($image['caption'] ?? '')<span class="hero-filmstrip-caption">{{ is_array($image['caption']) ? ($image['caption'][$locale] ?? '') : $image['caption'] }}</span>@endif</a>
@endforeach
