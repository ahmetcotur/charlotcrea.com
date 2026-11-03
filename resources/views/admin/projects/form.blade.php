@extends('admin.layout')
@section('title',$project->exists ? 'Portföy' : 'Yeni proje')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>{{ $project->exists ? $project->title : 'Yeni proje' }}</h1><p>Türkçe/İngilizce metinler, görseller ve destinasyon etiketleri.</p></div><a class="button secondary" href="{{ route('admin.projects.index') }}">← Portföye dön</a></div>

<form class="panel" method="POST" action="{{ $project->exists ? route('admin.projects.update',$project) : route('admin.projects.store') }}" enctype="multipart/form-data" data-loading-submit>
@csrf @if($project->exists)@method('PUT')@endif

<label class="field" style="display:flex;align-items:center;gap:8px;width:auto"><input style="width:auto" type="checkbox" name="published" value="1" @checked(old('published',$project->published))> Yayında</label>

<div class="field"><label for="project-reference">Marka / müşteri logosu</label><select id="project-reference" name="reference_id"><option value="">— Marka seçilmedi —</option>@foreach($references as $reference)<option value="{{ $reference->id }}" @selected(old('reference_id', $project->reference_id) == $reference->id)>{{ $reference->title }}{{ $reference->published ? '' : ' · Taslak' }}</option>@endforeach</select><div class="help">Logo Referanslar modülündeki kayıttan alınır. Ülke bilgisi aşağıdaki destinasyon seçimlerinden gelir.</div></div>
<div class="field">
<label>Kapak görseli</label>
<div class="upload-field">
<img class="upload-preview" id="cover-preview" src="{{ $project->exists && $project->hasImage('cover') ? $project->image('cover','default',['w'=>200]) : '' }}" alt="" @if(! ($project->exists && $project->hasImage('cover'))) hidden @endif>
<input type="file" name="cover" accept="image/png,image/jpeg,image/gif" data-preview-target="#cover-preview">
@if($project->exists && $project->hasImage('cover'))
<label class="upload-remove"><input type="checkbox" name="remove_cover" value="1"> Kapağı kaldır</label>
@endif
</div>
</div>

@if($project->exists)
<div class="field">
<label>Galeri</label>
@php($galleryMedia = $project->medias->filter(fn($m)=>$m->pivot->role==='gallery'))
@if($galleryMedia->isNotEmpty())
<p class="help">Fotoğrafların sırasını oklarla değiştirin. Değişiklikler kaydettiğinizde uygulanır.</p>
<div class="gallery-grid" data-gallery-sort>
@foreach($galleryMedia as $media)
<div class="gallery-grid-item">
<input type="hidden" name="gallery_order[]" value="{{ $media->id }}">
<div class="gallery-sort-actions"><button type="button" data-gallery-move="-1" aria-label="Görseli önceye taşı">←</button><button type="button" data-gallery-move="1" aria-label="Görseli sonraya taşı">→</button></div>
<img src="{{ $project->image('gallery','default',['w'=>240],false,false,$media) }}" alt="">
<label><input type="checkbox" name="remove_gallery[]" value="{{ $media->id }}"> Sil</label>
</div>
@endforeach
</div>
@endif
<input type="file" name="gallery[]" accept="image/png,image/jpeg,image/gif" multiple>
<div class="help">Birden fazla görsel seçebilirsiniz; seçilenler mevcut galeriye eklenir.</div>
</div>
@else
<div class="field"><label>Galeri</label><input type="file" name="gallery[]" accept="image/png,image/jpeg,image/gif" multiple><div class="help">Birden fazla görsel seçebilir, kaydetmeden önce seçiminizi düzenleyebilirsiniz.</div></div>
@endif

<div class="field">
<label>Destinasyon etiketleri</label>
@include('admin.partials.destination-picker', ['destinations' => $destinations, 'selected' => old('destinations', $selectedDestinations)])
<div class="help">Bir nokta seçildiğinde ülkesi otomatik olarak etiketlenir.</div>
</div>

@foreach(['tr'=>'Türkçe','en'=>'English'] as $locale=>$label)
@php($translation = $project->exists ? $project->translate($locale) : null)
<details class="panel" open><summary class="panel-title">{{ $label }}</summary>
<div class="field"><label>Başlık *</label><input name="title[{{ $locale }}]" required value="{{ old('title.'.$locale,$translation?->title) }}"></div>
<div class="field"><label>Sayfa adresi</label><input name="path[{{ $locale }}]" placeholder="Boş bırakırsanız başlıktan oluşturulur" value="{{ old('path.'.$locale,$translation?->path) }}"></div>
<div class="field"><label>Ana başlık</label><textarea name="heading[{{ $locale }}]" rows="2">{{ old('heading.'.$locale,$translation?->heading) }}</textarea></div>
<div class="field"><label>Giriş metni</label><textarea name="intro[{{ $locale }}]" rows="3">{{ old('intro.'.$locale,$translation?->intro) }}</textarea></div>
<div class="form-grid"><div class="field"><label>SEO başlığı</label><input name="seo_title[{{ $locale }}]" value="{{ old('seo_title.'.$locale,$translation?->seo_title) }}"></div><div class="field"><label>SEO açıklaması</label><textarea name="description[{{ $locale }}]" rows="3">{{ old('description.'.$locale,$translation?->description) }}</textarea></div></div>
</details>
@endforeach

@if($project->exists && $project->films->isNotEmpty())
<div class="panel"><h2 class="panel-title">Bağlı filmler</h2>
@foreach($project->films as $film)
<div class="list-row"><span>{{ $film->title }}</span><a href="{{ route('admin.films.edit',$film) }}" class="muted">Aç →</a></div>
@endforeach
</div>
@endif

@if($project->exists)
<div class="preview-actions" data-preview-url="{{ route('admin.content.preview', ['type' => 'projects', 'id' => $project->id]) }}">
<span>Kaydetmeden metin önizlemesi:</span><button type="button" class="button secondary" data-content-preview="tr">Türkçe ↗</button><button type="button" class="button secondary" data-content-preview="en">English ↗</button><span class="help" data-preview-status role="status"></span>
</div>
@else
<p class="help">Önizleme için önce kaydı oluşturun. Yeni görseller formda önizlenir.</p>
@endif
<div class="form-actions">
<button class="button" data-submit-button><span data-submit-label>{{ $project->exists ? 'Projeyi kaydet' : 'Proje oluştur' }}</span><span data-submit-spinner class="spinner" hidden></span></button>
@if($project->exists)<button type="button" class="button danger" onclick="document.getElementById('archive-project-dialog').showModal()">Arşivle</button>@endif
</div>
</form>

@if($project->exists)
<dialog id="archive-project-dialog" class="confirm-dialog">
<form method="POST" action="{{ route('admin.projects.destroy',$project) }}">
@csrf @method('DELETE')
<div class="confirm-body">
<h3>Projeyi arşivle</h3>
<p>{{ $project->title }} arşivlenecek, sitede görünmeyecek.</p>
<div class="confirm-actions">
<button type="button" class="button secondary" onclick="document.getElementById('archive-project-dialog').close()">Vazgeç</button>
<button type="submit" class="button danger">Arşivle</button>
</div>
</div>
</form>
</dialog>
@endif
@endsection
