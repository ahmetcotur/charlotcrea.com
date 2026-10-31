# Destinasyon içerik ve anahtar kelime planı

Bu ifadeler hizmet, konum ve mevcut proje eşleşmesinden türetilen başlangıç hedefleridir. Arama hacmi, rekabet veya sıralama verisi ölçülmemiştir. Canlı sitede Search Console verileri geldikçe öncelikler güncellenebilir.

| Sayfa | İngilizce odak | Türkçe odak | Mevcut proje kanıtı |
| --- | --- | --- | --- |
| Saudi Arabia | Saudi Arabia photography; hotel photography in Makkah; Makkah architectural photography | Suudi Arabistan fotoğraf çekimi; Mekke otel fotoğrafçılığı; Mekke mimari fotoğraf | Fairmont Makkah |
| Egypt | Egypt photography; hotel photography in Cairo; El Quseir resort photography | Mısır fotoğraf çekimi; Kahire otel fotoğrafçılığı; El Quseir resort çekimi | Fairmont Cairo, Mövenpick Resort El Quseir |
| Türkiye | Türkiye photography; hotel photography in Istanbul; Antalya photographer | Türkiye fotoğraf çekimi; İstanbul otel fotoğrafçılığı; Antalya fotoğrafçı | Fairmont Istanbul; Antalya çalışma merkezi |
| UAE | UAE photography; Dubai hotel photography; Abu Dhabi photography | BAE fotoğraf çekimi; Dubai otel fotoğrafçılığı; Abu Dhabi fotoğraf çekimi | Dubai ve Saadiyat galerileri |
| Dubai | Dubai photography; hotel photography in Dubai; Dubai interior photography | Dubai fotoğraf çekimi; Dubai otel fotoğrafçılığı; Dubai iç mekân fotoğraf | 25 Hours Dubai, Raffles The Palm, Rixos The Palm |

## Sayfa düzeni

- Destinasyon ve hizmeti belirten H1, kısa giriş ve arka planda dekoratif yer adı.
- Mevcut proje galerilerine doğrudan erişim.
- Bölgenin bağlamını açıklayan giriş; hizmet ve brief hazırlama üzerine üç ayrı içerik bölümü.
- İlgili projeler, görünür soru-cevaplar, proje talebi ve brief rehberi bağlantıları.
- Ülke veya şehir bağlamı için resmi turizm ve proje markası kaynaklarına bağlantılar.

Büyük arka plan yazısı yalnızca yer adıdır ve erişilebilirlik ağacından çıkarılmıştır. SEO içeriği okunabilir metin, başlıklar ve gerçek proje bağlantıları üzerinden sunulur. `meta keywords` etiketi üretilmez.

Başlık, açıklama, OG/Twitter metinleri ve sayfa schema açıklaması aynı içerikle eşleşir. JSON-LD'de yer bilgisi, hizmet alanı ve sayfada görülen sorular bulunur. FAQ işaretlemesi arama sonuçlarında özel bir görünüm garantisi vermez. Görünür içerikte veya schema'da yeni ofis, proje, sonuç verisi veya müşteri yorumu uydurulmaz.

## Düzenleme

`data/destinations.json`: iki dilde başlıklar, paragraflar, sorular, önerilen hedef ifadeler, proje eşleşmeleri ve kaynaklar.

`destinations.py`: üretilen iki dildeki sayfalara içeriği ve metadata/schema güncellemelerini uygular. `updated` alanı, gerçek editoryal değişiklik tarihinde güncellenir ve sitemap'e yansıtılır.

Demo `noindex` kalır. Bu çalışma, gerçek yayın için içerik temelini hazırlar; mevcut demoyu indekslemeye açmaz.
