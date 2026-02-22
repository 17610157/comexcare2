<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CarteraAbonosCacheService
{
    private const CACHE_PREFIX = 'cartera_abonos_';
    private const REAL_TIME_CACHE_TTL = 300; // 5 minutos para tiempo real
    private const PREAGGREGATED_CACHE_TTL = 1800; // 30 minutos para datos preagregados
    
    /**
     * Obtener datos cacheados o generarlos si no existen
     */
    public function getCachedData(array $params): array
    {
        $cacheKey = $this->generateCacheKey($params);
        
        // Intentar obtener de caché en tiempo real
        $cachedData = Cache::get($cacheKey);
        if ($cachedData !== null) {
            Log::info('Cache hit para Cartera Abonos', ['cache_key' => $cacheKey]);
            return $cachedData;
        }
        
        // Generar datos y cachear
        $data = $this->generateOptimizedData($params);
        Cache::put($cacheKey, $data, self::REAL_TIME_CACHE_TTL);
        
        // Preparar caché para próximos requests
        $this->warmupRelatedCaches($params);
        
        return $data;
    }
    
    /**
     * Generar datos con query optimizado
     */
    private function generateOptimizedData(array $params): array
    {
        $start = $params['start'] ?? Carbon::parse('first day of previous month')->toDateString();
        $end = $params['end'] ?? Carbon::parse('last day of previous month')->toDateString();
        $search = $params['search'] ?? '';
        $plaza = $params['plaza'] ?? '';
        $tienda = $params['tienda'] ?? '';
        $offset = $params['offset'] ?? 0;
        $limit = $params['limit'] ?? 10;
        
        // Query optimizado con CTEs y estrategias de performance
        $sql = "
        WITH 
        -- Primero obtener los abonos base (tabla más pequeña con filtros)
        abonos_base AS (
            SELECT 
                c.cplaza, c.ctienda, c.fecha, c.concepto, c.tipo_ref, c.no_ref, 
                c.clave_cl, c.IMPORTE, c.estado, c.cborrado
            FROM cobranza c
            WHERE c.cargo_ab = 'A' 
            AND c.estado = 'S' 
            AND c.cborrado <> '1'
            AND c.fecha BETWEEN :start AND :end
            -- Filtros exactos primero (más selectivos)
            AND (:plaza = '' OR c.cplaza = :plaza)
            AND (:tienda = '' OR c.ctienda = :tienda)
        ),
        -- Obtener información de clientes (join más pequeño)
        clientes_info AS (
            SELECT 
                cl.ctienda, cl.cplaza, cl.clie_clave, 
                cl.clie_rfc, cl.clie_nombr, cl.clie_credi
            FROM cliente_depurado cl
            WHERE EXISTS (
                SELECT 1 FROM abonos_base ab 
                WHERE ab.cplaza = cl.cplaza 
                AND ab.ctienda = cl.ctienda 
                AND ab.clave_cl = cl.clie_clave
            )
        ),
        -- Obtener fechas de vencimiento (subquery optimizado)
        fechas_vencimiento AS (
            SELECT 
                co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.clave_cl,
                co.fecha_venc, co.dfechafac
            FROM cobranza co
            WHERE co.cargo_ab = 'C'
            AND EXISTS (
                SELECT 1 FROM abonos_base ab 
                WHERE ab.cplaza = co.cplaza 
                AND ab.ctienda = co.ctienda 
                AND ab.tipo_ref = co.tipo_ref 
                AND ab.no_ref = co.no_ref 
                AND ab.clave_cl = co.clave_cl
            )
        )
        -- Query final con joins optimizados
        SELECT 
            ab.cplaza AS plaza,
            ab.ctienda AS tienda,
            ab.fecha AS fecha,
            COALESCE(fv.dfechafac, ab.fecha) AS fecha_vta,
            ab.concepto AS concepto,
            ab.tipo_ref AS tipo,
            ab.no_ref AS factura,
            ab.clave_cl AS clave,
            COALESCE(ci.clie_rfc, '') AS rfc,
            COALESCE(ci.clie_nombr, '') AS nombre,
            CASE WHEN ab.tipo_ref = 'FA' AND ab.concepto <> 'DV' THEN ab.IMPORTE ELSE 0 END AS monto_fa,
            CASE WHEN ab.tipo_ref = 'FA' AND ab.concepto = 'DV' THEN ab.IMPORTE ELSE 0 END AS monto_dv,
            CASE WHEN ab.tipo_ref = 'CD' AND ab.concepto <> 'DV' THEN ab.IMPORTE ELSE 0 END AS monto_cd,
            COALESCE(ci.clie_credi, 0) AS dias_cred,
            (ab.fecha - COALESCE(fv.fecha_venc, ab.fecha)) AS dias_vencidos
        FROM abonos_base ab
        LEFT JOIN clientes_info ci ON (
            ab.cplaza = ci.cplaza 
            AND ab.ctienda = ci.ctienda 
            AND ab.clave_cl = ci.clie_clave
        )
        LEFT JOIN fechas_vencimiento fv ON (
            ab.cplaza = fv.cplaza 
            AND ab.ctienda = fv.ctienda 
            AND ab.tipo_ref = fv.tipo_ref 
            AND ab.no_ref = fv.no_ref 
            AND ab.clave_cl = fv.clave_cl
        )
        -- Búsqueda de texto (último filtro, menos selectivo)
        WHERE (:search = '' OR 
            ab.cplaza ILIKE :search OR
            ab.ctienda ILIKE :search OR
            ci.clie_nombr ILIKE :search OR
            ci.clie_rfc ILIKE :search OR
            ab.no_ref ILIKE :search OR
            ab.clave_cl ILIKE :search)
        ORDER BY ab.cplaza, ab.ctienda, ab.fecha DESC
        LIMIT :limit OFFSET :offset";
        
        $bindings = [
            'start' => $start,
            'end' => $end,
            'plaza' => $plaza,
            'tienda' => $tienda,
            'search' => '%' . $search . '%',
            'limit' => $limit,
            'offset' => $offset
        ];
        
        $startTime = microtime(true);
        $results = DB::select($sql, $bindings);
        $queryTime = (microtime(true) - $startTime) * 1000;
        
        Log::info('Query optimizado ejecutado', [
            'query_time_ms' => round($queryTime, 2),
            'results_count' => count($results),
            'params' => $params
        ]);
        
        return [
            'data' => $results,
            'query_time' => $queryTime,
            'cache_key' => $cacheKey ?? null
        ];
    }
    
    /**
     * Obtener conteo total para paginación (query separado y optimizado)
     */
    public function getCachedCount(array $params): int
    {
        $countCacheKey = $this->generateCountCacheKey($params);
        
        return Cache::remember($countCacheKey, self::REAL_TIME_CACHE_TTL, function() use ($params) {
            $start = $params['start'] ?? Carbon::parse('first day of previous month')->toDateString();
            $end = $params['end'] ?? Carbon::parse('last day of previous month')->toDateString();
            $search = $params['search'] ?? '';
            $plaza = $params['plaza'] ?? '';
            $tienda = $params['tienda'] ?? '';
            
            // Query de conteo optimizado
            $sql = "
            WITH abonos_filtrados AS (
                SELECT c.cplaza, c.ctienda, c.clave_cl, c.tipo_ref, c.no_ref
                FROM cobranza c
                WHERE c.cargo_ab = 'A' 
                AND c.estado = 'S' 
                AND c.cborrado <> '1'
                AND c.fecha BETWEEN :start AND :end
                AND (:plaza = '' OR c.cplaza = :plaza)
                AND (:tienda = '' OR c.ctienda = :tienda)
            )
            SELECT COUNT(*) as total
            FROM abonos_filtrados af
            LEFT JOIN cliente_depurado ci ON (
                af.cplaza = ci.cplaza 
                AND af.ctienda = ci.ctienda 
                AND af.clave_cl = ci.clie_clave
            )
            WHERE (:search = '' OR 
                af.cplaza ILIKE :search OR
                af.ctienda ILIKE :search OR
                ci.clie_nombr ILIKE :search OR
                ci.clie_rfc ILIKE :search OR
                af.no_ref ILIKE :search OR
                af.clave_cl ILIKE :search)";
            
            $bindings = [
                'start' => $start,
                'end' => $end,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'search' => '%' . $search . '%'
            ];
            
            $result = DB::selectOne($sql, $bindings);
            return (int) ($result->total ?? 0);
        });
    }
    
    /**
     * Preparar caché para búsquedas comunes
     */
    private function warmupRelatedCaches(array $params): void
    {
        // Preparar caché para las mismas fechas sin filtros
        $baseParams = [
            'start' => $params['start'],
            'end' => $params['end'],
            'search' => '',
            'plaza' => '',
            'tienda' => ''
        ];
        
        $baseCacheKey = $this->generateCacheKey($baseParams);
        if (!Cache::has($baseCacheKey)) {
            // Generar en background para próximos requests
            dispatch(function() use ($baseParams) {
                $this->generateOptimizedData($baseParams);
            })->afterResponse();
        }
    }
    
    /**
     * Invalidar caché cuando hay cambios en los datos
     */
    public function invalidateCache(): void
    {
        $pattern = self::CACHE_PREFIX . '*';
        $keys = Cache::getRedis()->keys($pattern);
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
            Log::info('Caché de Cartera Abonos invalidado', ['keys_count' => count($keys)]);
        }
    }
    
    /**
     * Generar clave de caché única
     */
    private function generateCacheKey(array $params): string
    {
        ksort($params);
        return self::CACHE_PREFIX . md5(json_encode($params));
    }
    
    /**
     * Generar clave de caché para conteo
     */
    private function generateCountCacheKey(array $params): string
    {
        $countParams = $params;
        unset($countParams['offset'], $countParams['limit']);
        ksort($countParams);
        return self::CACHE_PREFIX . 'count_' . md5(json_encode($countParams));
    }
    
    /**
     * Obtener estadísticas de caché para monitoreo
     */
    public function getCacheStats(): array
    {
        $pattern = self::CACHE_PREFIX . '*';
        $keys = Cache::getRedis()->keys($pattern);
        
        $stats = [
            'total_keys' => count($keys),
            'memory_usage' => 0,
            'oldest_key' => null,
            'newest_key' => null
        ];
        
        if (!empty($keys)) {
            $pipe = Cache::getRedis()->pipeline();
            foreach ($keys as $key) {
                $pipe->ttl($key);
            }
            $ttls = $pipe->execute();
            
            $stats['memory_usage'] = Cache::getRedis()->memory('usage');
            
            if (!empty($ttls)) {
                $maxTtl = max($ttls);
                $minTtl = min($ttls);
                $stats['oldest_key'] = $keys[array_search($maxTtl, $ttls)];
                $stats['newest_key'] = $keys[array_search($minTtl, $ttls)];
            }
        }
        
        return $stats;
    }
}