@extends('admin.layout')
@section('title',$page->exists ? ($page->management_title ?? 'Sayfa') : 'Yeni sayfa')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>{{ $page->exists ? ($page->management_title ?? 'Sayfayı düzenle') : 'Yeni sayfa' }}</h1><p>{{ $info['help'] ?? 'Sayfanın Türkçe ve İngilizce metinlerini ekleyin.' }}</p></div><a class="button secondary" href="{{ route('admin.page-manager') }}">← Sayfalara dön</a></div>
@php($mediaType = $page->exists ? ($info['media'] ?? null) : 'cover')
<form class="panel" method="POST" action="{{ $page->exists ? route('admin.pages.update',$page) : route('admin.pages.store') }}" enctype="multipart/form-data" data-loading-submit>@csrf @if($page->exists)@method('PUT')@endif
@if($page->exists)<label class="field"><input style="width:auto" type="checkbox" name="published" value="1" @checked(old('published',$page->published))> Sayfayı yayında tut</label>@endif

<div class="field"><label>Üst sayfa</label>
<select name="parent_id">
<option value="">— Yok (en üst seviye) —</option>
@php($depths = [])
@foreach($parents as $p)
@php($depths[$p->id] = $p->parent_id ? ($depths[$p->parent_id] ?? 0) + 1 : 0)
<option value="{{ $p->id }}" @selected(old('parent_id',$page->parent_id)==$p->id)>{{ str_repeat('— ', $depths[$p->id]) }}{{ $p->title }}</option>
@endforeach
</select>
</div>

@if(in_array($mediaType, ['cover', 'video']))
<div class="field">
<label>Sayfa görseli{{ $mediaType === 'video' ? ' (video arka planı için poster)' : '' }}</label>
<div class="upload-field">
<img class="upload-preview" id="cover-preview" src="{{ $page->exists && $page->hasImage('cover') ? $page->image('cover','default',['w'=>200]) : '' }}" alt="" @if(! ($page->exists && $page->hasImage('cover'))) hidden @endif>
<input type="file" name="cover" accept="image/png,image/jpeg,image/gif" data-preview-target="#cover-preview">
@if($page->exists && $page->hasImage('cover'))
<label class="upload-remove"><input type="checkbox" name="remove_cover" value="1"> Görseli kaldır</label>
@endif
</div>
</div>
@endif

@if($mediaType === 'video')
<div class="field"><label>Arka plan videosu</label><input name="video_url" value="{{ old('video_url',$page->video_url) }}" placeholder="https://.../video.mp4"></div>
@endif

@if($mediaType === 'gallery')
<div class="field">
<label>Şeritteki fotoğraflar</label>
@if($page->exists)
@php($galleryMedia = $page->medias->filter(fn($m)=>$m->pivot->role==='gallery'))
@if($galleryMedia->isNotEmpty())
<p class="help">Fotoğrafların sırasını oklarla değiştirin. Değişiklikler kaydettiğinizde uygulanır.</p>
<div class="gallery-grid" data-gallery-sort>
@foreach($galleryMedia as $media)
<div class="gallery-grid-item">
<input type="hidden" name="gallery_order[]" value="{{ $media->id }}">
<div class="gallery-sort-actions"><button type="button" data-gallery-move="-1" aria-label="Görseli önceye taşı">←</button><button type="button" data-gallery-move="1" aria-label="Görseli sonraya taşı">→</button></div>
<img src="{{ $page->image('gallery','default',['w'=>240],false,false,$media) }}" alt="">
<label><input type="checkbox" name="remove_gallery[]" value="{{ $media->id }}"> Sil</label>
</div>
@endforeach
</div>
@endif
@endif
<input type="file" name="gallery[]" accept="image/png,image/jpeg,image/gif" multiple>
</div>
@endif

@include('admin.partials.home-sections', ['sections' => $homeSections ?? [], 'choices' => $sectionChoices ?? []])

