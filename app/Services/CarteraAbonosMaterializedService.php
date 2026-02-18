<?php

namespace App\Services;

use App\Jobs\CarteraAbonosSyncJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Carbon\Carbon;

class CarteraAbonosMaterializedService
{
    private const CACHE_PREFIX = 'cartera_materialized_';
    private const SYNC_INTERVAL = 300; // 5 minutos
    private const FALLBACK_TIMEOUT = 30; // 30 segundos

    /**
     * Obtener datos de tabla materializada (tiempo real)
     */
    public function getData(array $params): array
    {
        $startTime = microtime(true);
        
        try {
            // Verificar si la tabla materializada está actualizada
            if (!$this->isMaterializedDataFresh()) {
                // Disparar sincronización en background
                $this->triggerBackgroundSync();
                
                // Usar fallback a tabla original si es muy antiguo
                if ($this->isMaterializedDataStale()) {
                    Log::warning("Datos materializados muy antiguos, usando fallback");
                    return $this->getFallbackData($params);
                }
            }

            // Consultar tabla materializada optimizada
            $data = $this->queryMaterializedTable($params);
            
            $queryTime = (microtime(true) - $startTime) * 1000;
            
            Log::info("Consulta materializada ejecutada", [
                'query_time_ms' => round($queryTime, 2),
                'records_count' => count($data),
                'params' => $params,
                'data_source' => 'materialized'
            ]);

            return [
                'data' => $data,
                'query_time' => $queryTime,
                'data_source' => 'materialized',
                'last_sync' => $this->getLastSyncTime()
            ];

        } catch (\Exception $e) {
            Log::error("Error consultando tabla materializada", [
                'error' => $e->getMessage(),
                'fallback_used' => true
            ]);

            // Fallback automático a tabla original
            return $this->getFallbackData($params);
        }
    }

    /**
     * Obtener conteo total desde tabla materializada
     */
    public function getCount(array $params): int
    {
        try {
            $query = DB::table('cartera_abonos_materialized')
                ->where('sync_status', 'active');

            // Aplicar filtros
            $this->applyFilters($query, $params);

            return $query->count();

        } catch (\Exception $e) {
            Log::error("Error obteniendo conteo materializado", ['error' => $e->getMessage()]);
            
            // Fallback a conteo original
            return $this->getFallbackCount($params);
        }
    }

    /**
     * Consultar tabla materializada con filtros
     */
    private function queryMaterializedTable(array $params): array
    {
        $query = DB::table('cartera_abonos_materialized')
            ->where('sync_status', 'active')
            ->select([
                'plaza', 'tienda', 'fecha', 'fecha_vta', 'concepto', 'tipo',
                'factura', 'clave', 'rfc', 'nombre', 'monto_fa', 'monto_dv',
                'monto_cd', 'dias_cred', 'dias_vencidos'
            ]);

        // Aplicar filtros
        $this->applyFilters($query, $params);

        // Búsqueda de texto
        if (!empty($params['search'])) {
            $search = '%' . $params['search'] . '%';
            $query->where(function($q) use ($search) {
                $q->where('plaza', 'ILIKE', $search)
                  ->orWhere('tienda', 'ILIKE', $search)
                  ->orWhere('nombre', 'ILIKE', $search)
                  ->orWhere('rfc', 'ILIKE', $search)
                  ->orWhere('factura', 'ILIKE', $search)
                  ->orWhere('clave', 'ILIKE', $search);
            });
        }

        // Ordenamiento y paginación
        $query->orderBy('plaza')->orderBy('tienda')->orderBy('fecha', 'desc');

        if (isset($params['limit'])) {
            $query->limit($params['limit']);
        }

        if (isset($params['offset'])) {
            $query->offset($params['offset']);
        }

        return $query->get()->toArray();
    }

    /**
     * Aplicar filtros a la consulta
     */
    private function applyFilters($query, array $params): void
    {
        // Filtro de fechas
        if (!empty($params['start'])) {
            $query->where('fecha', '>=', $params['start']);
        }

        if (!empty($params['end'])) {
            $query->where('fecha', '<=', $params['end']);
        }

        // Filtros exactos
        if (!empty($params['plaza'])) {
            $query->where('plaza', $params['plaza']);
        }

        if (!empty($params['tienda'])) {
            $query->where('tienda', $params['tienda']);
        }
    }

