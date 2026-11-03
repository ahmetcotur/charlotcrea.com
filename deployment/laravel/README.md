# Laravel yayını

Bu yapı mevcut statik Dockerfile ve demo yayınının yanında kullanılabilir. PHP-FPM,
Nginx, PostgreSQL 17 ve veritabanı kuyruğunu tüketen ayrı worker içerir. Depodaki
SQLite veritabanını veya yerel giriş bilgilerini imaja kopyalamaz.

Depo kökünde:

```sh
cp deployment/laravel/env.example deployment/laravel/.env
php artisan key:generate --show
```

Üretilen anahtarı `APP_KEY` alanına, benzersiz PostgreSQL parolasını
`POSTGRES_PASSWORD` alanına koyun. `APP_URL` yayın alan adı olmalıdır. `.env`
dosyasını Git'e eklemeyin. Ortam dosyasını değiştirmek tek başına konteynerleri
ve çalışan kuyruk sürecini yenilemez; `up -d --force-recreate` kullanın.

```sh
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml build
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml up -d database app web
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan migrate --force
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan charlot:import
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan charlot:import-media
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan charlot:import-references
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan charlot:seed-settings
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan charlot:prepare-portfolio-content
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan charlot:link-project-brands
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan charlot:prepare-enquiry-pages
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan charlot:prepare-destination-seo
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml exec app php artisan twill:superadmin
docker compose --env-file deployment/laravel/.env -f deployment/laravel/compose.yaml up -d worker
```

Komut adı `charlot:import-references` ve mevcut komutları `php artisan list`
ile kontrol edin. Kaynak aktarımı mevcut panel düzenlemelerini korur;
`--overwrite` üretim içeriğini değiştireceği için normal yayında kullanılmaz.
Mevcut yerel veriler yayına taşınacaksa ayrıca veri aktarımı yapılmalıdır; bu
başlangıç komutları kaynak katalogdan yeni bir veritabanı oluşturur.

Web yalnızca `127.0.0.1:8092` üzerinde dinler. HTTPS sonlandıran ters proxy'yi bu
porta yönlendirin ve gelen `X-Forwarded-Proto` başlığını proxy'de güvenilir
biçimde `https` olarak yeniden yazın. `APP_URL=https://...` ve
`SESSION_SECURE_COOKIE=true` üretim içindir. Yerel HTTP denemesinde ikisini
HTTP'ye uygun ayarlayın. `/up` sağlık kontrolüdür.

SMTP alanları gerçek sağlayıcıya göre ortam dosyasından alınır. Varsayılan
`MAIL_MAILER=log` e-posta göndermez. SMTP hazır olduğunda `MAIL_MAILER=smtp`
yapıp app ve worker'ı yeniden oluşturun. `MAIL_SCHEME=smtp` STARTTLS;
sağlayıcı doğrudan TLS istiyorsa `smtps` ve uygun portu kullanın. Gönderen alan
adının SPF/DKIM kayıtlarını sağlayıcının değerleriyle doğrulayın. Başarısız
kuyruk işleri `php artisan queue:failed` ve `queue:retry <id>` ile yönetilir.

Yayından önce:

```sh
python3 tests/check_seo_live.py --base-url https://cms.example.com
```

`SITE_INDEXABLE=false` önizleme içindir. Gerçek alan adı ve SEO kontrolleri
hazır olduğunda `true` yapıp app ve worker'ı yeniden oluşturun, uygulama
önbelleğini `php artisan cache:clear` ile temizleyin. Güncelleme öncesi yedek
alın, yeni imajları oluşturun, migration çalıştırın ve worker'ı yenileyin.

## Yedek ve geri yükleme

Tutarlı bir veritabanı/medya çifti için yedek öncesi bakım moduna geçin ve
worker'ı durdurun. Sonrasında bakım modundan çıkıp worker'ı başlatın.

```sh
ENV_FILE="$PWD/deployment/laravel/.env" BACKUP_ROOT=/private/charlot-backups deployment/laravel/backup.sh
```

Yedek; PostgreSQL custom dump, `storage/app` ve şifreli bağlantıların korunması
için ortam dosyasını içerir. Yedeği erişimi kısıtlı ve sunucu dışında bir yerde
saklayın. Zamanlama ve saklama süresi işletim ortamında yapılandırılmalıdır.

Geri yüklemeyi önce ayrı bir ortamda deneyin. app/worker duruyorken boş bir
veritabanına `pg_restore -U <user> -d <db> --no-owner --no-acl` ile dump'ı yükleyin;
`storage-app.tar.gz` arşivini `/var/www/html` altında açıp dosya sahibini
`www-data` yapın. Aynı `APP_KEY` ve aynı alan adı mevcut imzalı bağlantıları
korur. `down -v` kalıcı veritabanını ve medyayı siler; normal güncellemede
kullanmayın.

## Müşteri paneli ve dosyalar

Migration'lar müşteri hesaplarını, ayrı parola token tablosunu ve iş dosyalarını
oluşturur. Admin → Müşteriler ekranından davet bağlantısı oluşturulur. Parola
yenileme e-postaları aynı worker ile gönderilir; gerçek SMTP tanımlanmadan
kullanıcıya mail gönderildiği varsayılmamalıdır.

Özel teslimatlar `storage/app/private/deliveries` altında, kalıcı storage
volume'ündedir ve yedek betiğine dahildir. Nginx toplam 512 MB, PHP dosya başına
256 MB kabul edecek şekilde ayarlanmıştır. `DELIVERY_FILE_MAX_KB` uygulama sınırını
değiştirir; PHP ve ters proxy sınırlarını da uyumlu tutun.

## Coolify ile Git yayını

Mevcut statik uygulama kökteki Dockerfile'ı kullanmaya devam edebilir. Laravel için
ayrı Git uygulaması oluşturun: build pack `dockercompose`, base directory `/`,
Compose location `/deployment/laravel/compose.coolify.yaml`. Public hizmet `web`,
iç port `8080` olmalıdır. PHP, PostgreSQL, worker ve scheduler dışarıya açılmaz.
`APP_KEY`, `POSTGRES_PASSWORD`, `APP_URL`, `INITIAL_ADMIN_EMAIL` ve güçlü
`INITIAL_ADMIN_PASSWORD` Coolify ortam değişkenlerinden verilir. İlk yönetici
oluşturulduktan sonra başlangıç e-posta/parola değişkenlerini kaldırabilirsiniz;
mevcut hesabın parolası sonraki yayınlarda sıfırlanmaz.

Başlangıçta migration ve eksik kaynak içerik/medya aktarımı otomatik yapılır;
mevcut CMS değişiklikleri korunur. Yereldeki SQLite, hesaplar, demo kayıtları ve
özel dosyalar imajla taşınmaz; bu yeni bir veritabanı kurulumudur. Mevcut yerel
verinin birebir aktarılması ayrı yedek/geri yükleme işlemidir. İlk kurulum süresince
web ve worker servisleri PHP uygulamasının hazır olmasını bekler. PostgreSQL ve
storage ayrı kalıcı volume'lerdedir.

Coolify Traefik ağındaki gerçek proxy IP/CIDR'lerini `TRUSTED_PROXIES` alanına
verin. Önizlemede `SITE_INDEXABLE=false` tutun. Dashboard analizleri için scheduler
bu Compose tanımına dahildir; ülke veritabanı ilk açılışta indirilir.
