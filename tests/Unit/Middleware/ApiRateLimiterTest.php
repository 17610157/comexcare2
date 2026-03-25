<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ApiRateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiRateLimiterTest extends TestCase
{
    protected ApiRateLimiter $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new ApiRateLimiter;
        RateLimiter::clear('api_general:test:*');
    }

    public function test_middleware_allows_requests_under_limit(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'api_general');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-RateLimit-Limit'));
        $this->assertTrue($response->headers->has('X-RateLimit-Remaining'));
    }

    public function test_middleware_blocks_requests_over_limit(): void
    {
        $request = Request::create('/api/test_block', 'GET');
        $request->server->set('REMOTE_ADDR', '10.255.255.1');

        for ($i = 0; $i < 100; $i++) {
            RateLimiter::hit('api_general:10.255.255.1:api/test_block', 60);
        }

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'api_general');

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));
    }

    public function test_api_agents_has_different_limits(): void
    {
        $request = Request::create('/api/heartbeat', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'api_agents');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(60, $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_api_auth_has_stricter_limits(): void
    {
        $request = Request::create('/api/login', 'POST');
        $request->server->set('REMOTE_ADDR', '10.0.0.1');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'api_auth');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(5, $response->headers->get('X-RateLimit-Limit'));
    }

    public function test_request_with_computer_id_uses_computer_key(): void
    {
        $request = Request::create('/api/heartbeat', 'POST');
        $request->merge(['computer_id' => 123]);
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'api_agents');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