    /**
     * Verificar si los datos materializados están frescos
     */
    private function isMaterializedDataFresh(): bool
    {
        $lastSync = $this->getLastSyncTime();
        
        if (!$lastSync) {
            return false;
        }

        $freshnessThreshold = Carbon::now()->subSeconds(self::SYNC_INTERVAL);
        return $lastSync->greaterThan($freshnessThreshold);
    }

    /**
     * Verificar si los datos materializados están muy antiguos
     */
    private function isMaterializedDataStale(): bool
    {
        $lastSync = $this->getLastSyncTime();
        
        if (!$lastSync) {
            return true;
        }

        $staleThreshold = Carbon::now()->subSeconds(self::FALLBACK_TIMEOUT);
        return $lastSync->lessThan($staleThreshold);
    }

    /**
     * Obtener última sincronización
     */
    private function getLastSyncTime(): ?Carbon
    {
        $cacheKey = self::CACHE_PREFIX . 'last_sync';
        
        return Cache::remember($cacheKey, 60, function() {
            $lastSync = DB::table('cartera_abonos_sync_control')
                ->where('status', 'completed')
                ->orderBy('completed_at', 'desc')
                ->first();

            return $lastSync ? Carbon::parse($lastSync->completed_at) : null;
        });
    }

    /**
     * Disparar sincronización en background
     */
    private function triggerBackgroundSync(): void
    {
        $cacheKey = self::CACHE_PREFIX . 'sync_running';
        
        // Verificar si ya hay una sincronización en curso
        if (Cache::has($cacheKey)) {
            return;
        }

        // Marcar sincronización en curso
        Cache::put($cacheKey, true, 600); // 10 minutos máximo

        // Determinar tipo de sincronización
        $syncType = $this->determineSyncType();

        // Disparar job en background
        CarteraAbonosSyncJob::dispatch($syncType)
            ->onQueue('sync')
            ->afterResponse();

        Log::info("Sincronización disparada en background", [
            'sync_type' => $syncType,
            'triggered_by' => 'materialized_service'
        ]);
    }

    /**
     * Determinar tipo de sincronización necesario
     */
    private function determineSyncType(): string
    {
        // Verificar si hay cambios pendientes
        $pendingChanges = DB::table('cartera_abonos_change_log')
            ->where('processed', false)
            ->count();

        // Si hay muchos cambios, hacer full sync
        if ($pendingChanges > 10000) {
            return 'full';
        }

        // Si hay cambios recientes, hacer incremental
        if ($pendingChanges > 0) {
            return 'incremental';
        }

        // Verificar última sincronización
        $lastSync = $this->getLastSyncTime();
        
        // Si no hay sincronización previa, hacer full
        if (!$lastSync) {
            return 'full';
        }

        // Si la última sincronización es muy antigua, hacer full
        $fullSyncThreshold = Carbon::now()->subHours(24);
        if ($lastSync->lessThan($fullSyncThreshold)) {
            return 'full';
        }

        // Sino, incremental
        return 'incremental';
    }

