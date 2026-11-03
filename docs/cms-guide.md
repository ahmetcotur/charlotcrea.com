# İçerik ve iş yönetimi

Panel: http://localhost:8081/admin. Yerel giriş bilgileri Git dışında
`storage/app/private/local-admin.txt` dosyasındadır.

## Sayfalar ve ana sayfa seçimi

**Sayfalar** bölümünde ana sayfalar, kurumsal sayfalar ve içerik listeleri bulunur.
TR/EN başlık, giriş, SEO başlığı, açıklama ve sayfaya uygun metin alanları ayrı
düzenlenir. Fotoğraf, film, destinasyon ve referans kayıtları kendi modüllerindedir.
Yeni genel sayfalar taslak oluşturulur. Adres boşsa başlıktan hazırlanır; başlık
değişikliği mevcut adresi kendiliğinden değiştirmez.

**Site ayarları → Aktif ana sayfa** alanından Ana sayfa 1 (klasik kapak), Ana sayfa 2
(fotoğraf şeridi) veya Ana sayfa 3 (video) seçilir. Kaydedince `/tr/` ve `/en/`
adresleri birlikte güncellenir ve önbellek yenilenir. Seçilen varyant yayından
kaldırılmışsa o dilde özgün ana sayfa gösterilir. Aktif varyantın kendi adresi
ana adresi canonical olarak kullanır; sitemap aynı içeriği iki kez listelemez.

## Ana sayfa bölümleri

**Sayfalar → İlgili ana sayfa → Düzenle** ekranında her varyantın kendi bölüm
listesi vardır. Bölümü açıp görünürlüğünü, yukarı/aşağı oklarıyla sırasını ve
Türkçe/İngilizce metinlerini düzenleyin. Varyantların özgün bölüm yapıları korunur;
birindeki ayar diğerini değiştirmez. Kapak, fotoğraf şeridi ve video medyası
sayfanın üstündeki alanlardan yönetilir.

Seçili projeler, film seçkisi, destinasyonlar ve referanslar veritabanından okunur.
Otomatik mod yayındaki kayıtları panel sırasıyla alır. Elle seçim modunda kayıtları
arama ve küçük görsellerle seçebilir, sıralarını ve gösterilecek kayıt sayısını
belirleyebilirsiniz. Taslaklar, silinenler ve o dilde yayınlanmamış kayıtlar
ziyaretçiye gösterilmez. Elle seçim boşsa eski örnek kartlar geri gelmez.
Film seçkisinin büyük videosu da kayıtlı filmlerden seçilir.

## Önizleme ve kaydetme

Kayıtlı sayfa, portföy, film, destinasyon ve blog formlarında kaydetmeden TR/EN
metin önizlemesi bulunur. Ana sayfalarda bölüm görünürlüğü, sırası ve seçki ayarları
da önizlenir. Önizleme kaydetmez, ortak önbelleğe girmez ve indekslenmez.

Yeni dosyalar ile formdaki destinasyon/marka değişiklikleri bu önizlemeye henüz
yansımaz; görseller ve ilişkiler son kaydedilen halleriyle gösterilir. Yeni
dosyaların önizlemeleri formun içindedir. Yeni kaydı önizlemek için önce oluşturun.
Kaydedilmemiş değişiklik varken sayfadan ayrılmak tarayıcı uyarısı açar.

## Görseller ve galeriler

PNG, JPG ve GIF dosyalarını seçerek veya sürükleyip bırakarak ekleyin. Görsel
başına sınır 8 MB'dır. Galerilere birden fazla seçimle dosya eklenebilir. Yeni
seçimler küçük önizlemelerle görünür; Kaldır düğmesi henüz yüklenmemiş seçimi
kaldırır. Yükleme form kaydedilince gerçekleşir.

Kayıtlı galeri görsellerini oklarla sıralayın; Sil kutuları ve sıra değişiklikleri
kaydettiğinizde uygulanır. Kapak/logo kaldırma seçeneği de kayıt sırasında işlenir.

## Portföy, marka ve destinasyon