@foreach(['tr'=>'Türkçe','en'=>'English'] as $locale=>$label)@php($translation=$page->translate($locale))<details class="panel" open><summary class="panel-title">{{ $label }}</summary>
<div class="field"><label>Panelde görünen sayfa adı *</label><input name="title[{{ $locale }}]" {{ $locale==='en' ? 'required' : '' }} value="{{ old('title.'.$locale,$translation?->title) }}"></div><div class="field"><label>Sayfa adresi</label><input name="path[{{ $locale }}]" value="{{ old('path.'.$locale,$translation?->path) }}"><div class="help">Örnek: /tr/ornek-sayfa/</div></div>@if(empty($homeSections))<div class="field"><label>Ana başlık</label><textarea name="heading[{{ $locale }}]" rows="2">{{ old('heading.'.$locale,$translation?->heading) }}</textarea></div><div class="field"><label>Giriş metni</label><textarea name="intro[{{ $locale }}]" rows="3">{{ old('intro.'.$locale,$translation?->intro) }}</textarea></div>@endif
@if(empty($homeSections) && !in_array($page->import_key, ['/en/contact/', '/en/request-a-quote/']))
<div class="field rich-editor-field"><label for="body-editor-{{ $locale }}">{{ in_array($page->import_key, ['/en/photography/', '/en/film/', '/en/destinations/']) ? 'SEO içeriği' : 'Tüm bölüm metinleri' }}</label><div class="rich-toolbar" role="toolbar" aria-label="Metin biçimlendirme araçları">
<select data-command="formatBlock" aria-label="Metin stili"><option value="P">Normal metin</option><option value="H2">Bölüm başlığı</option><option value="H3">Alt başlık</option><option value="H4">Küçük başlık</option><option value="BLOCKQUOTE">Alıntı</option></select>
<button type="button" data-command="bold" aria-label="Kalın"><strong>B</strong></button><button type="button" data-command="italic" aria-label="İtalik"><em>I</em></button><button type="button" data-command="underline" aria-label="Altı çizili"><u>U</u></button><span class="toolbar-divider"></span><button type="button" data-command="insertUnorderedList" aria-label="Madde işaretli liste">• Liste</button><button type="button" data-command="insertOrderedList" aria-label="Numaralı liste">1. Liste</button><button type="button" data-command="createLink" aria-label="Bağlantı ekle">Bağlantı</button><button type="button" data-command="removeFormat" aria-label="Biçimi temizle">Biçimi temizle</button></div>
<div id="body-editor-{{ $locale }}" class="rich-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ $label }} bölüm metinleri">{!! \App\Services\RichText::clean(old('body.'.$locale,$translation?->body)) !!}</div><textarea class="rich-editor-value" name="body[{{ $locale }}]" hidden></textarea><div class="help">Metni seçip araç çubuğundan başlık, kalın yazı, liste veya bağlantı biçimi verin. Bölümlerin sırasını koruyun; sayfa düzeni otomatik olarak korunur.</div></div>
@endif
<div class="form-grid"><div class="field"><label>SEO başlığı</label><input name="seo_title[{{ $locale }}]" value="{{ old('seo_title.'.$locale,$translation?->seo_title) }}"></div><div class="field"><label>SEO açıklaması</label><textarea name="description[{{ $locale }}]" rows="3">{{ old('description.'.$locale,$translation?->description) }}</textarea></div></div></details>@endforeach
@if($page->exists)
<div class="preview-actions" data-preview-url="{{ route('admin.content.preview', ['type' => 'pages', 'id' => $page->id]) }}">
<span>Kaydetmeden metin önizlemesi:</span><button type="button" class="button secondary" data-content-preview="tr">Türkçe ↗</button><button type="button" class="button secondary" data-content-preview="en">English ↗</button><span class="help" data-preview-status role="status"></span>
</div>
@else
<p class="help">Önizleme için önce kaydı oluşturun. Yeni görseller formda önizlenir.</p>
@endif
<div class="form-actions"><button class="button" data-submit-button><span data-submit-label>{{ $page->exists ? 'Sayfayı kaydet' : 'Taslak oluştur' }}</span><span data-submit-spinner class="spinner" hidden></span></button></div></form>
<style>
.rich-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:5px;padding:7px;border:1px solid #d8ddd3;border-bottom:0;background:#f8f9f6}.rich-toolbar button,.rich-toolbar select{min-height:34px;padding:6px 9px;border:1px solid #d8ddd3;background:white;color:#30362e;border-radius:2px;cursor:pointer}.rich-toolbar button:hover,.rich-toolbar select:hover{background:#eef1e9}.rich-toolbar select{max-width:180px}.toolbar-divider{height:22px;border-left:1px solid #d8ddd3;margin:0 3px}.rich-editor{min-height:320px;max-height:70vh;overflow:auto;border:1px solid #d8ddd3;background:#fff;padding:18px 20px;line-height:1.65;outline:none}.rich-editor:focus{border-color:#718267;box-shadow:0 0 0 2px #46583e18}.rich-editor h2,.rich-editor h3,.rich-editor h4{line-height:1.3;margin:1.2em 0 .45em}.rich-editor p{margin:.6em 0}.rich-editor ul,.rich-editor ol{padding-left:1.5em}.rich-editor blockquote{margin:1em 0;padding-left:1em;border-left:3px solid #aab59f;color:#65705e}.rich-editor a{color:#46583e;text-decoration:underline}.rich-editor-field{margin-top:20px}
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
   if(command==='createLink'){value=window.prompt('Bağlantı adresi (https://, mailto: veya /adres/):');if(!value)return;}
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