    /**
     * Fallback a tabla original (query optimizado)
     */
    private function getFallbackData(array $params): array
    {
        $startTime = microtime(true);
        
        try {
            // Usar el servicio cacheado original como fallback
            $cacheService = app(CarteraAbonosCacheService::class);
            $result = $cacheService->getCachedData($params);
            
            $queryTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'data' => $result['data'] ?? [],
                'query_time' => $queryTime,
                'data_source' => 'fallback_original',
                'last_sync' => $this->getLastSyncTime()
            ];

        } catch (\Exception $e) {
            Log::error("Error incluso en fallback", ['error' => $e->getMessage()]);
            
            return [
                'data' => [],
                'query_time' => 0,
                'data_source' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Fallback para conteo
     */
    private function getFallbackCount(array $params): int
    {
        try {
            $cacheService = app(CarteraAbonosCacheService::class);
            return $cacheService->getCachedCount($params);
        } catch (\Exception $e) {
            Log::error("Error en fallback count", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * Forzar sincronización completa
     */
    public function forceFullSync(): array
    {
        try {
            // Limpiar tabla materializada
            DB::table('cartera_abonos_materialized')->truncate();

            // Disparar full sync
            $job = new CarteraAbonosSyncJob('full');
            $job->handle();

            // Limpiar cachés
            $this->clearAllCaches();

            return [
                'status' => 'success',
                'message' => 'Sincronización completa forzada',
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            Log::error("Error forzando full sync", ['error' => $e->getMessage()]);
            
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Obtener estadísticas de sincronización
     */
    public function getSyncStats(): array
    {
        $stats = [
            'last_sync' => $this->getLastSyncTime(),
            'is_fresh' => $this->isMaterializedDataFresh(),
            'is_stale' => $this->isMaterializedDataStale(),
            'pending_changes' => $this->getPendingChangesCount(),
            'total_records' => $this->getTotalRecords(),
            'sync_history' => $this->getSyncHistory()
        ];

        return $stats;
    }

    /**
     * Obtener conteo de cambios pendientes
     */
    private function getPendingChangesCount(): int
    {
        return DB::table('cartera_abonos_change_log')
            ->where('processed', false)
            ->count();
    }

    /**
     * Obtener total de registros en tabla materializada
     */
    private function getTotalRecords(): int
    {
        return DB::table('cartera_abonos_materialized')
            ->where('sync_status', 'active')
            ->count();
    }

    /**
     * Obtener historial de sincronización
     */
    private function getSyncHistory(): array
    {
        return DB::table('cartera_abonos_sync_control')
            ->orderBy('started_at', 'desc')
            ->limit(5)
            ->get()
            ->toArray();
    }

    /**
     * Limpiar todas las cachés relacionadas
     */
    private function clearAllCaches(): void
    {
        $patterns = [
            self::CACHE_PREFIX . '*',
            'cartera_abonos_*',
            'reporte_cartera_abonos_*'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Verificar estado de salud del sistema
     */
    public function healthCheck(): array
    {
        $health = [
            'materialized_table_exists' => $this->checkMaterializedTable(),
            'sync_control_table_exists' => $this->checkSyncControlTable(),
            'change_log_table_exists' => $this->checkChangeLogTable(),
            'last_sync_successful' => $this->checkLastSyncSuccess(),
            'data_freshness' => $this->getDataFreshnessStatus(),
            'queue_processing' => $this->checkQueueStatus(),
            'overall_status' => 'healthy'
        ];

        // Determinar estado general
        foreach ($health as $key => $value) {
            if ($value !== true && $key !== 'overall_status') {
                $health['overall_status'] = 'degraded';
                break;
            }
        }

        return $health;
    }

    /**
     * Verificar existencia de tablas
     */
    private function checkMaterializedTable(): bool
    {
        try {
            DB::table('cartera_abonos_materialized')->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkSyncControlTable(): bool
    {
        try {
            DB::table('cartera_abonos_sync_control')->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkChangeLogTable(): bool
    {
        try {
            DB::table('cartera_abonos_change_log')->limit(1)->get();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar éxito de última sincronización
     */
    private function checkLastSyncSuccess(): bool
    {
        $lastSync = DB::table('cartera_abonos_sync_control')
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc')
            ->first();

        return $lastSync && $lastSync->records_processed > 0;
    }

    /**
     * Verificar estado de frescura de datos
     */
    private function getDataFreshnessStatus(): string
    {
        if ($this->isMaterializedDataFresh()) {
            return 'fresh';
        } elseif ($this->isMaterializedDataStale()) {
            return 'stale';
        } else {
            return 'acceptable';
        }
    }

    /**
     * Verificar estado de cola de procesamiento
     */
    private function checkQueueStatus(): bool
    {
        try {
            // Verificar si hay jobs pendientes en cola sync
            $pendingJobs = DB::table('jobs')
                ->where('queue', 'sync')
                ->count();

            return $pendingJobs < 10; // Umbral aceptable
        } catch (\Exception $e) {
            return false;
        }
    }
}