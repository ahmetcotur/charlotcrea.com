# Drive portfolyosunun siteye aktarımı

Kaynak: Kullanıcının sağladığı “Ahmet Gül Portfolyo” Drive arşivi. Dosya ve klasör listeleri Google Drive bağlantısından okundu. Fotoğraflar proje klasörleri ve mimari portfolyo üzerinden eşleştirildi; görüntülerin web türevleri görsel incelemede kullanıldı.

## Yayınlanan seçki

- 19 fotoğraf koleksiyonu, toplam 205 farklı fotoğraf.
- 14 film: yatay Charlot Crea showreel’i, Balmy, Swandor, Sofitel, Mövenpick, Fairmont, Güral ve Rixos projeleri; Ali Bey Hotels’in Türkçe anlatımlı filmi.
- 4 ülke ve 11 şehir destinasyonu. Şehir sayfaları ilgili fotoğraf ve filmlere dayanır.
- 124 Türkçe / İngilizce sayfa; mevcut proje adresleri korunur.

Mevcut Fairmont Istanbul ve Fairmont Makkah projeleri korunmuştur. Drive’daki fotoğraflar, eski galerilerde aynı kareler tekrar eklenmeden birleştirilmiştir. Aynı fotoğraf hem bir proje hem de mimari seçkide yer alabilir; farklı fotoğraf sayısı dosya adresleri üzerinden hesaplanır.

## Proje eşleştirmeleri

| Kaynak klasör | Sitedeki karşılık |
| --- | --- |
| `24Hours_Dubai` | Mevcut sitedeki doğru marka adıyla 25 Hours Dubai |
| `Raffels_The Palm_Dubai` | Raffles The Palm |
| `Mövempick_Egypt` | Görseller ve mevcut referansla Mövenpick Resort El Quseir |
| `Rixos_Abu Dhabi_Sadiyad` | Rixos Premium Saadiyat |
| `Güral_Hotels_Turkey` | Genel Güral Hotels fotoğraf koleksiyonu; Belek/Tekirova filmleri ayrı |
| `Swandor Hotels_Turkey_Vietnam` | Çok konumlu Swandor Hotels koleksiyonu; tek tek fotoğraflara şehir atanmamıştır |
| `Rixos_Hotels` | Karma Rixos Hotels koleksiyonu; tek bir destinasyon gibi sunulmaz |
| `Ahmet Gül Mimari_Portfolio` | Architecture & Interiors / Mimari & İç Mekân |

BALMY Beach Resort Kemer, Rixos JBR, Tekirova, Downtown Antalya, Sofitel Winter Palace, Old Cataract ve Th8 Palm klasörleri kendi koleksiyonları olarak yayımlanmıştır.

Rixos Premium Seagate ve Magawish filmlerinin şehir eşleşmeleri otel grubunun resmi sayfalarıyla kontrol edilmiştir:

- [Rixos Premium Seagate — Sharm El Sheikh](https://all.accor.com/hotel/B1J8/index.en.shtml)
- [Rixos Premium Magawish — Hurghada](https://all.accor.com/hotel/B6F4/index.en.shtml)

## Seçki dışında tutulanlar

- `.DS_Store` ve kamera dosya adlarının sayfa başlığına taşınması.
- “pregrade” olarak işaretlenmiş çalışma sürümleri.
- Aynı showreel’in kopyaları ve telefonu döndürme yönergesiyle başlayan dikey sürümü; site yatay sürümü kullanır.
- Adsız `VIDEO-2022...` klipleri ve proje/iş türü kesinleştirilmemiş `EVOHE` dosyası.
- Ana klasördeki konumu belirsiz fotoğraflara tahmini otel/şehir isimleri verilmesi.

Kaynak Drive arşivinin düzeni, dosyaları ve paylaşım izinleri değiştirilmemiştir. İmzalı indirme adresleri veya erişim tokenleri kaynak koduna eklenmez.

## Sunum ve performans

Fotoğraf oranları korunur. Kartlar sınırlı yükseklikte gösterilir; galerilerde kırpma ve otomatik yakınlaştırma yoktur. Tam kareler tıklanarak açılır. Yeni fotoğraflarda küçük/büyük web kopyaları, `srcset`, boyut bilgileri ve ertelenmiş yükleme kullanılır.

13 film, web için hazırlanmış MP4 kopyasıyla doğrudan sitede oynar. Büyük Ali Bey Hotels dosyası Google Drive oynatıcısıyla açılır. Film dosyaları sayfa açılışında otomatik indirilmez veya oynatılmaz. Oynatıcı kullanıcı tıklamasıyla açılır; kapatılınca video durur. Google Drive üzerinden açılan dosyalar için aynı pencerede oynatıcı ve yeni sekmede açma bağlantısı bulunur. Sitede sunulan MP4 kopyaları H.264/AAC ve fast-start ile hazırlanır; Nginx byte-range yanıtlarını destekler.

Her film kendi sayfasına, VideoObject verisine ve video sitemap kaydına sahiptir. VideoObject dil bilgisi yalnızca kaynakta açıkça bilinen anlatım dili için yazılır. Şehir sayfaları ilgili işlerle karşılıklı bağlanır. Bütün sosyal paylaşım görselleri Charlot Crea logosu olarak kalır.

Düzenlenebilir katalog: `data/portfolio.json`. Şehir metinleri: `data/portfolio-cities.json`. Sayfa üretimi: `portfolio.py` ve `site_pages.py`.
