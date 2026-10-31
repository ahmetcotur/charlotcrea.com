# Charlot Crea — website demo

Photography ve Film odaklı, Türkçe / İngilizce statik web sitesi demosu.

- 124 sayfa ve sayfa bazında dil geçişi
- 19 fotoğraf koleksiyonu, 205 farklı fotoğraf ve klavyeyle kullanılabilen galeriler
- 14 film ve 11 şehir destinasyonu; her biri Türkçe ve İngilizce sunulan 5 örnek blog yazısı
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
