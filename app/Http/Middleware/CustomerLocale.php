<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomerLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->query('lang', $request->session()->get('customer_locale', 'tr'));
        $locale = in_array($locale, ['tr', 'en'], true) ? $locale : 'tr';
        $request->session()->put('customer_locale', $locale);
        app()->setLocale($locale);
        $response = $next($request);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'same-origin');

        return $response;
    }
}
