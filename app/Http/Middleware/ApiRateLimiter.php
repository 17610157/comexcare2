<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    public function handle(Request $request, Closure $next, string $type = 'api_general'): Response
    {
        $limiterConfig = $this->getLimiterConfig($type);
        $key = $this->resolveRequestKey($request, $type);

        if (RateLimiter::tooManyAttempts($key, $limiterConfig['limit'])) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Too Many Attempts',
                'message' => 'Rate limit exceeded. Please wait before making more requests.',
                'retry_after' => $seconds,
                'limit' => $limiterConfig['limit'],
                'period' => $limiterConfig['period'],
            ], 429, [
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => $limiterConfig['limit'],
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($key, $limiterConfig['period']);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $limiterConfig['limit']);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($key, $limiterConfig['limit']));

        return $response;
    }

    protected function getLimiterConfig(string $type): array
    {
        return match ($type) {
            'api_agents' => [
                'limit' => 500,
                'period' => 60,
            ],
            'api_auth' => [
                'limit' => 30,
                'period' => 60,
            ],
            'api_download' => [
                'limit' => 100,
                'period' => 60,
            ],
            'api_report' => [
                'limit' => 100,
                'period' => 60,
            ],
            'api_heartbeat' => [
                'limit' => 300,
                'period' => 60,
            ],
            'api_commands' => [
                'limit' => 300,
                'period' => 60,
            ],
            default => [
                'limit' => 300,
                'period' => 60,
            ],
        };
    }

    protected function resolveRequestKey(Request $request, string $type): string
    {
        $identifier = $request->ip();

        if ($request->user()) {
            $identifier = $request->user()->id;
        }

        if ($request->has('computer_id')) {
            $identifier = 'computer:'.$request->computer_id;
        }

        return sprintf('%s:%s:%s', $type, $identifier, $request->path());
    }
}
