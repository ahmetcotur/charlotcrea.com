@extends('admin.layout')
@section('title','Site ayarları')
@section('content')
<div class="admin-heading"><div><span class="eyebrow">Genel ayarlar</span><h1>Site ayarları</h1><p>İletişim bilgileri, adres ve sosyal bağlantılar tüm sitede ortak kullanılır.</p></div></div>
<form class="panel" method="POST" action="{{ route('admin.site-settings.update') }}">@csrf @method('PUT')
<section class="homepage-settings">
<h2 class="panel-title">Aktif ana sayfa</h2>
<p class="muted">Seçtiğiniz tasarım Türkçe ve İngilizce ana sayfada yayınlanır. Ana sayfa adresleri aynı kalır.</p>
<div class="homepage-options">
@foreach(['home-1' => ['Ana sayfa 1', 'Klasik kapak', 'Geniş kapak görseliyle açılan özgün tasarım.'], 'home-2' => ['Ana sayfa 2', 'Fotoğraf şeridi', 'Fotoğraf seçkisini öne çıkaran tasarım.'], 'home-3' => ['Ana sayfa 3', 'Video', 'Arka plan videosuyla açılan tasarım.']] as $value => [$title, $type, $description])
<div class="homepage-option">
<label><input type="radio" name="homepage_variant" value="{{ $value }}" @checked(old('homepage_variant', $settings['homepage_variant']) === $value)><span><strong>{{ $title }}</strong><small>{{ $type }}</small></span></label>
<p>{{ $description }}</p>
<a href="{{ $value === 'home-1' ? '/tr/' : '/tr/'.$value.'/' }}" target="_blank" rel="noopener">Sayfayı aç ↗</a>
</div>
@endforeach
</div>
<p class="help">Seçimi uygulamak için site ayarlarını kaydedin. Sayfa içeriklerini Sayfalar bölümünden düzenleyebilirsiniz.</p>
</section>
<h2 class="panel-title">Marka ve iletişim</h2><div class="form-grid"><div class="field"><label>Marka adı *</label><input name="name" required value="{{ old('name',$settings['name']) }}"></div><div class="field"><label>Genel e-posta *</label><input type="email" name="email" required value="{{ old('email',$settings['email']) }}"></div><div class="field"><label>Telefon *</label><input name="telephone" required value="{{ old('telephone',$settings['telephone']) }}"></div><div class="field"><label>WhatsApp (isteğe bağlı)</label><input name="whatsapp_number" value="{{ old('whatsapp_number',$settings['whatsapp_number']) }}"></div></div>
<h2 class="panel-title" style="margin-top:26px">Adres ve harita</h2><div class="form-grid"><div class="field"><label>Sokak adresi</label><input name="street_address" value="{{ old('street_address',$settings['street_address']) }}"></div><div class="field"><label>İlçe</label><input name="district" value="{{ old('district',$settings['district']) }}"></div><div class="field"><label>Şehir / bölge</label><input name="city" value="{{ old('city',$settings['city']) }}"></div><div class="field"><label>Ülke kodu</label><input name="country" maxlength="2" value="{{ old('country',$settings['country']) }}"></div><div class="field"><label>Ülke adı</label><input name="country_name" value="{{ old('country_name',$settings['country_name']) }}"></div><div class="field"><label>Haritada aranacak adres</label><input name="map_query" value="{{ old('map_query',$settings['map_query']) }}"></div></div><div class="field"><label>Görünen tam adres</label><textarea name="address">{{ old('address',$settings['address']) }}</textarea></div><div class="field"><label>Yol tarifi bağlantısı (isteğe bağlı)</label><input type="url" name="map_url" value="{{ old('map_url',$settings['map_url']) }}"></div>
<h2 class="panel-title" style="margin-top:26px">Sosyal medya</h2><div class="form-grid">@foreach(['instagram_url'=>'Instagram','vimeo_url'=>'Vimeo','linkedin_url'=>'LinkedIn'] as $key=>$label)<div class="field"><label>{{ $label }} bağlantısı</label><input type="url" name="{{ $key }}" value="{{ old($key,$settings[$key]) }}"></div>@endforeach</div>
<div class="form-actions"><button class="button">Site ayarlarını kaydet</button></div></form>
@endsection
