@extends('site.layout')
@section('content')
@php
$native = app(\App\Services\NativePageData::class);
$defaults = $native->defaults($model);
$groups = $native->groups($model,$locale);
$labels = $defaults['labels'][$locale] ?? [];
$cover = $model->coverImage(1600);
$approach = $groups[1]['parts'] ?? [];
$services = array_values(array_filter($groups[2]['parts'] ?? [],fn($part)=>filled($part['heading'])));
$cta = $groups[count($groups)-1] ?? ['heading'=>'','body'=>''];
@endphp
<section class="about-opening"><div class="about-opening-copy"><span class="label">{{ $labels[0] ?? '' }}</span><h1>{!! nl2br(e($translation->heading ?: $translation->title)) !!}</h1><p class="about-lead">{{ $translation->intro }}</p><a class="text-link" href="#our-story">{{ $locale==='tr'?'Hikâyemizi keşfedin':'Discover our story' }} @include('site.partials.icon')</a><div class="about-facts">@foreach($defaults['facts'][$locale] ?? [] as $fact)<div><strong>{{ $fact['value'] }}</strong><span>{{ $fact['label'] }}</span></div>@endforeach</div></div><figure class="about-opening-photo">@if($cover)<img src="{{ $cover['src'] }}" alt="{{ $translation->title }}" decoding="async">@endif<figcaption>@foreach($defaults['captions'][$locale] ?? [] as $caption)<span>{{ $caption }}</span>@endforeach</figcaption></figure></section>
@if(isset($groups[0]))<section class="section about-story" id="our-story"><div><span class="label">{{ $labels[1] ?? '' }}</span><h2>{!! $groups[0]['heading'] !!}</h2></div><div class="bodytext">{!! $groups[0]['body'] !!}</div></section>@endif
@if(isset($groups[1]))<section class="section about-approach"><div class="section-head"><div><span class="label">{{ $labels[2] ?? '' }}</span><h2>{!! $groups[1]['heading'] !!}</h2></div>@if(isset($approach[0]) && !$approach[0]['heading'])<div class="bodytext">{!! $approach[0]['body'] !!}</div>@endif</div><div class="approach-grid">@foreach(array_values(array_filter($approach,fn($part)=>filled($part['heading']))) as $part)<article><span class="label">{{ $labels[3+$loop->index] ?? str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><h3>{{ $part['heading'] }}</h3><div class="bodytext">{!! $part['body'] !!}</div></article>@endforeach</div></section>@endif
@if(isset($groups[2]))<section class="section about-services"><div class="section-head"><div><span class="label">{{ $labels[6] ?? '' }}</span><h2>{!! $groups[2]['heading'] !!}</h2></div></div><div class="about-service-grid">@foreach($services as $part)<a class="about-service" href="{{ $site->route($loop->first?'/en/photography/':'/en/film/') }}"><span class="label">{{ $labels[7+$loop->index] ?? '' }}</span><h3>{{ $part['heading'] }}</h3><div class="bodytext">{!! $part['body'] !!}</div><span class="text-link">{{ $loop->first?__('Explore photography'):__('Explore films') }} @include('site.partials.icon')</span></a>@endforeach</div></section>@endif
@foreach(array_slice($groups,3,max(0,count($groups)-4)) as $group)<section class="section about-story"><h2>{!! $group['heading'] !!}</h2><div class="bodytext">{!! $group['body'] !!}</div></section>@endforeach
<section class="section about-reference-strip"><span class="label">{{ $labels[9] ?? '' }}</span><div class="reference-grid">@include('site.partials.home-items',['key'=>'references','items'=>\App\Models\Reference::published()->whereHas('translations',fn($q)=>$q->where('locale',$locale)->where('active',true))->with(['translations','medias'])->orderBy('position')->orderBy('id')->limit(6)->get()])</div><a class="text-link" href="{{ $site->route('/en/references/') }}">{{ __('View all references') }} @include('site.partials.icon')</a></section>
<section class="cta"><span class="label">{{ $labels[10] ?? '' }}</span><h2>{!! $cta['heading'] !!}</h2><div class="bodytext">{!! $cta['body'] !!}</div><a class="btn" href="{{ $site->route('/en/request-a-quote/') ?: $site->route('/en/contact/') }}">{{ __('Discuss a project') }} @include('site.partials.icon')</a></section>
@endsection
