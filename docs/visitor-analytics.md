# Dashboard ve ziyaretçi analizleri

`/admin` CRM özeti, yaklaşan çekimler, içerik/SEO kontrolü ve ilk taraf ziyaretçi
analizlerini gösterir. Analiz bölümü tarih aralığı, ülke, cihaz ve tam IPv4/IPv6
adresiyle filtrelenir. Son ziyaret kayıtları sayfalanır; IP'ye tıklamak o IP'yi süzer.
Veri toplama kurulumdan itibaren başlar; geçmiş ziyaretler üretilmez.

Yalnızca herkese açık içerik rotasının başarılı HTML GET yanıtları kaydedilir;
HTML önbellekten gelse bile ziyaret sayılır. Yönetici/müşteri oturumları, yönetim,
müşteri paneli, özel teklif/teslimat rotaları, yönlendirmeler, hatalar, HEAD,
prefetch ve bilinen bot tarayıcı bilgileri dahil edilmez. Bu basit bot filtresi
her otomatik isteği tespit edemez.

Kaydedilenler: zaman, sorgusuz sayfa yolu, dil, şifreli IP, anahtarlı IP/ziyaretçi
özeti, ülke, cihaz sınıfı, tarayıcı, işletim sistemi ve yalnızca yönlendiren alan
adı. URL sorguları, form içerikleri, tam referrer URL'si ve ham tarayıcı bilgisi
saklanmaz. Analiz için ek çerez oluşturulmaz. Tahmini tekil ziyaretçi IP ve
tarayıcı bilgisine dayanır; ortak ağlar ve değişen IP/tarayıcılar sonucu etkiler.
IP alanı Laravel uygulama anahtarıyla şifrelidir; APP_KEY yedeklerini koruyun.

Ülke sunucuda DB-IP Country Lite MMDB ile bulunur. IP adresi harici API'ye
gönderilmez. Yerel/özel IP veya bulunamayan ülke “Bilinmiyor / yerel ağ” görünür.
IP konumu yaklaşık konumdur; VPN/proxy gerçek ziyaretçi ülkesini gizleyebilir.
Kaynak: [DB-IP Country Lite](https://db-ip.com/db/download/ip-to-country-lite),
CC BY 4.0. Panelde gerekli DB-IP kaynak bağlantısı bulunur.

Kurulum (uygulama konteynerinde):

```sh
php artisan migrate --force
php artisan charlot:analytics-countries
```

Veritabanı `storage/app/private/analytics/country.mmdb` altında kalıcı storage
hacmindedir. `charlot:analytics-countries --month=2026-11` belirli sürümü indirir.
İndirme/geçerlilik denetimi tamamlandıktan sonra atomik olarak değiştirilir;
başarısız güncelleme mevcut veritabanını korur. Composer bağımlılığı
`maxmind-db/reader` kurulu olmalıdır. Analiz hataları public sayfayı kesmez.

`.env` / Coolify değişkenleri:

```dotenv
ANALYTICS_ENABLED=true
ANALYTICS_RETENTION_DAYS=90
TRUSTED_PROXIES=
```

`TRUSTED_PROXIES` alanına yalnızca gerçek ters proxy IP'lerini veya CIDR aralıklarını
virgülle yazın. Boşken istemci tarafından yazılan X-Forwarded-For dikkate alınmaz.
Coolify/Traefik arkasında gerçek ziyaretçi IP'si için bu ağ yapılandırmasını
sunucunuzdaki gerçek proxy ağına göre ayarlayın. Tüm interneti güvenilir proxy
olarak tanımlamayın. Referans: [Laravel trusted proxies](https://laravel.com/framework/docs/requests#configuring-trusted-proxies).

Docker Compose'a `scheduler` servisi eklendi. Günlük 03:10'da saklama süresi geçen
kayıtlar silinir; ayın 3'ünde 03:30'da ülke veritabanı güncellenir. Saatler uygulama
saat dilimindedir. Docker dışında sistem cron'u her dakika `php artisan schedule:run`
çalıştırmalı veya `php artisan schedule:work` açık tutulmalıdır. Saklama temizliği
elle `php artisan charlot:analytics-prune` ile de çalışır. Yerelde scheduler
çalışmıyorsa otomatik silme gerçekleşmez.

İçerik kontrolü yayın sayısını ve aktif çevirilerde boş SEO başlık/açıklama
alanlarını ölçer; harici uptime testi değildir. E-posta göstergesi yapılandırmayı
belirtir; gerçek teslim doğrulaması değildir. CRM kartları genel durumu,
analiz tarih filtresi yalnızca ziyaretçi verilerini gösterir.
