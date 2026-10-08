<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class XcorteRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveKey($request);
        $limit = (int) config('services.cortes.rate_limit', 60);

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);
            $seconds = min(max($seconds, 10), 120);

            return response()->json([
                'error' => 'Demasiadas peticiones',
                'message' => "Reintente en {$seconds} segundos",
                'retry_after' => $seconds,
            ], 429, [
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($key, 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $limit));

        return $response;
    }

    protected function resolveKey(Request $request): string
    {
        $identifier = $request->attributes->get('corte_origen')
            ?: ($request->header('X-API-Key') ?: $request->ip());

        return 'xcorte_api:'.sha1((string) $identifier);
    }
}
