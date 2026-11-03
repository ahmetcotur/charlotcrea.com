@extends('admin.layout')
@section('title',$destination->exists ? 'Destinasyon' : 'Yeni destinasyon')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>{{ $destination->exists ? $destination->title : 'Yeni destinasyon' }}</h1><p>Hiyerarşi, metinler ve görseller.</p></div><a class="button secondary" href="{{ route('admin.destinations.index') }}">← Destinasyonlara dön</a></div>

<form class="panel" method="POST" action="{{ $destination->exists ? route('admin.destinations.update',$destination) : route('admin.destinations.store') }}" enctype="multipart/form-data" data-loading-submit>
@csrf @if($destination->exists)@method('PUT')@endif

<div class="form-grid">
<div class="field"><label style="display:flex;align-items:center;gap:8px"><input style="width:auto" type="checkbox" name="published" value="1" @checked(old('published',$destination->published))> Yayında</label></div>
<div class="field"><label>Üst destinasyon</label>
<select name="parent_id">
<option value="">— Yok (ülke / en üst seviye) —</option>
@php($depths = [])
@foreach($parents as $p)
@php($depths[$p->id] = $p->parent_id ? ($depths[$p->parent_id] ?? 0) + 1 : 0)
<option value="{{ $p->id }}" @selected(old('parent_id',request()->query('parent_id',$destination->parent_id))==$p->id)>{{ str_repeat('— ', $depths[$p->id]) }}{{ $p->title }}</option>
@endforeach
</select>
</div>
</div>

<div class="field">
<label>Kapak görseli</label>
<div class="upload-field">
<img class="upload-preview" id="cover-preview" src="{{ $destination->exists && $destination->hasImage('cover') ? $destination->image('cover','default',['w'=>200]) : '' }}" alt="" @if(! ($destination->exists && $destination->hasImage('cover'))) hidden @endif>
<input type="file" name="cover" accept="image/png,image/jpeg,image/gif" data-preview-target="#cover-preview">
@if($destination->exists && $destination->hasImage('cover'))
<label class="upload-remove"><input type="checkbox" name="remove_cover" value="1"> Kapağı kaldır</label>
@endif
</div>
</div>

@if($destination->exists)
<div class="field">
<label>Galeri</label>
@php($galleryMedia = $destination->medias->filter(fn($m)=>$m->pivot->role==='gallery'))
@if($galleryMedia->isNotEmpty())
<p class="help">Fotoğrafların sırasını oklarla değiştirin. Değişiklikler kaydettiğinizde uygulanır.</p>
<div class="gallery-grid" data-gallery-sort>
@foreach($galleryMedia as $media)
<div class="gallery-grid-item">
<input type="hidden" name="gallery_order[]" value="{{ $media->id }}">
<div class="gallery-sort-actions"><button type="button" data-gallery-move="-1" aria-label="Görseli önceye taşı">←</button><button type="button" data-gallery-move="1" aria-label="Görseli sonraya taşı">→</button></div>
<img src="{{ $destination->image('gallery','default',['w'=>240],false,false,$media) }}" alt="">
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

@foreach(['tr'=>'Türkçe','en'=>'English'] as $locale=>$label)
@php($translation = $destination->exists ? $destination->translate($locale) : null)
<details class="panel" open><summary class="panel-title">{{ $label }}</summary>
<div class="field"><label>Başlık *</label><input name="title[{{ $locale }}]" required value="{{ old('title.'.$locale,$translation?->title) }}"></div>
<div class="field"><label>Sayfa adresi</label><input name="path[{{ $locale }}]" placeholder="Boş bırakırsanız başlıktan oluşturulur" value="{{ old('path.'.$locale,$translation?->path) }}"></div>
<div class="field"><label>Ana başlık</label><textarea name="heading[{{ $locale }}]" rows="2">{{ old('heading.'.$locale,$translation?->heading) }}</textarea></div>
<div class="field"><label>Giriş metni</label><textarea name="intro[{{ $locale }}]" rows="3">{{ old('intro.'.$locale,$translation?->intro) }}</textarea></div>
<div class="field"><label>Arka plan yazısı (dekoratif, büyük başlık)</label><input name="background[{{ $locale }}]" value="{{ old('background.'.$locale,$translation?->background) }}"></div>

