<?php

namespace Tests\Feature;

use App\Models\Inquiry;
use App\Models\Page;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EnquirySessionTest extends TestCase
{
    use RefreshDatabase;

    private function prepare(): void
    {
        $this->artisan('charlot:import')->assertSuccessful();
        $this->artisan('charlot:prepare-enquiry-pages')->assertSuccessful();
        config(['cache.default' => 'array', 'charlot.page_cache_seconds' => 300]);
        Cache::flush();
        $this->withoutMiddleware(ThrottleRequests::class);
        // Exercise actual token checks, which Laravel normally bypasses in tests.
        $this->app->bind(PreventRequestForgery::class, fn ($app) => new class($app, $app->make(Encrypter::class)) extends PreventRequestForgery
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
    }

    public function test_forms_render_each_sessions_token_and_stay_on_the_current_host(): void
    {
        $this->prepare();
        foreach ([
            '/en/request-a-quote/' => '/quote-requests',
            '/tr/teklif-al/' => '/quote-requests',
            '/en/contact/' => '/contact-inquiries',
            '/tr/iletisim/' => '/contact-inquiries',
        ] as $path => $action) {
            foreach (['a', 'b'] as $visitor) {
                $token = str_repeat($visitor, 40);
                $this->withSession(['_token' => $token])->get($path)->assertOk()
                    ->assertSee('name="_token" value="'.$token.'"', false)
                    ->assertSee('action="'.$action.'"', false)
                    ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
                $this->postJson($action, ['_token' => $token, 'locale' => 'en'])
                    ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'details']);
            }
        }
        $this->assertDatabaseCount('inquiries', 0);
    }

    public function test_custom_form_paths_are_not_cached_and_csrf_remains_enforced(): void
    {
        $this->prepare();
        Page::where('import_key', '/en/request-a-quote/')->firstOrFail()->translate('en')->update(['path' => '/en/project-brief/']);
        foreach (['c', 'd'] as $visitor) {
            $token = str_repeat($visitor, 40);
            $this->withSession(['_token' => $token])->get('/en/project-brief/')->assertOk()
                ->assertSee('name="_token" value="'.$token.'"', false);
        }
        $this->postJson('/quote-requests', ['_token' => str_repeat('x', 40), 'locale' => 'en'])->assertStatus(419);
        $this->assertSame(0, Inquiry::count());
    }
}
