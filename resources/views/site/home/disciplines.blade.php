<section @class(['section','discipline-section'=>$variant==='classic','about-services'=>$variant!=='classic']) data-home-section="disciplines"><div class="section-head"><div><span class="label">{{ $plain(0) }}</span><h2>{!! $text(1) !!}</h2></div></div><div class="{{ $variant==='classic'?'discipline-grid':'about-service-grid' }}">
@foreach(['/en/photography/','/en/film/'] as $i=>$identity)
@if($path=$site->route($identity))<a href="{{ $path }}" @class(['discipline-card'=>$variant==='classic','discipline-film'=>$variant==='classic'&&$i===1,'about-service'=>$variant!=='classic'])><span class="label">{{ $plain(2+$i*3) }}</span><h3>{!! $text(3+$i*3) !!}</h3><p>{{ $plain(4+$i*3) }}</p><span class="{{ $variant==='classic'?'discipline-link':'text-link' }}">{{ $i===0?__('Explore photography'):__('Discover film') }} @include('site.partials.icon')</span></a>@endif
@endforeach
</div></section>