<div class="field rich-editor-field"><label for="body-editor-{{ $locale }}">Tanıtım metni (bölümler)</label><div class="rich-toolbar" role="toolbar" aria-label="Metin biçimlendirme araçları">
<select data-command="formatBlock" aria-label="Metin stili"><option value="P">Normal metin</option><option value="H2">Bölüm başlığı</option><option value="H3">Alt başlık</option></select>
<button type="button" data-command="bold"><strong>B</strong></button><button type="button" data-command="italic"><em>I</em></button><button type="button" data-command="insertUnorderedList">• Liste</button><button type="button" data-command="createLink">Bağlantı</button><button type="button" data-command="removeFormat">Biçimi temizle</button></div>
<div id="body-editor-{{ $locale }}" class="rich-editor" contenteditable="true">{!! \App\Services\RichText::clean(old('body.'.$locale,$translation?->body)) !!}</div><textarea class="rich-editor-value" name="body[{{ $locale }}]" hidden></textarea><div class="help">H2 başlıkları sitede numaralı bölüm kartlarına dönüşür.</div></div>

<div class="form-grid"><div class="field"><label>Hikaye başlığı</label><input name="story_heading[{{ $locale }}]" value="{{ old('story_heading.'.$locale,$translation?->story_heading) }}"></div><div class="field"><label>Hikaye girişi</label><textarea name="story_intro[{{ $locale }}]" rows="3">{{ old('story_intro.'.$locale,$translation?->story_intro) }}</textarea></div></div>

<div class="field rich-editor-field"><label for="faq-editor-{{ $locale }}">SSS (Soru / Cevap)</label><div class="rich-toolbar" role="toolbar" aria-label="Metin biçimlendirme araçları">
<select data-command="formatBlock" aria-label="Metin stili"><option value="P">Normal metin</option><option value="H2">Soru (başlık)</option></select>
<button type="button" data-command="bold"><strong>B</strong></button><button type="button" data-command="italic"><em>I</em></button><button type="button" data-command="insertUnorderedList">• Liste</button></div>
<div id="faq-editor-{{ $locale }}" class="rich-editor" contenteditable="true">{!! \App\Services\RichText::clean(old('faq_body.'.$locale,$translation?->faq_body)) !!}</div><textarea class="rich-editor-value" name="faq_body[{{ $locale }}]" hidden></textarea><div class="help">Her soruyu H2 başlık, cevabını hemen altına normal metin olarak yazın.</div></div>

<div class="field"><label for="keywords-{{ $locale }}">Hedef anahtar kelimeler</label><input id="keywords-{{ $locale }}" name="seo_keywords[{{ $locale }}]" value="{{ old('seo_keywords.'.$locale, implode(', ', $destination->source_data['seo_keywords'][$locale] ?? [])) }}"><p class="help">Başlık ve metinlerde doğal biçimde kullanacağınız hedef ifadeleri virgülle ayırın.</p></div>
<div class="form-grid"><div class="field"><label>SEO başlığı</label><input name="seo_title[{{ $locale }}]" value="{{ old('seo_title.'.$locale,$translation?->seo_title) }}"></div><div class="field"><label>SEO açıklaması</label><textarea name="description[{{ $locale }}]" rows="3">{{ old('description.'.$locale,$translation?->description) }}</textarea></div></div>
</details>
@endforeach

