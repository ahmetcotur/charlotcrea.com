<?php

namespace App\Services\Analytics;

use App\Models\PageVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VisitRecorder
{
    public static function ipHash(string $ip): string
    {
        // Normalize IPv6 representations before hashing.
        $canonical = @inet_pton($ip);

        return hash_hmac('sha256', $canonical === false ? $ip : bin2hex($canonical), config('app.key'));
    }

    public function record(Request $request): void
    {
        $agent = $request->userAgent() ?? '';
        if (! config('analytics.enabled') || $request->user('twill_users') || $request->user('customers') || $request->isMethod('HEAD') || ! $request->isMethod('GET') || preg_match('/bot|crawler|spider|slurp|headless|curl|wget|python|lighthouse|monitor|uptime|preview/i', $agent) || $request->header('Purpose') === 'prefetch' || str_contains($request->header('Sec-Purpose', ''), 'prefetch')) {
            return;
        }
        $ip = $request->ip();
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return;
        }
        $device = preg_match('/ipad|tablet|kindle|silk|android(?!.*mobile)/i', $agent) ? 'tablet' : (preg_match('/mobile|iphone|ipod|android/i', $agent) ? 'mobile' : ($agent === '' ? 'unknown' : 'desktop'));
        $browser = match (true) {
            (bool) preg_match('/edg(e|a|ios)?\//i', $agent) => 'Edge',
            (bool) preg_match('/opr\//i', $agent) => 'Opera',
            (bool) preg_match('/firefox|fxios/i', $agent) => 'Firefox',
            (bool) preg_match('/chrome|crios/i', $agent) => 'Chrome',
            (bool) preg_match('/safari/i', $agent) => 'Safari',
            default => 'Bilinmiyor',
        };
        $platform = match (true) {
            (bool) preg_match('/iphone|ipad|ipod/i', $agent) => 'iOS',
            (bool) preg_match('/android/i', $agent) => 'Android',
            (bool) preg_match('/windows/i', $agent) => 'Windows',
            (bool) preg_match('/macintosh|mac os/i', $agent) => 'macOS',
            (bool) preg_match('/linux/i', $agent) => 'Linux',
            default => 'Bilinmiyor',
        };
        $ipHash = self::ipHash($ip);
        $referrer = parse_url($request->header('referer', ''), PHP_URL_HOST);
        PageVisit::create([
            'visited_at' => now(),
            'path' => Str::limit('/'.trim($request->getPathInfo(), '/').'/', 1000, ''),
            'locale' => explode('/', trim($request->getPathInfo(), '/'))[0],
            'ip_address' => $ip,
            'ip_hash' => $ipHash,
            'visitor_hash' => hash_hmac('sha256', $ipHash.'|'.$agent, config('app.key')),
            'country_code' => app(CountryLookup::class)->lookup($ip),
            'device' => $device,
            'browser' => $browser,
            'platform' => $platform,
            'referrer_host' => is_string($referrer) ? mb_substr(strtolower($referrer), 0, 255) : null,
        ]);
    }
}
