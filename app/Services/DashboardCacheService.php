<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardCacheService
{
    protected int $defaultTtl;

    protected string $store;

    public function __construct()
    {
        $this->defaultTtl = config('cache.dashboard_ttl', 300);
        $this->store = config('cache.default', 'redis');
    }

    public function getMetrics(int $userId, string $fechaInicio, string $fechaFin, array $plazas = [], array $tiendas = []): array
    {
        $cacheKey = $this->buildMetricsKey($userId, $fechaInicio, $fechaFin, $plazas, $tiendas);

        return Cache::store($this->store)->remember($cacheKey, $this->defaultTtl, function () use ($fechaInicio, $fechaFin, $plazas, $tiendas) {
            return $this->calculateMetrics($fechaInicio, $fechaFin, $plazas, $tiendas);
        });
    }

    public function getVentasPlaza(string $fechaInicio, string $fechaFin, array $plazas = [], array $tiendas = []): array
    {
        $cacheKey = $this->buildKey('ventas_plaza', $fechaInicio, $fechaFin, $plazas, $tiendas);

        return Cache::store($this->store)->remember($cacheKey, $this->defaultTtl, function () use ($fechaInicio, $fechaFin, $plazas, $tiendas) {
            return $this->calculateVentasPlaza($fechaInicio, $fechaFin, $plazas, $tiendas);
        });
    }

    public function getVentasTienda(string $fechaInicio, string $fechaFin, array $plazas = [], array $tiendas = []): array
    {
        $cacheKey = $this->buildKey('ventas_tienda', $fechaInicio, $fechaFin, $plazas, $tiendas);

        return Cache::store($this->store)->remember($cacheKey, $this->defaultTtl, function () use ($fechaInicio, $fechaFin, $plazas, $tiendas) {
            return $this->calculateVentasTienda($fechaInicio, $fechaFin, $plazas, $tiendas);
        });
    }

    public function getCarteraAbonos(string $fechaInicio, string $fechaFin, array $plazas = [], array $tiendas = []): array
    {
        $cacheKey = $this->buildKey('cartera_abonos', $fechaInicio, $fechaFin, $plazas, $tiendas);

        return Cache::store($this->store)->remember($cacheKey, $this->defaultTtl, function () use ($fechaInicio, $fechaFin, $plazas, $tiendas) {
            return $this->calculateCarteraAbonos($fechaInicio, $fechaFin, $plazas, $tiendas);
        });
    }

    public function invalidateUserCache(int $userId): void
    {
        try {
            Cache::store($this->store)->forget("dashboard:metrics:{$userId}:*");
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate dashboard cache: '.$e->getMessage());
        }
    }

    public function invalidateAllMetrics(): void
    {
        try {
            if ($this->store === 'redis') {
                Cache::tags(['dashboard'])->flush();
            } else {
                Cache::store($this->store)->clear();
            }
        } catch (\Exception $e) {
            Log::warning('Failed to flush dashboard cache: '.$e->getMessage());
        }
    }

    public function warmCache(int $userId, string $fechaInicio, string $fechaFin, array $plazas = [], array $tiendas = []): void
    {
        $this->getMetrics($userId, $fechaInicio, $fechaFin, $plazas, $tiendas);
        $this->getVentasPlaza($fechaInicio, $fechaFin, $plazas, $tiendas);
        $this->getVentasTienda($fechaInicio, $fechaFin, $plazas, $tiendas);
        $this->getCarteraAbonos($fechaInicio, $fechaFin, $plazas, $tiendas);
    }

    protected function buildMetricsKey(int $userId, string $fechaInicio, string $fechaFin, array $plazas, array $tiendas): string
    {
        $plazasKey = ! empty($plazas) ? implode('_', $plazas) : 'all';
        $tiendasKey = ! empty($tiendas) ? implode('_', $tiendas) : 'all';

        return "dashboard:metrics:{$userId}:{$fechaInicio}:{$fechaFin}:{$plazasKey}:{$tiendasKey}";
    }

    protected function buildKey(string $type, string $fechaInicio, string $fechaFin, array $plazas, array $tiendas): string
    {
        $plazasKey = ! empty($plazas) ? implode('_', $plazas) : 'all';
        $tiendasKey = ! empty($tiendas) ? implode('_', $tiendas) : 'all';

        return "dashboard:{$type}:{$fechaInicio}:{$fechaFin}:{$plazasKey}:{$tiendasKey}";
    }

    protected function calculateMetrics(string $fechaInicio, string $fechaFin, array $plazas, array $tiendas): array
    {
        $wherePlaza = '';
        $whereTienda = '';
        $params = [$fechaInicio, $fechaFin];

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $wherePlaza = " AND cplaza IN ($placeholders)";
            $params = array_merge($params, $plazas);

            if (! empty($tiendas)) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $whereTienda = " AND ctienda IN ($placeholders)";
                $params = array_merge($params, $tiendas);
            }
        } elseif (! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $whereTienda = " AND ctienda IN ($placeholders)";
            $params = array_merge($params, $tiendas);
        }

        $sql = "
            SELECT COALESCE(SUM(
                (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                (COALESCE(vtacred, 0) - COALESCE(descred, 0))
            ), 0) AS total_ventas,
            COUNT(*) AS total_tickets
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
            $wherePlaza $whereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
        ";

        $result = DB::select($sql, $params);
        $ventas = floatval($result[0]->total_ventas ?? 0);
        $tickets = intval($result[0]->total_tickets ?? 0);

        return [
            'ventas' => $ventas,
            'tickets' => $tickets,
            'ticket_promedio' => $tickets > 0 ? $ventas / $tickets : 0,
        ];
    }

    protected function calculateVentasPlaza(string $fechaInicio, string $fechaFin, array $plazas, array $tiendas): array
    {
        $params = [$fechaInicio, $fechaFin];
        $wherePlaza = '';
        $whereTienda = '';

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $wherePlaza = " AND cplaza IN ($placeholders)";
            $params = array_merge($params, $plazas);
        }

        if (! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $whereTienda = " AND ctienda IN ($placeholders)";
            $params = array_merge($params, $tiendas);
        }

        $sql = "
            SELECT cplaza AS plaza,
                   SUM((COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                       (COALESCE(vtacred, 0) - COALESCE(descred, 0))) AS ventas
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
            $wherePlaza $whereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
            GROUP BY cplaza
            ORDER BY ventas DESC
        ";

        $result = DB::select($sql, $params);
        $data = [];

        foreach ($result as $row) {
            $data[$row->plaza] = floatval($row->ventas);
        }

        return $data;
    }

    protected function calculateVentasTienda(string $fechaInicio, string $fechaFin, array $plazas, array $tiendas): array
    {
        $params = [$fechaInicio, $fechaFin];
        $wherePlaza = '';
        $whereTienda = '';

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $wherePlaza = " AND cplaza IN ($placeholders)";
            $params = array_merge($params, $plazas);
        }

        if (! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $whereTienda = " AND ctienda IN ($placeholders)";
            $params = array_merge($params, $tiendas);
        }

        $sql = "
            SELECT ctienda AS tienda,
                   cplaza AS plaza,
                   SUM((COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                       (COALESCE(vtacred, 0) - COALESCE(descred, 0))) AS ventas
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
            $wherePlaza $whereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
            GROUP BY ctienda, cplaza
            ORDER BY ventas DESC
            LIMIT 20
        ";

        $result = DB::select($sql, $params);
        $data = [];

        foreach ($result as $row) {
            $data[$row->tienda] = [
                'plaza' => $row->plaza,
                'ventas' => floatval($row->ventas),
            ];
        }

        return $data;
    }

    protected function calculateCarteraAbonos(string $fechaInicio, string $fechaFin, array $plazas, array $tiendas): array
    {
        $query = DB::table('cartera_abonos_cache')
            ->selectRaw('COALESCE(SUM(monto_fa), 0) AS cargos,
                        COALESCE(SUM(monto_cd), 0) AS abonos,
                        COALESCE(SUM(monto_fa + monto_dv + monto_cd), 0) AS total')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if (! empty($plazas)) {
            $query->whereIn('plaza', $plazas);
        }

        if (! empty($tiendas)) {
            $query->whereIn('tienda', $tiendas);
        }

        $result = $query->first();

        return [
            'cargos' => floatval($result->cargos ?? 0),
            'abonos' => floatval($result->abonos ?? 0),
            'total' => floatval($result->total ?? 0),
        ];
    }
}
