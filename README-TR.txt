CHARLOT CREA - DEMO KAYNAK DOSYALARI
31 Ekim 2026

PAKET ICERIGI
- dist/: Yayinlanmaya hazir 20 HTML sayfasi, CSS, JavaScript ve gorseller.
- dist/assets/vendor/: Yerel GSAP 3.13.0 ve ScrollTrigger dosyalari.
- build.py: Sayfalari yeniden ureten Python kaynak dosyasi.
- motion.js: GSAP animasyonlarinin duzenlenebilir kaynagi.

MAC / WINDOWS UZERINDE CALISTIRMA
1. ZIP dosyasini cikartin.
2. Terminali Charlot-Crea-Demo klasorunde acin.
3. Asagidaki komutlari calistirin:

   python3 build.py
   python3 serve.py

Windows'ta gerekirse python3 yerine py kullanin:
   py build.py
   py serve.py

4. Tarayicida http://localhost:8080 adresini acin.
5. Sunucuyu kapatmak icin Ctrl+C kullanin.

Python kurulu degilse VS Code Live Server ile dist klasorunu acabilirsiniz.
HTML dosyasina cift tiklamak yerine yerel sunucu kullanin;
site baglantilari / ile baslayan kok adreslerini kullanir.

DUZENLEME
- Gorunumu dist/style.css dosyasindan degistirebilirsiniz.
- Form ve filtre davranislari dist/app.js icindedir.
- Animasyonlari motion.js dosyasinda duzenleyip python3 build.py calistirin.
- build.py sayfalari, style.css ve app.js dosyalarini yeniden uretir.
  Dogrudan dist icinde yaptiginiz degisikliklerin korunmasi icin
  ayni degisiklikleri build.py kaynagina da uygulayin.

BASKA SUNUCUDA YAYINLAMA
- dist klasorunun ICERIGINI alan adinin web kokune yukleyin.
- Ornek: public_html/index.html, public_html/en/, public_html/assets/.
- Ek bir kurulum veya npm build gerekmez.
- Alt klasore yayinlamak icin / ile baslayan varlik ve sayfa yollarini uyarlayin.

DEMO SINIRLARI
- Teklif formu yerelde brief dosyasi hazirlar; e-posta gondermez.
- CRM, veri tabani, yonetim paneli ve e-posta servisi bu pakette yoktur.
- Formdaki bilgiler bir sunucuya kaydedilmez.
- Demo indekslemeye kapalidir: robots.txt ve HTML noindex etiketleri mevcut.
  Gercek yayina geciste bunlari SEO planiyla birlikte duzenleyin.
- Google Fonts internet baglantisi kullanir; diger ana varliklar yereldir.
- Proje fotograflari ve referans logolari Charlot Crea'nin mevcut sitesinden
  alinmistir; kullanim amaci bu demo ile sinirlidir.

GUNCELLEME: Orijinal About icerigi, fotografi ve yeni sayfa tasarimi dahildir.

PHOTOGRAPHY / FILM GUNCELLEMESI
- Photography ve Film ayri ana sayfalar olarak eklendi.
- 8 fotograf projesinde toplam 71 fotograf, kirpilmadan gosterilir.
- Galeride tiklayarak buyutme, ok tuslariyla gezinme ve Escape ile kapatma vardir.
- Film sayfasinda orijinal sitede yayinlanmis video bulunmadigi icin video eklenmedi.
- enhance.py, build.py sonunda calisir; data/galleries.json galeri kaynaklarini tutar.
- dist/portfolio.css ve dist/gallery.js ilgili tasarim ve galeri davranisini icerir.
- Favicon ve ana sayfa logo reveal korunur.

SEO / YAYIN AYARLARI
- Yerel/demo derlemesi: python3 build.py
  Canonical, OG ve sitemap http://localhost:8080 adresini kullanir.
  Demo noindex ve robots Disallow: / ile korunur.
- Yerel sunucu: python3 serve.py
  Yeni sunucu eski URL'lerde gercek 301 ve bulunamayan sayfalarda 404 dondurur.
  Basit python http.server yonlendirme kurallarini uygulamaz.
- Canli yayin derlemesi: python3 build.py --production
  Canonical, OG, JSON-LD ve sitemap https://www.charlotcrea.com adresini kullanir.
  Indeksleme acilir; robots.txt sitemap adresini bildirir.
- Farkli alan adi: python3 build.py --production --site-url https://ornek.com
- Marka ve adres bilgileri data/site.json, sayfa SEO uretimi seo.py icindedir.
- Yayin: dist icerigini yukleyin. Apache icin .htaccess, Netlify icin _redirects
  hazirdir. Diger sunucularda redirects.json kurallarini gercek 301 olarak uygulayin;
  /404.html gercek 404 durum koduyla sunulmalidir.
- En son derleme modu dist/build-info.json dosyasinda gorulur.
- Kontrol: python3 tests/check_seo.py
- Turkce ve Ingilizce sayfalar sayfa bazinda eslesir; en, tr ve x-default
  hreflang ile sitemap dil alternatifleri uretilir. Film videosu olmadigi icin
  VideoObject uretilmez.
- Sitemap canli sunucuda yayinlandiktan sonra Search Console'a gonderilebilir.
  Yerel testler arama motoru indekslenmesini veya siralama sonucunu dogrulamaz.

IKI DIL / BLOG / ILETISIM GUNCELLEMESI
- Ana sayfalar: /tr/ ve /en/. Dil secici her sayfanin dogrudan karsiligina gider.
- Toplam 54 sayfa: 21 ana sayfa yapisinin iki dili, 2 blog listesi, 10 yazi sayfasi.
- Blog ana menude yoktur; footer baglantisindan acilir.
- Bes ornek yazinin Turkce ve Ingilizce metinleri data/blog.json icindedir.
- Arayuz cevirileri data/tr.json; dil/URL eslesmeleri data/language-routes.json.
- localize.py dil secimini, blog sayfalarini ve dil bazli SEO ciktisini uretir.
- polish.py ana sayfa ticker ve kayan referans seridini duzenler.
- Logolarin boyutlari korunur; SVG filtresi beyaz zemini saydamlastirir.
- dist/editorial.css ve dist/site.js yeni arayuz ve dil davranislarini icerir.
- Iletisim: info@charlotcrea.com / +90 541 396 22 60.
- Adres ve harita data/site.json icindeki dogrulanmis Antalya adresinden uretilir.
- Kaynak sitedeki Music telefonu kullanilmaz; Film icin ayrica telefon uydurulmaz.
- Google Maps haritasi internet baglantisi gerektirir; yol tarifi baglantisi da vardir.

DESTINASYON ICERIKLERI
- Turkiye, BAE, Misir, Suudi Arabistan ve Dubai icin ozgun Turkce/Ingilizce metinler.
- data/destinations.json: giris, bolge baglami, hizmet basliklari, sorular ve kaynaklar.
- destinations.py: gorunur icerik, arka plan yer adi, metadata ve schema uretimi.
- docs/destination-seo.md: hedef ifade haritasi; arama hacmi olcumu yapilmamistir.
- Buyuk arka plan yazisi dekoratiftir; icerik okunabilir paragraflarda yer alir.
