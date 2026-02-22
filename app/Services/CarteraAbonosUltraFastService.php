<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CarteraAbonosUltraFastService
{
    private const CACHE_PREFIX = 'cartera_ultra_';
    private const PRELOAD_CACHE_TTL = 7200; // 2 horas
    private const INCREMENTAL_CACHE_TTL = 300; // 5 minutos
    private const MAX_USERS_CONCURRENT = 500;
    
    /**
     * Arquitectura Ultra-Fast para 500 usuarios:
     * 1. Pre-carga de datos del periodo anterior en Redis
     * 2. Filtrado 100% cliente-side
     * 3. Actualizaciones incrementales en background
     * 4. Zero database queries en tiempo real
     */
    
    /**
     * Obtener datos pre-cargados (sin queries a BD)
     */
    public function getPreloadedData(): array
    {
        $cacheKey = $this->getPreloadCacheKey();
        
        try {
            // Intentar obtener de Redis (memoria)
            $cachedData = Redis::get($cacheKey);
            
            if ($cachedData) {
                $data = json_decode($cachedData, true);
                
                Log::info('Datos obtenidos de cache Redis', [
                    'cache_key' => $cacheKey,
                    'records_count' => count($data['data'] ?? []),
                    'memory_usage' => memory_get_usage(true)
                ]);
                
                return $data;
            }
            
            // Si no está en cache, pre-cargar asíncronamente
            $this->triggerPreloadAsync();
            
            // Devolver datos vacíos mientras carga
            return $this->getEmptyResponse();
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos pre-cargados', [
                'error' => $e->getMessage(),
                'cache_key' => $cacheKey
            ]);
            
            return $this->getEmptyResponse();
        }
    }
    
    /**
     * Pre-cargar datos del periodo anterior en background
     */
    public function preloadPeriodData(): array
    {
        $startTime = microtime(true);
        $period = $this->getPreviousPeriod();
        
        try {
            // Query optimizado para pre-carga (única vez)
            $data = $this->executePreloadQuery($period);
            
            // Estructurar datos para filtrado cliente-side
            $structuredData = $this->structureDataForClientSide($data);
            
            // Guardar en Redis con TTL largo
            $cacheKey = $this->getPreloadCacheKey();
            Redis::setex($cacheKey, self::PRELOAD_CACHE_TTL, json_encode($structuredData));
            
            // Crear índices de búsqueda en Redis
            $this->createSearchIndexes($structuredData);
            
            $loadTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Pre-carga completada exitosamente', [
                'period' => $period,
                'records_count' => count($data),
                'load_time_ms' => round($loadTime, 2),
                'cache_key' => $cacheKey,
                'memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
            ]);
            
            return [
                'status' => 'success',
                'records_count' => count($data),
                'load_time_ms' => round($loadTime, 2),
                'period' => $period,
                'cache_key' => $cacheKey
            ];
            
        } catch (\Exception $e) {
            Log::error('Error en pre-carga de datos', [
                'error' => $e->getMessage(),
                'period' => $period
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Actualización incremental (sin afectar usuarios)
     */
    public function incrementalUpdate(): array
    {
        $startTime = microtime(true);
        
        try {
            // Obtener cambios desde última actualización
            $changes = $this->getIncrementalChanges();
            
            if (empty($changes)) {
                return ['status' => 'no_changes', 'records_updated' => 0];
            }
            
            // Actualizar cache en Redis sin bloquear
            $this->updateRedisIncremental($changes);
            
            $updateTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Actualización incremental completada', [
                'changes_count' => count($changes),
                'update_time_ms' => round($updateTime, 2)
            ]);
            
            return [
                'status' => 'success',
                'records_updated' => count($changes),
                'update_time_ms' => round($updateTime, 2)
            ];
            
        } catch (\Exception $e) {
            Log::error('Error en actualización incremental', [
                'error' => $e->getMessage()
            ]);
            
            return ['status' => 'error', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Query optimizado para pre-carga (se ejecuta una sola vez)
     */
    private function executePreloadQuery(array $period): array
    {
        $sql = "
        WITH 
        -- Optimización: solo traer campos necesarios
        abonos_optimized AS (
            SELECT 
                c.cplaza, c.ctienda, c.fecha, c.concepto, c.tipo_ref, c.no_ref, c.clave_cl,
                c.IMPORTE,
                -- Fechas de vencimiento (subquery optimizada)
                COALESCE(c2.dfechafac, c.fecha) as fecha_vta,
                COALESCE(c2.fecha_venc, c.fecha) as fecha_venc,
                -- Datos cliente (join mínimo)
                COALESCE(cl.clie_rfc, '') as rfc,
                COALESCE(cl.clie_nombr, '') as nombre,
                COALESCE(cl.clie_credi, 0) as dias_cred
            FROM cobranza c
            LEFT JOIN (
                SELECT cplaza, ctienda, tipo_ref, no_ref, clave_cl, dfechafac, fecha_venc
                FROM cobranza 
                WHERE cargo_ab = 'C' 
                AND fecha BETWEEN :start AND :end
            ) c2 ON (
                c.cplaza = c2.cplaza AND c.ctienda = c2.ctienda 
                AND c.tipo_ref = c2.tipo_ref AND c.no_ref = c2.no_ref 
                AND c.clave_cl = c2.clave_cl
            )
            LEFT JOIN cliente_depurado cl ON (
                c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave
            )
            WHERE c.cargo_ab = 'A' 
            AND c.estado = 'S' 
            AND c.cborrado <> '1'
            AND c.fecha BETWEEN :start AND :end
        )
        SELECT 
            cplaza as plaza,
            ctienda as tienda,
            fecha,
            fecha_vta,
            concepto,
            tipo_ref as tipo,
            no_ref as factura,
            clave_cl as clave,
            rfc,
            nombre,
            -- Cálculos pre-procesados
            CASE WHEN tipo_ref = 'FA' AND concepto <> 'DV' THEN IMPORTE ELSE 0 END as monto_fa,
            CASE WHEN tipo_ref = 'FA' AND concepto = 'DV' THEN IMPORTE ELSE 0 END as monto_dv,
            CASE WHEN tipo_ref = 'CD' AND concepto <> 'DV' THEN IMPORTE ELSE 0 END as monto_cd,
            dias_cred,
            (fecha - COALESCE(fecha_venc, fecha)) as dias_vencidos
        FROM abonos_optimized
        ORDER BY cplaza, ctienda, fecha DESC";
        
        return DB::select($sql, [
            'start' => $period['start'],
            'end' => $period['end']
        ]);
    }
    
    /**
     * Estructurar datos para filtrado cliente-side ultra-rápido
     */
    private function structureDataForClientSide(array $data): array
    {
        $structured = [
            'data' => [],
            'indexes' => [
                'plaza' => [],
                'tienda' => [],
                'fecha' => [],
                'nombre' => [],
                'rfc' => [],
                'factura' => [],
                'clave' => []
            ],
            'stats' => [
                'total_records' => count($data),
                'unique_plazas' => 0,
                'unique_tiendas' => 0,
                'total_monto' => 0,
                'period' => $this->getPreviousPeriod()
            ],
            'metadata' => [
                'created_at' => now()->toISOString(),
                'expires_at' => now()->addSeconds(self::PRELOAD_CACHE_TTL)->toISOString(),
                'version' => '1.0.0'
            ]
        ];
        
        // Procesar datos y crear índices
        foreach ($data as $row) {
            $record = (array) $row;
            $structured['data'][] = $record;
            
            // Crear índices para búsqueda instantánea
            $this->addToIndex($structured['indexes'], 'plaza', $record['plaza'], count($structured['data']) - 1);
            $this->addToIndex($structured['indexes'], 'tienda', $record['tienda'], count($structured['data']) - 1);
            $this->addToIndex($structured['indexes'], 'fecha', $record['fecha'], count($structured['data']) - 1);
            $this->addToIndex($structured['indexes'], 'nombre', strtolower($record['nombre']), count($structured['data']) - 1);
            $this->addToIndex($structured['indexes'], 'rfc', strtolower($record['rfc']), count($structured['data']) - 1);
            $this->addToIndex($structured['indexes'], 'factura', $record['factura'], count($structured['data']) - 1);
            $this->addToIndex($structured['indexes'], 'clave', $record['clave'], count($structured['data']) - 1);
            
            // Calcular estadísticas
            $structured['stats']['total_monto'] += ($record['monto_fa'] + $record['monto_dv'] + $record['monto_cd']);
        }
        
        // Calcular únicos
        $structured['stats']['unique_plazas'] = count(array_unique(array_column($data, 'plaza')));
        $structured['stats']['unique_tiendas'] = count(array_unique(array_column($data, 'tienda')));
        
        return $structured;
    }
    
    /**
     * Agregar a índice para búsqueda instantánea
     */
    private function addToIndex(array &$indexes, string $field, string $value, int $position): void
    {
        if (!isset($indexes[$field][$value])) {
            $indexes[$field][$value] = [];
        }
        $indexes[$field][$value][] = $position;
    }
    
    /**
     * Crear índices de búsqueda en Redis para filtrado instantáneo
     */
    private function createSearchIndexes(array $structuredData): void
    {
        $indexKey = $this->getPreloadCacheKey() . '_indexes';
        
        // Guardar índices por separado para búsquedas ultra-rápidas
        Redis::setex($indexKey, self::PRELOAD_CACHE_TTL, json_encode($structuredData['indexes']));
    }
    
    /**
     * Obtener cambios incrementales
     */
    private function getIncrementalChanges(): array
    {
        // Lógica para obtener solo cambios desde última actualización
        // Esto se ejecuta en background sin afectar usuarios
        
        return [];
    }
    
    /**
     * Actualizar Redis incrementalmente
     */
    private function updateRedisIncremental(array $changes): void
    {
        // Actualizar cache sin bloquear a usuarios
        // Implementar lógica de actualización atómica
    }
    
    /**
     * Obtener periodo anterior
     */
    private function getPreviousPeriod(): array
    {
        $now = Carbon::now();
        return [
            'start' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
            'end' => $now->copy()->subMonth()->endOfMonth()->toDateString(),
            'name' => $now->copy()->subMonth()->format('Y-m')
        ];
    }
    
    /**
     * Obtener cache key para pre-carga
     */
    private function getPreloadCacheKey(): string
    {
        $period = $this->getPreviousPeriod();
        return self::CACHE_PREFIX . 'preload_' . $period['name'];
    }
    
    /**
     * Disparar pre-carga asíncrona
     */
    private function triggerPreloadAsync(): void
    {
        // Usar queue para pre-carga en background
        dispatch(function() {
            $this->preloadPeriodData();
        })->onQueue('preload')->afterResponse();
    }
    
    /**
     * Respuesta vacía mientras carga
     */
    private function getEmptyResponse(): array
    {
        return [
            'data' => [],
            'indexes' => [],
            'stats' => [
                'total_records' => 0,
                'unique_plazas' => 0,
                'unique_tiendas' => 0,
                'total_monto' => 0,
                'period' => $this->getPreviousPeriod()
            ],
            'metadata' => [
                'status' => 'loading',
                'message' => 'Pre-cargando datos del periodo anterior...',
                'created_at' => now()->toISOString()
            ]
        ];
    }
    
    /**
     * Verificar si hay datos pre-cargados
     */
    public function hasPreloadedData(): bool
    {
        $cacheKey = $this->getPreloadCacheKey();
        return Redis::exists($cacheKey);
    }
    
    /**
     * Forzar recarga de datos pre-cargados
     */
    public function forcePreload(): array
    {
        // Eliminar cache existente
        $cacheKey = $this->getPreloadCacheKey();
        Redis::del($cacheKey);
        
        // Pre-cargar nuevamente
        return $this->preloadPeriodData();
    }
    
    /**
     * Obtener estadísticas del sistema
     */
    public function getSystemStats(): array
    {
        return [
            'has_preloaded_data' => $this->hasPreloadedData(),
            'cache_keys' => $this->getCacheKeys(),
            'redis_memory' => $this->getRedisMemoryUsage(),
            'concurrent_users' => $this->getConcurrentUsers(),
            'period' => $this->getPreviousPeriod()
        ];
    }
    
    /**
     * Obtener cache keys activas
     */
    private function getCacheKeys(): array
    {
        $pattern = self::CACHE_PREFIX . '*';
        return Redis::keys($pattern);
    }
    
    /**
     * Obtener uso de memoria Redis
     */
    private function getRedisMemoryUsage(): array
    {
        return [
            'used_memory' => Redis::info('memory')['used_memory_human'] ?? 'Unknown',
            'used_memory_peak' => Redis::info('memory')['used_memory_peak_human'] ?? 'Unknown'
        ];
    }
    
    /**
     * Obtener usuarios concurrentes
     */
    private function getConcurrentUsers(): int
    {
        // Implementar lógica para contar usuarios activos
        return 0;
    }
}