Portföy formundaki **Marka / müşteri logosu** Referanslar modülünden seçim yapar.
Proje detayında markanın güncel logosu ve adı, yanında destinasyonlardan gelen
ülke bağlantısı gösterilir. Referans taslaksa veya ilgili dilde aktif değilse logo
gizlenir. 25hours Dubai, mevcut 25hours Hotels referansına bağlanmıştır. Bu alan
marka bilgisidir; hukuki mülkiyet kaydı değildir.

Portföy ve film formlarında destinasyonları ülke gruplarından seçebilir, ülke veya
şehir adıyla arayabilirsiniz. Seçimler etiket olarak görünür; etikete tıklayarak
kaldırabilirsiniz. Bir çekim noktası seçilince bağlı üst ülkesi sunucuda eklenir.
Yeni destinasyon bağlantısı ayrı sekme açar. Yeni kaydı kaydettikten sonra içerik
formunu kaydedip yeniden açarak güncel destinasyon listesinden seçebilirsiniz.

Destinasyonlar ülke → bölge → şehir → çekim noktası yapısındadır. Formda üst kayıt
seçilir; kendi altına taşıma ve alt kaydı olan destinasyonu silme engellenir.
Üst kayıt yayından kalkarsa alt sayfaları da ziyaretçiye ve sitemap'e kapatılır.
Adresler hiyerarşi değişince kendiliğinden değişmez.

Portföy, film, blog ve destinasyon oluştururken adres boş bırakılabilir. Sistem
başlıktan benzersiz adres üretir; aynı ad varsa sonuna sayı ekler. Var olan kaydı
başka isimle kaydetmek mevcut adresi korur.

## Blog ve SEO içerikleri

Blog editöründe başlık, paragraf, liste, bağlantı ve alıntı biçimleri kullanılabilir.
Kelime/okuma sayacı vardır; yapıştırılan metnin dış biçimlendirmesi temizlenir.

**Sayfalar → Fotoğraf portföyü / Film portföyü** ekranında her dil için SEO başlığı,
açıklaması, giriş ve zengin metin SEO içeriği düzenlenir. İçerik portföy listesinin
altında, iletişim çağrısının üstünde görünür. Hizmetler sayfasındaki fotoğraf/film
başlıkları ve devam bağlantıları aynı dilin güncel portföy adreslerine gider.

`php artisan charlot:prepare-portfolio-content` ilk TR/EN SEO metinlerini başlatır;
paneldeki metinleri tekrar çalıştırmada değiştirmez. `charlot:import` kaynak
katalog aktarımıdır; panel düzenlemelerini korumak için normal kullanımda
`--overwrite` verilmez.

## Site ayarları ve referanslar

Marka, e-posta, telefon, WhatsApp, adres/harita ve sosyal bağlantılar Site ayarları
bölümündedir. İletişim sayfası, footer ve site şemasında ortak kullanılır. Mail
sunucusu bilgileri burada tutulmaz; ortam değişkenleriyle yapılandırılır.

Referanslar bölümünden firma adı, logo, web adresi, açıklama ve yayın durumu
ayarlanır. Referans adı TR/EN ayrı tutulur. Projelerdeki logo bu kayıttan alınır.

## Müşteri, teklif, iş ve teslimat

Müşteriler iletişim bilgilerini ve ekip içi notları tutar. Teklifler müşteriye
bağlı kalemler, kapsam, para birimi, geçerlilik tarihi ve durum taşır. Durum
geçmişi yönetici/müşteri işlemini kaydeder. Müşteriler girişli panelden veya paylaşılan süreli bağlantıdan tekliflerini görüntüleyip kabul/ret yanıtı verir.

Kabul; müşteri, başlık ve kapsam bilgileriyle otomatik Planlandı işi oluşturur.
Panelden Kabul edildi yapmak da aynı akışı çalıştırır. Tekrar kabul mevcut işi
veya arşivlenmiş kaydı kopyalamaz. Teklifte bağlı işin bağlantısı bulunur.
Eski teklifler için `charlot:reconcile-accepted-quotes` eksikleri listeler;
`--apply` eksik işleri oluşturur.

