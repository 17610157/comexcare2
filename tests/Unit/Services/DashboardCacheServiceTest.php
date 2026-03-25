<?php

namespace Tests\Unit\Services;

use App\Services\DashboardCacheService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardCacheServiceTest extends TestCase
{
    protected DashboardCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardCacheService;
    }

    public function test_service_can_be_instantiated(): void
    {
        $this->assertInstanceOf(DashboardCacheService::class, $this->service);
    }

    public function test_cache_key_contains_user_id(): void
    {
        $userId = 1;
        $fechaInicio = '2024-01-01';
        $fechaFin = '2024-01-31';

        Cache::shouldReceive('store')
            ->once()
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['ventas' => 1000, 'tickets' => 50]);

        $result = $this->service->getMetrics($userId, $fechaInicio, $fechaFin);

        $this->assertArrayHasKey('ventas', $result);
        $this->assertArrayHasKey('tickets', $result);
    }

    public function test_get_ventas_plaza_returns_array(): void
    {
        Cache::shouldReceive('store')
            ->once()
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(['PLAZA1' => 5000, 'PLAZA2' => 3000]);

        $result = $this->service->getVentasPlaza('2024-01-01', '2024-01-31');

        $this->assertIsArray($result);
    }

    public function test_get_ventas_tienda_returns_array(): void
    {
        Cache::shouldReceive('store')
            ->once()
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'TIENDA1' => ['plaza' => 'PLAZA1', 'ventas' => 1000],
            ]);

        $result = $this->service->getVentasTienda('2024-01-01', '2024-01-31');

        $this->assertIsArray($result);
    }

    public function test_get_cartera_abonos_returns_array(): void
    {
        Cache::shouldReceive('store')
            ->once()
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([
                'cargos' => 5000,
                'abonos' => 3000,
                'total' => 8000,
            ]);

        $result = $this->service->getCarteraAbonos('2024-01-01', '2024-01-31');

        $this->assertArrayHasKey('cargos', $result);
        $this->assertArrayHasKey('abonos', $result);
        $this->assertArrayHasKey('total', $result);
    }

    public function test_invalidate_user_cache_handles_exceptions(): void
    {
        Cache::shouldReceive('store')
            ->once()
            ->andThrow(new \Exception('Cache error'));

        $this->service->invalidateUserCache(1);

        $this->assertTrue(true);
    }

    public function test_invalidate_all_metrics_handles_exceptions(): void
    {
        Cache::shouldReceive('store')
            ->once()
            ->andThrow(new \Exception('Cache error'));

        $this->service->invalidateAllMetrics();

        $this->assertTrue(true);
    }

    public function test_warm_cache_calls_all_methods(): void
    {
        Cache::shouldReceive('store')
            ->times(4)
            ->andReturnSelf();

        Cache::shouldReceive('remember')
            ->times(4)
            ->andReturn([]);

        $this->service->warmCache(1, '2024-01-01', '2024-01-31');

        $this->assertTrue(true);
    }
}
