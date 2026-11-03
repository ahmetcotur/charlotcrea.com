@extends('admin.layout')
@section('title',$post->exists ? 'Blog yazısı' : 'Yeni yazı')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Site içeriği</span><h1>{{ $post->exists ? $post->title : 'Yeni blog yazısı' }}</h1><p>Türkçe ve İngilizce metinleri, kapak görselini ve yayın tarihini düzenleyin.</p></div><a class="button secondary" href="{{ route('admin.posts.index') }}">← Bloga dön</a></div>

<form class="panel" method="POST" action="{{ $post->exists ? route('admin.posts.update',$post) : route('admin.posts.store') }}" enctype="multipart/form-data" data-loading-submit>
@csrf @if($post->exists)@method('PUT')@endif

<div class="form-grid">
<div class="field"><label style="display:flex;align-items:center;gap:8px"><input style="width:auto" type="checkbox" name="published" value="1" @checked(old('published',$post->published))> Yayında</label></div>
<div class="field"><label>Yayın tarihi</label><input type="date" name="published_at" value="{{ old('published_at',$post->published_at?->format('Y-m-d')) }}"></div>
</div>

<div class="field">
<label>Kapak görseli</label>
<div class="upload-field">
<img class="upload-preview" id="cover-preview" src="{{ $post->exists && $post->hasImage('cover') ? $post->image('cover','default',['w'=>200]) : '' }}" alt="" @if(! ($post->exists && $post->hasImage('cover'))) hidden @endif>
<input type="file" name="cover" accept="image/png,image/jpeg,image/gif" data-preview-target="#cover-preview">
@if($post->exists && $post->hasImage('cover'))
<label class="upload-remove"><input type="checkbox" name="remove_cover" value="1"> Kapağı kaldır</label>
@endif
</div>
</div>

@foreach(['tr'=>'Türkçe','en'=>'English'] as $locale=>$label)
@php($translation = $post->exists ? $post->translate($locale) : null)
<details class="panel" open><summary class="panel-title">{{ $label }}</summary>
<div class="field"><label>Başlık *</label><input name="title[{{ $locale }}]" required value="{{ old('title.'.$locale,$translation?->title) }}"></div>
<div class="form-grid">
<div class="field"><label>Sayfa adresi</label><input name="path[{{ $locale }}]" placeholder="Boş bırakırsanız başlıktan oluşturulur" value="{{ old('path.'.$locale,$translation?->path) }}"><div class="help">Örnek: /{{ $locale }}/blog/yazi-basligi/</div></div>
<div class="field"><label>Kategori</label><input name="category[{{ $locale }}]" value="{{ old('category.'.$locale,$translation?->category) }}"></div>
</div>
<div class="field"><label>Ana başlık</label><textarea name="heading[{{ $locale }}]" rows="2">{{ old('heading.'.$locale,$translation?->heading) }}</textarea></div>
<div class="field"><label>Giriş metni</label><textarea name="intro[{{ $locale }}]" rows="3">{{ old('intro.'.$locale,$translation?->intro) }}</textarea></div>

<div class="field rich-editor-field"><label for="body-editor-{{ $locale }}">Yazı içeriği</label><div class="rich-toolbar" role="toolbar" aria-label="Metin biçimlendirme araçları">
<select data-command="formatBlock" aria-label="Metin stili"><option value="P">Normal metin</option><option value="H2">Bölüm başlığı</option><option value="H3">Alt başlık</option><option value="H4">Küçük başlık</option><option value="BLOCKQUOTE">Alıntı</option></select>
<button type="button" data-command="bold" aria-label="Kalın"><strong>B</strong></button><button type="button" data-command="italic" aria-label="İtalik"><em>I</em></button><button type="button" data-command="underline" aria-label="Altı çizili"><u>U</u></button><span class="toolbar-divider"></span><button type="button" data-command="insertUnorderedList" aria-label="Madde işaretli liste">• Liste</button><button type="button" data-command="insertOrderedList" aria-label="Numaralı liste">1. Liste</button><button type="button" data-command="createLink" aria-label="Bağlantı ekle">Bağlantı</button><button type="button" data-command="removeFormat" aria-label="Biçimi temizle">Biçimi temizle</button></div>
<div id="body-editor-{{ $locale }}" class="rich-editor" contenteditable="true" role="textbox" aria-multiline="true" aria-label="{{ $label }} yazı içeriği">{!! \App\Services\RichText::clean(old('body.'.$locale,$translation?->body)) !!}</div><textarea class="rich-editor-value" name="body[{{ $locale }}]" hidden></textarea><div class="help">Bölüm başlıkları (H2) yazı içinde otomatik içindekiler listesi oluşturur.</div></div>