İşler bölümünden durum, başlangıç/teslim tarihleri ve ekip içi notlar düzenlenir.
Teslim notu ve bağlantısı müşteriye imzalı sayfadan sunulur; müşteri onaylayabilir
veya revizyon talep edebilir. Teslim edilmemiş, iptal edilmiş ya da açık revizyonu
olan iş onaylanamaz. Aynı işe ikinci açık revizyon talebi oluşturulamaz.

İş düzenleme ekranında **Teslimat & revizyonlar → Google Drive teslimat bağlantıları**
alanından başlık ve HTTPS Google Drive klasör/dosya bağlantısı eklenir. Sisteme
fotoğraf, video veya teslimat dosyası yüklenmez. Drive paylaşım izinlerini
müşterinin erişebileceği şekilde ayarlayın. `drive.google.com` ve `docs.google.com`
bağlantıları desteklenir.

Yeni veya düzenlenen bağlantılar **Teslim et** işlemine kadar müşteriye görünmez.
Tamamlanmış işe yeniden teslim yapmak işi Devam ediyor durumuna alır. Bağlantı
kaldırmak Drive dosyalarını silmez. Hesaplı müşteri bağlantılarını panelinden,
hesabı olmayan müşteri süreli imzalı teslimat sayfasından açar. Google Drive erişimi
Drive paylaşım izinleriyle yönetilir. Önceden yüklenen özel dosyalar arşivde korunur;
yeni dosya yükleme uç noktası kaldırılmıştır.

CRM panosunda görünüm (tümü / yaklaşan çekimler / onaylanan işler), iş tarih alanı,
yıl ve başlangıç-bitiş aralığı seçilebilir. Aralık uçları dahildir; yıl ve aralık
birlikte uygulanır. Yaklaşan çekimler bugünden sonraki aktif çekimlerdir.
Onaylanan işler kabul edilmiş teklife bağlı, iptal edilmemiş işlerdir. Tarih
filtreleri tarihsiz işleri dışarıda bırakır. Teklifler gönderim/kayıt tarihine,
onaylanan görünümünde kabul tarihine göre süzülür. Müşteri ve revizyon listeleri
genel kalır.

İletişim brief'leri İletişim talepleri bölümüne düşer; e-postayla müşteri
ilişkilendirilir veya aday müşteri açılır. `[ÖRNEK]` etiketli kayıtlar demo içeriğidir.

## Mail ve yayın

Teklif/teslimat bildirimleri kuyruğa alınır. Varsayılan log mailer gerçek e-posta
göndermez; SMTP ve worker gerekir. PostgreSQL, web/worker konteynerleri, kalıcı
medya depolama ve yedekleme için [Laravel yayın rehberine](../deployment/laravel/README.md)
bakın. [Güncel proje durumu](project-status.md) kalan işleri ve doğrulamaları içerir.

## Müşteri paneli ve erişim

Giriş: `/panel/login`. TR/EN arayüz sağ üstteki dil seçicisinden değiştirilir.
Müşteri, kendi gönderilmiş tekliflerini ve işlerini görür; yönetici notları ile
yayımlanmamış teklif taslakları gösterilmez. Teklif kabul/ret, iş durumu, teslim
Drive bağlantısı açma, teslimat onayı ve revizyon talebi buradan kullanılabilir.

**Müşteriler → Müşteriyi düzenle → Müşteri paneli erişimi** bölümünde e-posta
kaydedildikten sonra davet bağlantısı oluşturulur. Bu işlem kendiliğinden e-posta
göndermez. Bağlantıyı ilgili müşteriye iletin: 60 dakika geçerli ve tek kullanımlıktır.
Müşteri kendi parolasını belirler; yönetici parolayı görmez. Yeni davet önceki
parola bağlantısını geçersizleştirir.

**Erişimi kapat** hesap girişini ve teslimat bağlantılarına panel erişimini kapatır. Müşteri
e-postası değişirse hesap yeniden davet edilene kadar kapatılır. Parola değişikliği
eski oturumların erişimini keser. Parola unuttum ekranı, SMTP/worker hazırsa
yenileme bağlantısını e-posta kuyruğuna alır. Müşteri ve yönetici guard'ları ayrıdır.

## İletişim ve teklif formları

`/tr/iletisim/` ve `/en/contact/` kısa iletişim formudur: ad, e-posta, isteğe bağlı
telefon, mesaj ve yanıt için izin. Proje ayrıntısı zorunlu değildir.

