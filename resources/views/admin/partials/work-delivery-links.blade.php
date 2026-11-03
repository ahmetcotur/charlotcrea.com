<section class="panel"><h2 class="panel-title">Google Drive teslimat bağlantıları</h2>
<p class="muted">Fotoğraf ve videoları Google Drive üzerinden teslim edin. Bağlantılar, Teslim et işlemine kadar müşteriye gösterilmez. Drive paylaşım izinlerini müşterinizin erişebileceği şekilde ayarlayın.</p>
@forelse($work->deliveryLinks as $link)
<div class="revision-card">
<div class="toolbar"><strong>{{ $link->title }}</strong><span class="badge {{ $link->released_at ? 'badge-completed' : 'badge-draft' }}">{{ $link->released_at ? 'Müşteriye açık' : 'Henüz teslim edilmedi' }}</span></div>
<a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" style="overflow-wrap:anywhere">Drive'da aç ↗</a>
<details style="margin-top:12px"><summary>Bağlantıyı düzenle</summary><form method="POST" action="{{ route('admin.works.delivery-links.update',[$work,$link]) }}">@csrf<input type="hidden" name="_delivery_tab" value="1"> @method('PUT')<div class="field"><label for="link-title-{{ $link->id }}">Başlık</label><input id="link-title-{{ $link->id }}" name="title" value="{{ $link->title }}" required maxlength="150"></div><div class="field"><label for="link-url-{{ $link->id }}">Google Drive bağlantısı</label><input id="link-url-{{ $link->id }}" name="url" type="url" value="{{ $link->url }}" required maxlength="2000"></div><p class="help">Değiştirilen bağlantı yeniden teslim edilene kadar müşteriye kapatılır.</p><button class="button secondary">Güncelle</button></form></details>
<form method="POST" action="{{ route('admin.works.delivery-links.destroy',[$work,$link]) }}" style="margin-top:12px">@csrf<input type="hidden" name="_delivery_tab" value="1"> @method('DELETE')<button class="button danger">Bağlantıyı kaldır</button></form>
</div>
@empty<p class="muted">Henüz teslimat bağlantısı eklenmedi.</p>@endforelse
<form method="POST" action="{{ route('admin.works.delivery-links.store',$work) }}" data-loading-submit>@csrf<input type="hidden" name="_delivery_tab" value="1">
<div class="field"><label for="delivery-link-title">Teslimat başlığı</label><input id="delivery-link-title" name="title" value="{{ old('title') }}" placeholder="Örn. Final fotoğraflar / Film / Revizyon 2" required maxlength="150"></div>
<div class="field"><label for="delivery-link-url">Google Drive bağlantısı</label><input id="delivery-link-url" type="url" name="url" value="{{ old('url') }}" placeholder="https://drive.google.com/drive/folders/..." required maxlength="2000"><p class="help">Google Drive klasör veya dosya paylaşım bağlantısını yapıştırın.</p></div>
<button class="button" data-submit-button><span data-submit-label>Bağlantıyı ekle</span><span data-submit-spinner class="spinner" hidden></span></button></form>
</section>
