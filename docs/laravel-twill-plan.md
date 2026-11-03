> 3 Kasım 2026 güncellemesi: Bu belge ilk geçiş planını ve geçmiş aşamaları içerir.
> Güncel uygulama durumu ve kalan işler için [proje durumuna](project-status.md),
> kullanım için [CMS rehberine](cms-guide.md) bakın.

# Charlot Crea — Laravel paneli geçiş planı

## Kabul edilen mimari

Tek depoda tek Laravel uygulaması: Blade ile ziyaretçi sitesi ve Laravel
controller/Blade ile özel yönetim paneli (/admin). Astro, ayrı API sunucusu veya
ikinci frontend uygulaması yoktur. Twill arayüzü artık ana panel değildir;
geçiş sırasında eski ekranlar /legacy-cms altında tutulmaktadır.

Laravel 13; üretimde PostgreSQL, yerel hızlı başlangıçta SQLite. Özel site
CSS/JS'si korunur. Twill paketinin model ve medya bağımlılıkları henüz tamamen
çıkarılmadı; bu, sonraki refaktör aşamasıdır.

## 1. İçerik yönetimi ve site

- TR/EN sayfalar, fotoğraf projeleri, filmler, ülke/şehir destinasyonları, blog.
- Twill medya kütüphanesinde yeni dosya yükleme, kapak seçimi ve galeri sıralama.
- JSON kaynaklarını tekrar çalıştırılabilir ve kayıpsız bir komutla aktar.
  Normal çalıştırma paneldeki düzenlemelerin üzerine yazmamalı.
- Mevcut URL'ler, 301 kuralları, hreflang, sitemap, JSON-LD ve tasarım korunur.
- Kademeli geçişte henüz Blade'e taşınmamış sayfalar mevcut HTML çıktısından
  sunulur. Bu geçici uyumluluk katmanı CMS tamamlandı diye raporlanmaz.
- Taslaklar ve yönetici oturumları ortak sayfa önbelleğine alınmaz.
- İçerik yayınında bağımlı liste/detay/destinasyon önbellekleri geçersizleşir.
- Mevcut Home 2 ve Home 3 farklı bölüm sıralamalarını korur; ortak gövdeye
  indirgenmez. Varyantlar TR/EN düzenlenebilir ve aktif ana sayfa seçilebilir.

## 2. Müşteri ve iş akışı

Müşteri hesapları Twill yöneticilerinden ayrı guard/policy kullanır. Her müşteri
sadece kendisine ait teklif, iş ve teslimleri görebilir. Teklif tutarları ondalık
para alanlarında saklanır; sürümleme, onay ve durum geçişleri kayda alınır.
İşler ve teslimler müşteriyle ilişkilendirilir. Teslimler özel disktedir; public
medya kütüphanesine konmaz. Yetki kontrolünden sonra süreli indirme sağlanır.

## 3. Mail ve işletim

Teklif/teslim bildirimleri veritabanı kuyruğunda, aynı uygulamanın ayrı worker
sürecinde çalışır. Gönderim kaydı, yeniden deneme ve mükerrer gönderim koruması
olur. SMTP/sağlayıcı bilgileri yalnızca ortam değişkenleriyle alınır.
Başlangıçta Redis zorunlu değildir. Büyük medya için S3 uyumlu depolama ve CDN
sonradan aynı disk arayüzünden eklenebilir. Performans ölçümle doğrulanır.

## Geçiş ve doğrulama

Mevcut commit edilmemiş tasarım değişiklikleri ve Python üreticisi korunur.
Yeni uygulama doğrulanmadan mevcut Docker/demo yayını değiştirilmez.
Migration, idempotent import, admin erişimi, TR/EN içerik düzenleme, medya,
404/301, SEO ve müşteri izolasyonu ilgili aşamada test edilir.
Parolalar depoya veya dokümantasyona yazılmaz; yönetici CLI ile oluşturulur.

## Uygulama durumu — içerik ve hiyerarşi

Tamamlandı:

- Laravel/Twill kurulumu, iki dilde 65 sayfa kaydı, 14 referans ve medya aktarımı.
- Fotoğraf, film, destinasyon, blog, referans liste/detaylarının canlı Blade
  şablonları ve iletişim sayfası (120 mevcut URL). Yeni içerikler yeniden build gerektirmez.
- Destinasyon ve sayfalarda ağaç görünümü, üst kayıt seçimi, anlık sürükleyerek
  sıralama, döngü ve üst kayıt silme koruması.
- Ülke → çekim noktası bağlı seçicileri; formdan ayrılmadan yeni ülke/nokta ekleme.
  Ülke/nokta eşleşmesi sunucuda doğrulanır; yeni destinasyon taslak oluşturulur.
- Şehirdeki çalışmaların bölge/ülkede görünmesi; listelerde yayın, dil, güncel URL
  ve kapak durumunun veritabanından okunması.
- Referans adı/logo/URL/sıra/yayın yönetimi, özgün 14 logonun kayıpsız aktarımı.
- Canlı metadata, hreflang, JSON-LD, breadcrumb, sitemap; içerik önbelleği ve
  düzenleme sonrası geçersizleştirme. Canlı SEO kontrol komutu eklenmiştir.

Bu aşamada tamamlanan ekler:

- Sayfalar için gruplu/aranabilir genel görünüm, okunabilir adlar ve modül kısayolları.
- Sayfa türüne göre alanlar; yeni sayfa metin editörü ve otomatik TR/EN adresleri.
- Kaydetmeden, cache'e girmeyen ve indekslenmeyen taslak önizleme.
- Ana sayfa kapak, fotoğraf şeridi sırası ve video/poster düzenlemesi.
- Site ayarları: marka, iletişim, WhatsApp, adres/harita ve sosyal medya.
  Ayarlar iletişim, footer ve schema'da ortak; güncelleme cache'i geçersizleştirir.

Kalan işler:

- Ana sayfa/Hakkımızda/Hizmetler metinleri panelden düzenlenir; bölüm yapısı ve
  görsel seçkileri için görsel bölüm editörü ile uyumluluk katmanından Blade'e geçiş.
- PostgreSQL/Docker yayın yapılandırması.
- Müşterilerin oturum açacağı alan ve teklifleri görüntüleme/onaylama akışı.
- Müşteriye açık iş takibi, özel dosya teslimi ve otomatik mail bildirimleri.

## 3. CRM başlangıcı

Twill içindeki **Müşteriler**, **Teklifler**, **İşler** ve **İletişim talepleri**
modülleri eklendi. Müşteri kaydı
isim, firma, e-posta, telefon, ülke ve iç notları tutar. Teklif; müşteriye bağlı
başlık/numara, kapsam, tutar/para birimi, geçerlilik tarihi ve taslak, gönderildi,
kabul edildi, reddedildi veya süresi doldu durumlarından birini taşır. Durum
geçişleri zaman ve yönetici bilgisiyle kaydedilir. Bu aşama panel içi CRM'dir;
müşteri girişi, dışarı açık teklif/onay, iş ve dosya teslim akışları sonraki
aşamadadır.

Web iletişim brief'i de CRM'e bağlandı. Gönderimler **İletişim talepleri** Twill
modülünde saklanır; bilinen e-posta ile müşteri adayı eşleştirilir veya yeni bir
müşteri kaydı açılır. Gönderim hâlâ e-posta bildirimi yollamaz. Dashboard, yeni
talep/müşteri/açık teklif/aktif iş sayılarını ve yönetim kısayollarını gösterir.

Mevcut statik Docker/Netlify yayını korunmuştur. Kullanım: [CMS rehberi](cms-guide.md).
