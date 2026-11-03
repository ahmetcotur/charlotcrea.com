> Güncel Laravel paneli ve tamamlanan işler: [proje durumu](docs/project-status.md).
> Kullanım: [CMS rehberi](docs/cms-guide.md). PostgreSQL/Docker yayını:
> [yayın rehberi](deployment/laravel/README.md). Aşağıdaki statik site ve demo
> notları eski yayın hattını da belgeler.

# Charlot Crea — Laravel

Ziyaretçi sitesi ve özel Laravel yönetim paneli aynı uygulamadadır.
Yerel çalışma adresi `http://localhost:8081`, yönetim paneli `/admin`.
Twill arayüzü ana yönetim paneli olmaktan çıkarıldı. Eski Twill ekranları
geçiş süresince `/legacy-cms` altında durur; Twill model/medya bağımlılıklarının
tamamen kaldırılması ayrı refaktör işidir.
PHP 8.3+ ve Composer gerekir. Twill'in hazır panel varlıkları kullanıldığı için
bu aşamada Node/npm build gerekmez.

```sh
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan twill:install --no-interaction
php artisan charlot:assets
php artisan storage:link
php artisan charlot:import
php artisan charlot:import-media
php artisan charlot:local-admin
php artisan serve --port=8081
```

Bu makinede Composer için `composer` yerine `php .tools/composer` kullanılabilir.
Yerel yönetici bilgileri `storage/app/private/local-admin.txt` içinde, yalnızca
sahibine okunabilir izinlerle tutulur ve Git'e dahil edilmez. Üretim yöneticisi
`php artisan twill:superadmin` ile etkileşimli oluşturulur.

## Mevcut geçiş durumu

- Twill'de sayfa, portföy, film, destinasyon ve blog modülleri; EN/TR alanları.
- 65 iki dilli kayıt (130 sayfa), orijinal içerik verileri ve medya bağlantıları.
- Başlık, giriş, SEO alanları; blog/destinasyon metinleri; portföy galerileri
  panelden düzenlenebilir. Yayın durumu ve içerik değişikliği önbelleği yeniler.
- Yeni görsel yükleme Twill medya kütüphanesindedir. Eski galeri görselleri
  `charlot:import-media` ile kütüphaneye alınır; tekrar çalıştırmak editörün
  seçimini değiştirmez.
- `charlot:import` mevcut düzenlemeleri korur. `--overwrite` paneldeki değişiklikleri
  kaynak verilerle değiştirir; yalnızca bilinçli veri sıfırlamada kullanılır.
- `/sitemap.xml` yayınlanan kayıtlardan, eski URL'lerin 301 kuralları veritabanından
  üretilir. Demo varsayılan olarak indekslemeye kapalıdır.

Fotoğraf, film, destinasyon, blog ve referansların liste/detayları canlı Blade
şablonlarına taşındı. Destinasyon ve sayfalar ağaç olarak yönetilir. Portföy
formunda ülke → çekim noktası bağlı seçicileri ve formdan ayrılmadan yeni yer
oluşturma vardır. Referans ad/logo/URL/sıra/yayın yönetimi `/admin/references`
adresindedir. Kullanım: [CMS rehberi](docs/cms-guide.md).

Sayfaların genel görünümü `/admin/page-manager`, ortak iletişim/adres/harita
ayarları `/admin/site-settings` adresindedir. Yeni içerik sayfalarında otomatik
TR/EN adresleri ve kaydetmeden önizleme; ana sayfalarda kapak, fotoğraf şeridi ve
video/poster düzenlemesi vardır. Ana sayfalar, Hakkımızda ve Hizmetler için tüm
bölüm başlıkları/açıklamaları EN/TR olarak düzenlenir; özgün görsel düzen korunur.

**CRM:** `/admin` dashboard'unda müşteri, teklif ve iş özetleri bulunur. Müşteriler,
teklifler, işler ve iletişim talepleri aynı girişle yönetilir. Web brief formu
gönderimleri **İletişim talepleri** modülüne kaydeder ve müşteriyi e-posta adresiyle
eşleştirir. `[ÖRNEK]` etiketli kayıtlar panel akışını denemek içindir.

Müşterilerin giriş yapacağı portal, teklif onayı, özel dosya teslimi ve e-posta
bildirimleri henüz eklenmedi. Ana sayfa varyantları/Hakkımızda/Hizmetler hâlâ
`PageRenderer` uyumluluk katmanını kullanır; metinler düzenlenebilir, görseller
ve portföy kayıtları kendi medya/içerik modüllerinden yönetilir.

Mevcut Dockerfile/Netlify yapılandırması eski statik demoyu yayınlamaya devam eder;
yeni Laravel uygulamasını yayınlamaz. Yerel `charlot:assets` komutu dosyaları
symlink ile bağlar. Üretim paketi bu bağlantılara hedef olan medya dosyalarını da
içermeli, public web kökü yalnızca `public/` olmalıdır. PostgreSQL ve PHP web/worker
üretim yapılandırması geçişin yayın aşamasında tamamlanacaktır.

