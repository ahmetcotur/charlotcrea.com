# Charlot Crea — çalışma durumu

3 Kasım 2026. Ziyaretçi sitesi ve özel yönetim paneli aynı Laravel uygulamasında.
Yerel uygulama: http://localhost:8081; yönetim: /admin; müşteri girişi: /panel/login.

Tamamlanan işler:

- Teklif kabulünde otomatik Planlandı işi; tekrar kabulde aynı işi koruma,
  arşivlenmiş işi kopyalamama ve eski teklifler için uzlaştırma komutu.
- Görsel sürükle-bırak, çoklu önizleme, seçim kaldırma ve dosya kontrolleri;
  galeri sıralama, destinasyon arama ve seçim etiketleri.
- Başlıktan benzersiz adres üretimi; isim değişikliğinde mevcut adresi koruma.
- Kaydedilmemiş değişiklik uyarısı, editörde kelime/okuma sayacı, kayıtlı
  içeriklerde kaydetmeden metin önizlemesi.
- Site ayarlarından üç ana sayfa varyantını seçme; TR/EN ana adreslerini koruma.
- Her ana sayfada ayrı bölüm görünürlüğü, sırası, TR/EN metinleri ve veritabanı
  kayıtlarından otomatik/elle proje, film, referans ve destinasyon seçkileri.
- Ana sayfadaki destinasyonlar kısa ülke/şehir özeti gösterir. Otomatik seçki
  ülke düzeyindedir; uzun SEO metinleri detay sayfalarında kalır.
- Türkçe/İngilizce fotoğraf ve film SEO metinleri; Hizmetler bölümünden ilgili
  dilin güncel fotoğraf/film sayfalarına bağlantılar.
- 17 konumun 34 dil sürümü için özgün SEO başlıkları, açıklamalar, H1, içerik,
  SSS ve panelde düzenlenebilir hedef anahtar kelimeler. Önceki metinler özel
  yedekte saklanır; URL ve yayın durumları korunur.
- Mekke, Suudi Arabistan altında eklendi; Fairmont Makkah şehre bağlandı.
- 18 markalı proje referans/logolarıyla eşleştirildi. Balmy logosu eklendi;
  Architecture & Interiors genel seçkisi markasız bırakıldı.
- Referans logoları site zemininde tek renk gösterilir; sosyal medya footer'da
  logonun altındadır. Teklif ve müşteri paneli ayrı eylem kartlarıdır.
- Kısa iletişim formu ile ayrıntılı proje teklif formu ayrıldı; TR/EN çalışır.
  Talepler panelde İletişim / Teklif talebi olarak filtrelenir. Eski önizleme
  footer metni kaldırıldı.
- Ayrı müşteri hesabı/guard, tek kullanımlık 60 dakikalık davet, parola yenileme,
  erişim kapatma ve parola/e-posta değişiminde eski erişimi engelleme.
- Müşteri yalnızca kendi gönderilmiş tekliflerini, işlerini ve teslimlerini
  görür. Panelden teklif kabul/ret, teslimat onayı ve revizyon talebi kullanılabilir.
- Teslimatlar Google Drive bağlantılarıyla yapılır; yeni dosya yükleme kaldırıldı.
  Bağlantılar teslimde müşteriye açılır; düzenleme sonrası yeniden teslim gerekir.
- CRM panosunda yıl, tarih aralığı, çekim/teslim/kayıt tarihi, yaklaşan çekimler ve
  onaylanan işler filtreleri bulunur.
- Ayrı Laravel Docker yapısı, PostgreSQL, worker, kalıcı depolama ve yedek betiği.

Doğrulama:

- 173 test / 1.312 assertion hem SQLite hem PostgreSQL üzerinde geçti.
- Teklif/teslimat, hesap izolasyonu, davet/parola, özel dosya indirme ve formlar
  otomatik testlerle doğrulandı.
- Chromium'da izole kopya veritabanıyla davet → parola → giriş → yükleme → teslim
  → indirme → müşteri onayı ve teklif kabulü denendi. Gerçek müşteri verisi veya
  gerçek e-posta alıcısı kullanılmadı.
- İki dilin iletişim/teklif formları, mobil menü, referanslar, ana sayfa bölüm
  kontrolleri, kısa destinasyonlar ve footer kartları tarayıcıda denendi.
- Canlı SEO taraması 134 URL'de dil, başlık, meta, canonical, hreflang, JSON-LD ve
  sitemap kontrolünü geçti; son içerik/görünüm değişiklikleri sonrası yenilenir.
- Yerel SMTP kutusunda kuyruk bildirimi ve ayrı veritabanına yedek geri yükleme denendi.

Kalan işletim işleri:

- Gerçek SMTP sağlayıcısı, gönderen alan adı doğrulaması ve gerçek alıcıyla gönderim.
  Yerel varsayılan log mailer gerçek e-posta göndermez; davet mesajları kuyruğa alınır, bağlantı panelden de iletilebilir.
- Yayın sunucusu/alan adı, HTTPS ters proxy, yerel düzenlenmiş verilerin taşınması
  ve işletim ortamında zamanlanmış, sunucu dışında saklanan yedekler.
- Üç ana sayfa, Hakkımızda ve Hizmetler tamamen yerel Blade şablonları kullanır.
  Eski HTML yalnızca içe aktarma/yedek için tutulur; public render onu okumaz.

Kullanım: [CMS rehberi](cms-guide.md). Yayın: [Laravel yönergeleri](../deployment/laravel/README.md).

Dashboard güncellemesi: CRM özeti, yaklaşan çekimler, yayın/SEO alan kontrolü ve
ülke/cihaz/IP bazlı ilk taraf ziyaretçi analizleri eklendi. IP'ler şifreli saklanır;
90 günlük temizlik ve aylık yerel ülke veritabanı güncellemesi scheduler'a bağlıdır.
Kurulum: [Ziyaretçi analizleri](visitor-analytics.md).

Özel teslimat, sonuç, müşteri giriş/parola ve panel sayfaları ortak Charlot logosu,
renkler ve iletişim footer'ı kullanır. Paneldeki tekliflerden de yetkili PDF
indirmesi yapılabilir.


E-posta güncellemesi: ortak logolu HTML ve düz metin şablonları, müşteri kartında
TR/EN e-posta dili, form alındı + yönetici bildirimleri, panel daveti ve özel
önizleme ekranı eklendi. Müşteri bağlantıları ve teklif PDF'i seçili dilde açılır.
Gerçek gönderim için SMTP yapılandırması beklenir.
