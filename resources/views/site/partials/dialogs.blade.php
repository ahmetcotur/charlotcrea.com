@if ($gallery ?? false)
<dialog class="gallery-dialog" aria-label="{{ __('Photo viewer') }}"><button class="gallery-close" aria-label="{{ __('Close photo') }}">@include('site.partials.icon', ['icon' => 'close'])</button><button class="gallery-prev" aria-label="{{ __('Previous photograph') }}">@include('site.partials.icon', ['icon' => 'prev'])</button><img alt=""><button class="gallery-next" aria-label="{{ __('Next photograph') }}">@include('site.partials.icon', ['icon' => 'next'])</button><p class="gallery-count" aria-live="polite"></p></dialog>
@endif
@if ($video ?? false)
<dialog class="film-dialog" aria-label="{{ __('Film player') }}"><button class="film-close" aria-label="{{ __('Close film') }}">@include('site.partials.icon', ['icon' => 'close'])</button><div class="film-player-host"></div><div class="film-player-caption" aria-live="polite"></div></dialog>
@endif