@if($destination->exists)
<div class="preview-actions" data-preview-url="{{ route('admin.content.preview', ['type' => 'destinations', 'id' => $destination->id]) }}">
<span>Kaydetmeden metin önizlemesi:</span><button type="button" class="button secondary" data-content-preview="tr">Türkçe ↗</button><button type="button" class="button secondary" data-content-preview="en">English ↗</button><span class="help" data-preview-status role="status"></span>
</div>
@else
<p class="help">Önizleme için önce kaydı oluşturun. Yeni görseller formda önizlenir.</p>
@endif
<div class="form-actions">
<button class="button" data-submit-button><span data-submit-label>{{ $destination->exists ? 'Destinasyonu kaydet' : 'Destinasyon oluştur' }}</span><span data-submit-spinner class="spinner" hidden></span></button>
@if($destination->exists)<button type="button" class="button danger" onclick="document.getElementById('archive-destination-dialog').showModal()">Arşivle</button>@endif
</div>
</form>

@if($destination->exists)
<dialog id="archive-destination-dialog" class="confirm-dialog">
<form method="POST" action="{{ route('admin.destinations.destroy',$destination) }}">
@csrf @method('DELETE')
<div class="confirm-body">
<h3>Destinasyonu arşivle</h3>
<p>{{ $destination->title }} arşivlenecek. Alt kayıtları olan bir destinasyon silinemez — önce onları başka bir üst kayda taşıyın.</p>
<div class="confirm-actions">
<button type="button" class="button secondary" onclick="document.getElementById('archive-destination-dialog').close()">Vazgeç</button>
<button type="submit" class="button danger">Arşivle</button>
</div>
</div>
</form>
</dialog>
@endif

<style>
.rich-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:5px;padding:7px;border:1px solid #d8ddd3;border-bottom:0;background:#f8f9f6}.rich-toolbar button,.rich-toolbar select{min-height:34px;padding:6px 9px;border:1px solid #d8ddd3;background:white;color:#30362e;border-radius:2px;cursor:pointer}.rich-toolbar button:hover,.rich-toolbar select:hover{background:#eef1e9}.rich-toolbar select{max-width:180px}.rich-editor{min-height:180px;max-height:60vh;overflow:auto;border:1px solid #d8ddd3;background:#fff;padding:18px 20px;line-height:1.65;outline:none}.rich-editor:focus{border-color:#718267;box-shadow:0 0 0 2px #46583e18}.rich-editor h2,.rich-editor h3{line-height:1.3;margin:1.2em 0 .45em}.rich-editor p{margin:.6em 0}.rich-editor ul,.rich-editor ol{padding-left:1.5em}.rich-editor a{color:#46583e;text-decoration:underline}.rich-editor-field{margin-top:20px}
</style>
<script>
document.querySelectorAll('.rich-editor-field').forEach((field)=>{
 const editor=field.querySelector('.rich-editor'), input=field.querySelector('.rich-editor-value');let savedRange=null;
 const sync=()=>{input.value=editor.innerHTML};
 const rememberSelection=()=>{const selection=window.getSelection();if(selection.rangeCount&&editor.contains(selection.anchorNode))savedRange=selection.getRangeAt(0).cloneRange()};
 document.addEventListener('selectionchange',rememberSelection);
 field.querySelectorAll('[data-command]').forEach((control)=>{
  const run=()=>{const command=control.dataset.command;let value=control.value;
   if(savedRange){editor.focus();const selection=window.getSelection();selection.removeAllRanges();selection.addRange(savedRange)}else editor.focus();
   if(command==='createLink'){value=window.prompt('Bağlantı adresi:');if(!value)return;}
   if(command==='formatBlock')value='<'+value.toLowerCase()+'>';
   document.execCommand(command,false,value||null);sync();
  };
  control.addEventListener(control.tagName==='SELECT'?'change':'click',run);
  control.addEventListener('mousedown',rememberSelection);
 });
 editor.addEventListener('input',sync);editor.closest('form').addEventListener('submit',sync);sync();
});
</script>
@endsection
