# Referans logoları ve proje markaları

Mevcut 14 referans logosu özgün kaynaklardan korunmuştur. Balmy Beach Resort Kemer
logosu [resmî sitenin](https://www.balmybeachresort.com/) başlığındaki SVG'den alınmıştır.
Kaynak vektör `data/brand-sources/balmy.svg`, siteye uygun raster dışa aktarım
`dist/assets/references/balmy.png` dosyasıdır. Görünümde logolar CSS ile tek renk ve
site zeminiyle uyumlu gösterilir; özgün yüklemeler değiştirilmez. Koyu zeminli özgün
Th8 logosunda ters ton uygulanır; yeni bir logo yüklendiğinde bu özel dönüşüm uygulanmaz.

`data/project-brands.json`, 18 markalı projenin doğrulanmış referans eşleşmeleridir.
`php artisan charlot:link-project-brands` mevcut panel seçimlerini koruyarak eksikleri
tamamlar. Architecture & Interiors genel seçkidir; tek bir müşteri/marka atanmamıştır.