<div class="form-grid"><div class="field"><label>SEO başlığı</label><input name="seo_title[{{ $locale }}]" value="{{ old('seo_title.'.$locale,$translation?->seo_title) }}"></div><div class="field"><label>SEO açıklaması</label><textarea name="description[{{ $locale }}]" rows="3">{{ old('description.'.$locale,$translation?->description) }}</textarea></div></div>
</details>
@endforeach

@if($post->exists)
<div class="preview-actions" data-preview-url="{{ route('admin.content.preview', ['type' => 'posts', 'id' => $post->id]) }}">
<span>Kaydetmeden metin önizlemesi:</span><button type="button" class="button secondary" data-content-preview="tr">Türkçe ↗</button><button type="button" class="button secondary" data-content-preview="en">English ↗</button><span class="help" data-preview-status role="status"></span>
</div>
@else
<p class="help">Önizleme için önce kaydı oluşturun. Yeni görseller formda önizlenir.</p>
@endif
<div class="form-actions">
<button class="button" data-submit-button><span data-submit-label>{{ $post->exists ? 'Yazıyı kaydet' : 'Yazı oluştur' }}</span><span data-submit-spinner class="spinner" hidden></span></button>
@if($post->exists)<button type="button" class="button danger" onclick="document.getElementById('archive-post-dialog').showModal()">Arşivle</button>@endif
</div>
</form>

@if($post->exists)
<dialog id="archive-post-dialog" class="confirm-dialog">
<form method="POST" action="{{ route('admin.posts.destroy',$post) }}">
@csrf @method('DELETE')
<div class="confirm-body">
<h3>Yazıyı arşivle</h3>
<p>{{ $post->title }} arşivlenecek, sitede görünmeyecek.</p>
<div class="confirm-actions">
<button type="button" class="button secondary" onclick="document.getElementById('archive-post-dialog').close()">Vazgeç</button>
<button type="submit" class="button danger">Arşivle</button>
</div>
</div>
</form>
</dialog>
@endif

<style>
.rich-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:5px;padding:7px;border:1px solid #d8ddd3;border-bottom:0;background:#f8f9f6}.rich-toolbar button,.rich-toolbar select{min-height:34px;padding:6px 9px;border:1px solid #d8ddd3;background:white;color:#30362e;border-radius:2px;cursor:pointer}.rich-toolbar button:hover,.rich-toolbar select:hover{background:#eef1e9}.rich-toolbar select{max-width:180px}.toolbar-divider{height:22px;border-left:1px solid #d8ddd3;margin:0 3px}.rich-editor{min-height:260px;max-height:70vh;overflow:auto;border:1px solid #d8ddd3;background:#fff;padding:18px 20px;line-height:1.65;outline:none}.rich-editor:focus{border-color:#718267;box-shadow:0 0 0 2px #46583e18}.rich-editor h2,.rich-editor h3,.rich-editor h4{line-height:1.3;margin:1.2em 0 .45em}.rich-editor p{margin:.6em 0}.rich-editor ul,.rich-editor ol{padding-left:1.5em}.rich-editor blockquote{margin:1em 0;padding-left:1em;border-left:3px solid #aab59f;color:#65705e}.rich-editor a{color:#46583e;text-decoration:underline}.rich-editor-field{margin-top:20px}
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
