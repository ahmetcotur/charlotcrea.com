<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\PageVisit;
use App\Services\Analytics\CountryLookup;
use App\Services\Analytics\VisitRecorder;
use App\Services\PageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VisitorAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private string $agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1 Version/17 Mobile Safari/604.1';

    private function publicPage(): void
    {
        $page = Page::create(['published' => true, 'import_key' => '/en/contact/']);
        $page->translations()->create(['locale' => 'en', 'title' => 'Contact', 'path' => '/en/contact/', 'active' => true]);
        $this->mock(PageRenderer::class, fn ($mock) => $mock->shouldReceive('render')->andReturn('<!doctype html><html><body>Contact</body></html>'));
        $this->mock(CountryLookup::class, fn ($mock) => $mock->shouldReceive('lookup')->andReturn('TR'));
    }

    private function visit(array $changes = []): PageVisit
    {
        return PageVisit::create(array_merge([
            'visited_at' => now(), 'path' => '/en/contact/', 'locale' => 'en',
            'ip_address' => '203.0.113.8', 'ip_hash' => VisitRecorder::ipHash('203.0.113.8'),
            'visitor_hash' => 'visitor-a', 'country_code' => 'TR', 'device' => 'mobile',
            'browser' => 'Safari', 'platform' => 'iOS', 'referrer_host' => 'google.com',
        ], $changes));
    }

    public function test_public_html_records_cached_visits_without_queries_and_encrypts_ip(): void
    {
        $this->publicPage();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.8'])->withHeaders(['User-Agent' => $this->agent, 'Referer' => 'https://google.com/search?secret=private', 'X-Forwarded-For' => '1.1.1.1']);
        $this->get('/en/contact/?secret=private')->assertOk();
        $this->get('/en/contact/')->assertOk();
        $this->assertDatabaseCount('page_visits', 2);
        $visit = PageVisit::firstOrFail();
        $this->assertSame('/en/contact/', $visit->path);
        $this->assertSame('203.0.113.8', $visit->ip_address);
        $this->assertSame('google.com', $visit->referrer_host);
        $this->assertSame('mobile', $visit->device);
        $this->assertSame('Safari', $visit->browser);
        $this->assertSame('iOS', $visit->platform);
        $this->assertSame(1, PageVisit::distinct()->count('visitor_hash'));
        $this->assertStringNotContainsString('203.0.113.8', DB::table('page_visits')->value('ip_address'));
        $this->assertStringNotContainsString('private', json_encode(DB::table('page_visits')->first()));
    }

    public function test_bots_head_prefetch_admin_and_errors_are_excluded(): void
    {
        $this->publicPage();
        $this->withHeader('User-Agent', 'Googlebot')->get('/en/contact/')->assertOk();
        $this->withHeader('User-Agent', $this->agent)->head('/en/contact/')->assertOk();
        $this->withHeader('Purpose', 'prefetch')->get('/en/contact/')->assertOk();
        $this->flushHeaders();
        $this->withHeader('User-Agent', $this->agent)->get('/en/not-found/')->assertNotFound();
        $this->actingAsAdmin();
        $this->get('/en/contact/')->assertOk();
        $this->get('/admin')->assertOk();
        $this->assertDatabaseCount('page_visits', 0);
    }

    public function test_disabled_analytics_and_lookup_failures_do_not_break_pages(): void
    {
        $this->publicPage();
        config(['analytics.enabled' => false]);
        $this->withHeader('User-Agent', $this->agent)->get('/en/contact/')->assertOk();
        $this->assertDatabaseCount('page_visits', 0);
        config(['analytics.enabled' => true]);
        $this->mock(CountryLookup::class, fn ($mock) => $mock->shouldReceive('lookup')->andThrow(new \RuntimeException('test unavailable')));
        $this->get('/en/contact/')->assertOk();
    }

    public function test_dashboard_aggregates_filters_and_paginates_private_analytics(): void
    {
        $this->travelTo(now()->setDate(2030, 6, 10));
        $this->visit();
        $this->visit(['path' => '/tr/iletisim/', 'locale' => 'tr']);
        $this->visit(['ip_address' => '8.8.8.8', 'ip_hash' => VisitRecorder::ipHash('8.8.8.8'), 'visitor_hash' => 'visitor-b', 'country_code' => 'US', 'device' => 'desktop']);
        $this->visit(['visited_at' => now()->subDays(40), 'path' => '/old/']);
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->actingAsAdmin();
        $this->get('/admin')->assertOk()->assertViewHas('analytics', fn ($a) => $a['total'] === 3 && $a['visitors'] === 2 && $a['ipCount'] === 2 && $a['countryCount'] === 2)->assertSee('203.0.113.8')->assertDontSee('/old/');
        $this->get('/admin?country=TR&device=mobile&ip=203.0.113.8')->assertOk()->assertViewHas('analytics', fn ($a) => $a['total'] === 2 && $a['visitors'] === 1);
        $this->get('/admin?from=2030-05-01&to=2030-05-31')->assertOk()->assertViewHas('analytics', fn ($a) => $a['total'] === 1);
        for ($i = 0; $i < 18; $i++) {
            $this->visit();
        }
        $this->get('/admin?country=TR&page=2')->assertOk()->assertViewHas('analytics', fn ($a) => $a['recent']->currentPage() === 2 && str_contains($a['recent']->url(1), 'country=TR'));
    }

    public function test_invalid_filters_and_excessive_date_ranges_are_rejected(): void
    {
        $this->actingAsAdmin();
        $this->getJson('/admin?ip=not-an-ip&device=robot&country=invalid')->assertUnprocessable()->assertJsonValidationErrors(['ip', 'device', 'country']);
        $this->getJson('/admin?from=2030-06-10&to=2030-06-01')->assertUnprocessable();
        $this->getJson('/admin?from=2020-01-01&to=2030-01-01')->assertUnprocessable();
    }

    public function test_expired_records_are_pruned_and_local_ips_have_no_country(): void
    {
        $this->visit(['visited_at' => now()->subDays(91)]);
        $this->visit(['visited_at' => now()->subDays(89)]);
        $this->artisan('charlot:analytics-prune')->assertSuccessful();
        $this->assertDatabaseCount('page_visits', 1);
        $this->assertNull(app(CountryLookup::class)->lookup('127.0.0.1'));
        $this->assertNull(app(CountryLookup::class)->lookup('192.168.1.5'));
        config(['analytics.country_database' => '/nonexistent-country.mmdb']);
        $this->assertNull(app(CountryLookup::class)->lookup('8.8.8.8'));
    }
}
