<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('charlot:import')->assertSuccessful();
    }

    public function test_sitemap_returns_valid_xml_with_lastmod_and_alternates(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=3600', $response->headers->get('Cache-Control'));

        $xml = $response->getContent();
        $this->assertStringContainsString('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', $xml);
        $this->assertStringContainsString('<loc>' . config('app.url') . '/en/</loc>', $xml);
        $this->assertStringContainsString('<loc>' . config('app.url') . '/tr/</loc>', $xml);
        $this->assertStringContainsString('<lastmod>', $xml);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="en"', $xml);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="tr"', $xml);
        $this->assertStringContainsString('<xhtml:link rel="alternate" hreflang="x-default"', $xml);

        preg_match_all('/<loc>(.*?)<\/loc>/', $xml, $locMatches);
        preg_match_all('/<lastmod>(.*?)<\/lastmod>/', $xml, $lastmodMatches);

        $this->assertNotEmpty($locMatches[1]);
        $this->assertSame(count($locMatches[1]), count($lastmodMatches[1]));

        foreach ($lastmodMatches[1] as $lastmod) {
            $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $lastmod);
        }
    }

    public function test_robots_txt_when_indexable(): void
    {
        config(['charlot.indexable' => true]);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString("User-agent: *\nAllow: /", $content);
        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /panel/', $content);
        $this->assertStringContainsString('Disallow: /legacy-cms/', $content);
        $this->assertStringContainsString('Disallow: /teklif/', $content);
        $this->assertStringContainsString('Disallow: /teslimat/', $content);
        $this->assertStringContainsString('Sitemap: ' . rtrim(config('app.url'), '/') . '/sitemap.xml', $content);
    }

    public function test_robots_txt_when_not_indexable(): void
    {
        config(['charlot.indexable' => false]);

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString("User-agent: *\nDisallow: /", $content);
        $this->assertStringContainsString('Sitemap: ' . rtrim(config('app.url'), '/') . '/sitemap.xml', $content);
    }

    public function test_legacy_redirect_preserves_target_trailing_slash(): void
    {
        $response = $this->get('/en/portfolio/photography');

        $response->assertStatus(301);
        $response->assertRedirect('/en/photography/');
    }
}
