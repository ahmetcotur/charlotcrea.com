@extends('admin.layout')
@section('title',$film->exists ? 'Film' : 'Yeni film')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>{{ $film->exists ? $film->title : 'Yeni film' }}</h1><p>Video bağlantısı, görseller ve destinasyon etiketleri.</p></div><a class="button secondary" href="{{ route('admin.films.index') }}">← Filmlere dön</a></div>

<form class="panel" method="POST" action="{{ $film->exists ? route('admin.films.update',$film) : route('admin.films.store') }}" enctype="multipart/form-data" data-loading-submit>
@csrf @if($film->exists)@method('PUT')@endif

<div class="form-grid">
<div class="field"><label style="display:flex;align-items:center;gap:8px"><input style="width:auto" type="checkbox" name="published" value="1" @checked(old('published',$film->published))> Yayında</label></div>
<div class="field"><label>Bağlı proje (opsiyonel)</label><select name="project_id"><option value="">Seçilmedi</option>@foreach($projects as $project)<option value="{{ $project->id }}" @selected(old('project_id',$film->project_id)==$project->id)>{{ $project->title }}</option>@endforeach</select></div>
</div>
<div class="field"><label>Video adresi</label><input name="video_url" value="{{ old('video_url',$film->video_url) }}" placeholder="https://drive.google.com/file/d/... veya /assets/films/....mp4"></div>

<div class="field">
<label>Kapak / poster görseli</label>
<div class="upload-field">
<img class="upload-preview" id="cover-preview" src="{{ $film->exists && $film->hasImage('cover') ? $film->image('cover','default',['w'=>200]) : '' }}" alt="" @if(! ($film->exists && $film->hasImage('cover'))) hidden @endif>
<input type="file" name="cover" accept="image/png,image/jpeg,image/gif" data-preview-target="#cover-preview">
@if($film->exists && $film->hasImage('cover'))
<label class="upload-remove"><input type="checkbox" name="remove_cover" value="1"> Kapağı kaldır</label>
@endif
</div>
</div>

@if($film->exists)
<div class="field">
<label>Galeri</label>
@php($galleryMedia = $film->medias->filter(fn($m)=>$m->pivot->role==='gallery'))
@if($galleryMedia->isNotEmpty())
<p class="help">Fotoğrafların sırasını oklarla değiştirin. Değişiklikler kaydettiğinizde uygulanır.</p>
<div class="gallery-grid" data-gallery-sort>
@foreach($galleryMedia as $media)
<div class="gallery-grid-item">
<input type="hidden" name="gallery_order[]" value="{{ $media->id }}">
<div class="gallery-sort-actions"><button type="button" data-gallery-move="-1" aria-label="Görseli önceye taşı">←</button><button type="button" data-gallery-move="1" aria-label="Görseli sonraya taşı">→</button></div>
<img src="{{ $film->image('gallery','default',['w'=>240],false,false,$media) }}" alt="">
<label><input type="checkbox" name="remove_gallery[]" value="{{ $media->id }}"> Sil</label>
</div>
@endforeach
</div>
@endif
<input type="file" name="gallery[]" accept="image/png,image/jpeg,image/gif" multiple>
</div>
@else
<div class="field"><label>Galeri</label><input type="file" name="gallery[]" accept="image/png,image/jpeg,image/gif" multiple><div class="help">Birden fazla görsel seçebilir, kaydetmeden önce seçiminizi düzenleyebilirsiniz.</div></div>
@endif

<div class="field">
<label>Destinasyon etiketleri</label>
@include('admin.partials.destination-picker', ['destinations' => $destinations, 'selected' => old('destinations', $selectedDestinations)])
</div>

@foreach(['tr'=>'Türkçe','en'=>'English'] as $locale=>$label)
@php($translation = $film->exists ? $film->translate($locale) : null)
<details class="panel" open><summary class="panel-title">{{ $label }}</summary>
<div class="field"><label>Başlık *</label><input name="title[{{ $locale }}]" required value="{{ old('title.'.$locale,$translation?->title) }}"></div>
<div class="field"><label>Sayfa adresi</label><input name="path[{{ $locale }}]" placeholder="Boş bırakırsanız başlıktan oluşturulur" value="{{ old('path.'.$locale,$translation?->path) }}"></div>
<div class="field"><label>Ana başlık</label><textarea name="heading[{{ $locale }}]" rows="2">{{ old('heading.'.$locale,$translation?->heading) }}</textarea></div>
<div class="field"><label>Giriş metni</label><textarea name="intro[{{ $locale }}]" rows="3">{{ old('intro.'.$locale,$translation?->intro) }}</textarea></div>
<div class="form-grid"><div class="field"><label>SEO başlığı</label><input name="seo_title[{{ $locale }}]" value="{{ old('seo_title.'.$locale,$translation?->seo_title) }}"></div><div class="field"><label>SEO açıklaması</label><textarea name="description[{{ $locale }}]" rows="3">{{ old('description.'.$locale,$translation?->description) }}</textarea></div></div>
</details>
@endforeach

@if($film->exists)
<div class="preview-actions" data-preview-url="{{ route('admin.content.preview', ['type' => 'films', 'id' => $film->id]) }}">
<span>Kaydetmeden metin önizlemesi:</span><button type="button" class="button secondary" data-content-preview="tr">Türkçe ↗</button><button type="button" class="button secondary" data-content-preview="en">English ↗</button><span class="help" data-preview-status role="status"></span>
</div>
@else
<p class="help">Önizleme için önce kaydı oluşturun. Yeni görseller formda önizlenir.</p>
@endif
<div class="form-actions">
<button class="button" data-submit-button><span data-submit-label>{{ $film->exists ? 'Filmi kaydet' : 'Film oluştur' }}</span><span data-submit-spinner class="spinner" hidden></span></button>
@if($film->exists)<button type="button" class="button danger" onclick="document.getElementById('archive-film-dialog').showModal()">Arşivle</button>@endif
</div>
</form>

@if($film->exists)
<dialog id="archive-film-dialog" class="confirm-dialog">
<form method="POST" action="{{ route('admin.films.destroy',$film) }}">
@csrf @method('DELETE')
<div class="confirm-body">
<h3>Filmi arşivle</h3>
<p>{{ $film->title }} arşivlenecek, sitede görünmeyecek.</p>
<div class="confirm-actions">
<button type="button" class="button secondary" onclick="document.getElementById('archive-film-dialog').close()">Vazgeç</button>
<button type="submit" class="button danger">Arşivle</button>
</div>
</div>
</form>
</dialog>
@endif
@endsection
