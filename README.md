# Charlot Crea — website demo

Photography ve Film odaklı, Türkçe / İngilizce statik web sitesi demosu.

- 54 sayfa ve sayfa bazında dil geçişi
- 8 fotoğraf projesi, 71 fotoğraf ve klavyeyle kullanılabilen galeriler
- Her biri Türkçe ve İngilizce sunulan 5 örnek blog yazısı
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
| `data/tr.json` | Türkçe arayüz metinleri |
| `data/galleries.json` | Galeri kaynak bilgileri |
| `dist/editorial.css`, `dist/portfolio.css`, `dist/brand.css` | Ek arayüz stilleri |
| `dist/site.js`, `dist/gallery.js`, `motion.js` | Dil davranışları, galeri ve animasyonlar |

Form, brief'i tarayıcıda hazırlar ve dosya olarak indirir; e-posta veya CRM bağlantısı henüz yoktur. Film sayfasına gerçek video içerikleri henüz eklenmemiştir. Google Maps ve Google Fonts internet bağlantısı gerektirir.

Fotoğraflar ve marka logoları mevcut Charlot Crea sitesinden bu demo için alınmıştır. Ayrıntılı Türkçe notlar: [README-TR.txt](README-TR.txt).
