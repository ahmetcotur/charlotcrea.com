@extends('admin.layout')
@section('title',$reference->exists ? 'Referans' : 'Yeni referans')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>{{ $reference->exists ? $reference->title : 'Yeni referans' }}</h1><p>Logo, ad ve (isteğe bağlı) web adresi / açıklama ekleyin.</p></div><a class="button secondary" href="{{ route('admin.references.index') }}">← Referanslara dön</a></div>

<form class="panel" method="POST" action="{{ $reference->exists ? route('admin.references.update',$reference) : route('admin.references.store') }}" enctype="multipart/form-data" data-loading-submit>
@csrf @if($reference->exists)@method('PUT')@endif
<label class="field"><input style="width:auto" type="checkbox" name="published" value="1" @checked(old('published',$reference->published))> Yayında</label>

<div class="field">
<label>Logo</label>
<div class="upload-field">
<img class="upload-preview" id="logo-preview" src="{{ $reference->exists && $reference->hasImage('logo') ? $reference->image('logo','default',['w'=>160]) : '' }}" alt="" @if(! ($reference->exists && $reference->hasImage('logo'))) hidden @endif>
<input type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/svg+xml" data-preview-target="#logo-preview">
@if($reference->exists && $reference->hasImage('logo'))
<label class="upload-remove"><input type="checkbox" name="remove_logo" value="1"> Logoyu kaldır</label>
@endif
</div>
<div class="help">PNG, JPG, GIF veya SVG — kırpma yapılmaz, orijinal oranla gösterilir.</div>
</div>

@foreach(['tr'=>'Türkçe','en'=>'English'] as $locale=>$label)
@php($translation = $reference->exists ? $reference->translate($locale) : null)
<details class="panel" open><summary class="panel-title">{{ $label }}</summary>
<div class="field"><label>Ad *</label><input name="title[{{ $locale }}]" required value="{{ old('title.'.$locale,$translation?->title) }}"></div>
<div class="field"><label>Açıklama (opsiyonel)</label><textarea name="description[{{ $locale }}]" rows="2">{{ old('description.'.$locale,$translation?->description) }}</textarea></div>
</details>
@endforeach

<div class="field"><label>Web adresi (opsiyonel)</label><input type="url" name="website_url" placeholder="https://..." value="{{ old('website_url',$reference->website_url) }}"></div>

<div class="form-actions">
<button class="button" data-submit-button><span data-submit-label>{{ $reference->exists ? 'Referansı kaydet' : 'Referans oluştur' }}</span><span data-submit-spinner class="spinner" hidden></span></button>
@if($reference->exists)<button type="button" class="button danger" onclick="document.getElementById('archive-reference-dialog').showModal()">Arşivle</button>@endif
</div>
</form>

@if($reference->exists)
<dialog id="archive-reference-dialog" class="confirm-dialog">
<form method="POST" action="{{ route('admin.references.destroy',$reference) }}">
@csrf @method('DELETE')
<div class="confirm-body">
<h3>Referansı arşivle</h3>
<p>{{ $reference->title }} arşivlenecek, sitede görünmeyecek.</p>
<div class="confirm-actions">
<button type="button" class="button secondary" onclick="document.getElementById('archive-reference-dialog').close()">Vazgeç</button>
<button type="submit" class="button danger">Arşivle</button>
</div>
</div>
</form>
</dialog>
@endif
@endsection
