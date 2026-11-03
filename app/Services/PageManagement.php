<?php

namespace App\Services;

use App\Models\Page;

class PageManagement
{
    public const PAGES = [
        '/en/' => ['label' => 'Ana sayfa', 'group' => 'Ana sayfalar', 'type' => 'Görselli ana sayfa', 'help' => 'Tüm bölüm metinlerini, başlığı, giriş metnini ve ana görseli düzenleyin.', 'media' => 'cover', 'copy' => true],
        '/en/home-2/' => ['label' => 'Ana sayfa · Fotoğraf şeridi', 'group' => 'Ana sayfalar', 'type' => 'Fotoğraf şeridi', 'help' => 'Tüm bölüm metinlerini ve fotoğraf şeridini düzenleyin.', 'media' => 'gallery', 'copy' => true],
        '/en/home-3/' => ['label' => 'Ana sayfa · Video', 'group' => 'Ana sayfalar', 'type' => 'Video ana sayfa', 'help' => 'Tüm bölüm metinlerini, arka plan videosunu ve poster görselini düzenleyin.', 'media' => 'video', 'copy' => true],
        '/en/about/' => ['label' => 'Hakkımızda', 'group' => 'Kurumsal sayfalar', 'type' => 'Hakkımızda', 'help' => 'Tüm bölüm metinlerini ve sayfa fotoğrafını düzenleyin.', 'media' => 'cover', 'copy' => true],
        '/en/services/' => ['label' => 'Hizmetler', 'group' => 'Kurumsal sayfalar', 'type' => 'Hizmetler', 'help' => 'Fotoğraf ve film hizmetleri dahil tüm bölüm metinlerini düzenleyin.', 'copy' => true],
        '/en/request-a-quote/' => ['label' => 'Teklif al', 'group' => 'Kurumsal sayfalar', 'type' => 'Proje teklif formu', 'help' => 'Teklif formunun TR/EN başlığını, girişini ve SEO bilgilerini düzenleyin. Gelen proje ayrıntıları İletişim taleplerinde Teklif talebi türüyle görünür.'],
        '/en/contact/' => ['label' => 'İletişim', 'group' => 'Kurumsal sayfalar', 'type' => 'İletişim', 'help' => 'Başlık ve giriş bu ekrandadır. Telefon, e-posta, adres ve harita Site ayarlarından yönetilir.', 'settings' => true],
        '/en/photography/' => ['label' => 'Fotoğraf portföyü', 'group' => 'İçerik sayfaları', 'type' => 'Fotoğraf listesi', 'help' => 'Buradan sayfanın başlığını ve girişini düzenleyin. SEO içeriği ve sayfa metinleri bu ekrandadır. Fotoğraflar ve projeler Portföy bölümünden yönetilir.', 'module' => 'projects', 'action' => 'Fotoğraf projelerini yönet'],
        '/en/film/' => ['label' => 'Film portföyü', 'group' => 'İçerik sayfaları', 'type' => 'Film listesi', 'help' => 'Buradan sayfanın başlığını ve girişini düzenleyin. SEO içeriği ve sayfa metinleri buradadır. Film seçkisini Filmler bölümünden yönetin.', 'module' => 'films', 'action' => 'Filmleri yönet'],
        '/en/destinations/' => ['label' => 'Destinasyonlar', 'group' => 'İçerik sayfaları', 'type' => 'Destinasyon listesi', 'help' => 'Ülke ve çekim noktalarını Destinasyonlar bölümünden, bu sayfanın başlığını ve girişini buradan yönetin.', 'module' => 'destinations', 'action' => 'Ülke ve noktaları yönet'],
        '/en/blog/' => ['label' => 'Blog', 'group' => 'İçerik sayfaları', 'type' => 'Blog listesi', 'help' => 'Sayfa başlığı ve giriş metni buradadır. Yeni yazı eklemek veya mevcut yazıları düzenlemek için Blog bölümünü kullanın.', 'module' => 'posts', 'action' => 'Blog yazılarını yönet'],
        '/en/references/' => ['label' => 'Referanslar', 'group' => 'İçerik sayfaları', 'type' => 'Referans listesi', 'help' => 'Sayfa başlığını ve girişini buradan, firma adlarını ve logoları Referanslar bölümünden düzenleyin.', 'module' => 'references', 'action' => 'Firma ve logoları yönet'],
    ];

    public static function describe(Page $page): array
    {
        if (isset(self::PAGES[$page->import_key])) {
            return ['label' => $page->translate('tr')?->title ?: self::PAGES[$page->import_key]['label']] + self::PAGES[$page->import_key];
        }

        return ['label' => $page->translate('tr')?->title ?: ($page->title ?: 'Yeni sayfa'), 'group' => 'Diğer sayfalar', 'type' => 'İçerik sayfası', 'help' => 'Başlığınızı, giriş metninizi ve sayfa içeriğini ekleyin. Sayfa adresleri kaydederken otomatik hazırlanır.', 'media' => 'cover', 'editorial' => true];
    }
}