```sh
php artisan test
python3 tests/check_seo_live.py --base-url http://localhost:8081
```

Mimari ve aşamalar: [Laravel + Twill planı](docs/laravel-twill-plan.md).

---

# Charlot Crea — website demo

Photography ve Film odaklı, Türkçe / İngilizce statik web sitesi demosu.

- 126 sayfa ve sayfa bazında dil geçişi
- 19 fotoğraf koleksiyonu, 205 farklı fotoğraf ve klavyeyle kullanılabilen galeriler
- 14 film ve 12 şehir destinasyonu; her biri Türkçe ve İngilizce sunulan 5 örnek blog yazısı
- Logo reveal, ticker, kayan referanslar ve kart geçişleri
- İletişim bilgileri, Antalya adresine göre Google Maps haritası
- Sayfaya özel metadata, Open Graph, JSON-LD, hreflang ve görselli sitemap
- Eski adresler için kalıcı yönlendirmeler

## Yerelde çalıştırma

Python 3.9 veya üzeri yeterlidir; ek Python paketi gerekmez.

```sh
python3 build.py
python3 serve.py
```

- Türkçe: http://localhost:8080/tr/
- English: http://localhost:8080/en/
- Blog: http://localhost:8080/tr/blog/

`serve.py`, yönlendirmeleri gerçek HTTP 301 yanıtlarıyla uygular. Standart `python -m http.server` aynı yönlendirme desteğini sağlamaz.

## Kontrol

```sh
python3 tests/check_seo.py
```

## Demo yayını

Demo önizlemeleri arama motorlarına kapalıdır. Farklı bir önizleme alan adı için:

```sh
python3 build.py --site-url https://demo.example.com
```

Yayın kökü `dist/` klasörüdür. Apache için `.htaccess`, Netlify için `_redirects` üretilir. Diğer sunucularda `dist/redirects.json` kuralları HTTP 301 olarak uygulanmalıdır. Netlify depo içe aktarımı için `netlify.toml` hazırdır.

Gerçek alan adına geçişte:

```sh
python3 build.py --production --site-url https://www.charlotcrea.com
```

Bu komut indekslemeyi açar ve canonical, OG, schema, sitemap adreslerini yayın alan adına göre üretir. Bu depo henüz gerçek alan adına dağıtım yapmaz.

## Düzenleme

| Kaynak | İçerik |
| --- | --- |
| `build.py`, `enhance.py` | Ana sayfa yapısı ve portfolyo |
| `seo.py`, `localize.py`, `polish.py` | SEO, iki dil, blog ve görsel düzenlemeler |
| `data/site.json` | Marka, iletişim ve yayın adresleri |
| `data/blog.json` | Beş yazının iki dildeki içerikleri |
| `data/destinations.json`, `destinations.py` | İki dilde destinasyon metinleri, hedef ifadeler, kaynaklar ve yer/hizmet schema yapıları |
| `data/tr.json` | Türkçe arayüz metinleri |
| `data/galleries.json` | İlk portfolyonun kaynak bilgileri |
| `data/portfolio.json`, `portfolio.py`, `site_pages.py` | Drive seçkisi, fotoğraf/film sayfaları ve karşılıklı bağlantılar |
| `data/portfolio-cities.json` | Doğrulanmış projelerle desteklenen şehir sayfalarının iki dildeki metinleri |
| `dist/work.css`, `dist/work.js` | Portfolyo tasarımı ve film oynatıcıları |
| `dist/editorial.css`, `dist/portfolio.css`, `dist/brand.css` | Ek arayüz stilleri |
| `dist/site.js`, `dist/gallery.js`, `motion.js` | Dil davranışları, galeri ve animasyonlar |

Form, brief'i tarayıcıda hazırlar ve dosya olarak indirir; e-posta veya CRM bağlantısı henüz yoktur. Google Maps ve Google Fonts internet bağlantısı gerektirir.

Fotoğraflar ve marka logoları mevcut Charlot Crea sitesinden bu demo için alınmıştır. Ayrıntılı Türkçe notlar: [README-TR.txt](README-TR.txt).

## Coolify / Docker demo

Coolify'da **Dockerfile** build pack ve `8080` container portu kullanılır. Sağlık kontrolü `/en/` adresidir. Build-time `SITE_URL` demo origin'ine ayarlanır; varsayılan değer `https://charlot-demo.5.189.135.4.sslip.io`.

```sh
docker build --build-arg SITE_URL=https://demo.example.com -t charlot-demo .
docker run --rm -p 8090:8080 charlot-demo
```

Nginx gerçek 301/404 yanıtları üretir ve bütün demo yanıtlarında `X-Robots-Tag: noindex, nofollow` gönderir. Bu Dockerfile demo yayını içindir; gerçek alan adına indekslenebilir yayın için ayrıca yayın yapılandırması yapılmalıdır. Coolify tokenleri repoda tutulmaz.

Destinasyonların anahtar kelime ve içerik haritası: [docs/destination-seo.md](docs/destination-seo.md).

Drive portfolyosunun seçimi ve içerik eşleştirmesi: [docs/portfolio-import.md](docs/portfolio-import.md).