`/tr/teklif-al/` ve `/en/request-a-quote/` proje teklif formudur: firma/proje adı,
proje türü, ülke/şehir, hizmet, teslim beklentisi, kullanım alanı, tercih edilen
zaman, isteğe bağlı bütçe ve iletişim bilgileri. Hizmet seçimine göre brief
yönlendirmesi değişir. Formlar **İletişim talepleri** bölümünde İletişim / Teklif
talebi türleriyle filtrelenir; bütçe, kullanım alanı ve teslimler detayda görünür.
Footer'ın eski önizleme yazısı kaldırılmıştır. Sosyal medya logo altındadır; teklif
ve müşteri paneli ayrı eylem kartlarıdır.

## Konumların SEO içerikleri

Mevcut 17 konum için 34 dil sürümünde H1, SEO başlığı, açıklama, giriş, içerik
bölümleri ve SSS hazırlanmıştır. Konumlar → Düzenle ekranında bunlar ve
**Hedef anahtar kelimeler** alanı TR/EN ayrı düzenlenebilir. Hedefler doğal metin
planlaması içindir; sayfaya gizli anahtar kelime listesi eklenmez.

`data/destination-seo-content.json` içerik kaynağıdır.
`php artisan charlot:prepare-destination-seo` ilk uygulamayı yapar ve önceki
metinleri özel `seo-backups/` klasörüne yedekler. Tekrar çalıştırmak panel
düzenlemelerini korur; `--refresh` açıkça yeniden uygulamak içindir. URL ve
yayın durumu değiştirilmez. Yeni konumlar için yeni içerik planı yazılmalıdır.

Ana sayfalardaki destinasyon bölümü SEO metnini göstermez. Otomatik mod ülke
listesidir; kısa alt konum adları gösterilir. Elle seçimde şehir de seçilebilir.

## Yerel demo iş akışları

Dashboard üzerindeki **Demo iş akışları** bağlantısı (`/admin/demo-workflows`)
beş yeni senaryoyu ve her birinin müşteri/teklif/iş bağlantılarını gösterir:
teklif onayı bekleyen otel, planlanan mimari çekim, teslimat onayı bekleyen resort,
açık revizyonlu film ve tamamlanmış kurumsal iş. Her müşteri için iletişim ve
ayrıntılı teklif talebi örnekleri vardır. Kabul edilen teklifler otomatik iş
oluşturma akışından geçirilmiştir; elde bağımsız iş üretilmez.

Parolalar yalnızca giriş yapılmış yerel yönetici ekranında açılır; özel manifest
`storage/app/private/demo-workflows.json` dosyasındadır. Bu sayfa ve oluşturma
komutu yalnızca local/testing ortamında çalışır. Hesap e-postaları `.example.test`
alanındadır; oluşturma sırasında e-posta gönderilmez. Drive adresleri açıkça
`DEMO` yer tutucusudur, gerçek teslimat dosyası içermez. Portaldaki kabul/onay
butonları bu demo kayıtlarını da normal iş kurallarıyla değiştirir.

```sh
php artisan charlot:demo-workflows
```

Komutu yeniden çalıştırmak mevcut senaryoları, düzenlemeleri veya parolaları
sıfırlamaz. Eski `[ÖRNEK]` kayıtlar korunur.

## Yerel Blade sayfaları

Üç ana sayfa, Hakkımızda ve Hizmetler diğer içerik sayfalarıyla aynı Blade
altyapısındadır. `legacy_html` public render sırasında kullanılmaz. Geçiş komutu
`charlot:prepare-native-pages`, mevcut ana sayfa bölüm düzenlerini ve metinlerini
özel depolamaya yedekleyip `source_data.native_home_sections` içine alır; yeniden
çalıştırıldığında mevcut düzenlemeleri değiştirmez. Taze `charlot:import` bunu
otomatik çağırır. CMS çevirileri, bölüm görünürlüğü/sırası, seçili kayıtlar ve
kullanıcının metin değişiklikleri korunur. Başlıklar ve zengin metin gövdeleri
sayfa düzenleme ekranından güncellenmeye devam eder.
