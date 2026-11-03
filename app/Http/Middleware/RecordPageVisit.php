<?php

namespace App\Http\Middleware;

use App\Services\Analytics\VisitRecorder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordPageVisit
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        if ($response->getStatusCode() === 200 && str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            try {
                app(VisitRecorder::class)->record($request);
            } catch (\Throwable $exception) {
                // Analytics must never interrupt delivery of a public page.
                report($exception);
            }
        }

        return $response;
    }
}
