<?php

namespace App\Jobs;

use App\Services\CarteraAbonosUltraFastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CarteraAbonosIncrementalUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutos máximo
    public $tries = 3;
    public $backoff = [60, 180, 300]; // 1min, 3min, 5min

    /**
     * Job de actualización incremental en background
     * No afecta a los 500 usuarios concurrentes
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info("Iniciando actualización incremental Cartera Abonos Ultra-Fast");
            
            // Obtener cambios desde última actualización
            $changes = $this->getIncrementalChanges();
            
            if (empty($changes)) {
                Log::info("No hay cambios para actualizar");
                return;
            }
            
            // Procesar cambios y actualizar Redis
            $this->processIncrementalChanges($changes);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::info("Actualización incremental completada", [
                'changes_processed' => count($changes),
                'execution_time_ms' => round($executionTime, 2),
                'timestamp' => now()->toISOString()
            ]);
            
            // Notificar a clientes conectados (si es necesario)
            $this->notifyClientsOfUpdate(count($changes));
            
        } catch (\Exception $e) {
            Log::error("Error en actualización incremental", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Obtener cambios incrementales desde última actualización
     */
    private function getIncrementalChanges(): array
    {
        $lastUpdateKey = 'cartera_ultra_last_update';
        $lastUpdate = Redis::get($lastUpdateKey);
        
        if (!$lastUpdate) {
            // Si no hay última actualización, usar hace 1 hora
            $lastUpdate = Carbon::now()->subHour()->toISOString();
        }
        
        // Query para obtener cambios recientes
        $sql = "
        SELECT 
            c.cplaza, c.ctienda, c.fecha, c.concepto, c.tipo_ref, c.no_ref, c.clave_cl,
            c.IMPORTE,
            COALESCE(c2.dfechafac, c.fecha) as fecha_vta,
            COALESCE(c2.fecha_venc, c.fecha) as fecha_venc,
            COALESCE(cl.clie_rfc, '') as rfc,
            COALESCE(cl.clie_nombr, '') as nombre,
            COALESCE(cl.clie_credi, 0) as dias_cred,
            c.updated_at as change_timestamp,
            'UPDATE' as change_type
        FROM cobranza c
        LEFT JOIN (
            SELECT cplaza, ctienda, tipo_ref, no_ref, clave_cl, dfechafac, fecha_venc
            FROM cobranza 
            WHERE cargo_ab = 'C'
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
        AND c.updated_at > :last_update
        ORDER BY c.updated_at ASC
        LIMIT 1000
        ";
        
        $changes = DB::select($sql, ['last_update' => $lastUpdate]);
        
        // También buscar nuevos registros
        $newRecordsSql = "
        SELECT 
            c.cplaza, c.ctienda, c.fecha, c.concepto, c.tipo_ref, c.no_ref, c.clave_cl,
            c.IMPORTE,
            COALESCE(c2.dfechafac, c.fecha) as fecha_vta,
            COALESCE(c2.fecha_venc, c.fecha) as fecha_venc,
            COALESCE(cl.clie_rfc, '') as rfc,
            COALESCE(cl.clie_nombr, '') as nombre,
            COALESCE(cl.clie_credi, 0) as dias_cred,
            c.created_at as change_timestamp,
            'INSERT' as change_type
        FROM cobranza c
        LEFT JOIN (
            SELECT cplaza, ctienda, tipo_ref, no_ref, clave_cl, dfechafac, fecha_venc
            FROM cobranza 
            WHERE cargo_ab = 'C'
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
        AND c.created_at > :last_update
        AND c.updated_at <= :last_update
        ORDER BY c.created_at ASC
        LIMIT 1000
        ";
        
        $newRecords = DB::select($newRecordsSql, ['last_update' => $lastUpdate]);
        
        // Combinar cambios
        $allChanges = array_merge($changes, $newRecords);
        
        // Actualizar timestamp de última actualización
        Redis::setex($lastUpdateKey, 86400, now()->toISOString()); // 24 horas TTL
        
        return $allChanges;
    }
    
    /**
     * Procesar cambios incrementales y actualizar Redis
     */
    private function processIncrementalChanges(array $changes): void
    {
        $preloadCacheKey = $this->getPreloadCacheKey();
        
        // Obtener datos actuales de Redis
        $currentData = Redis::get($preloadCacheKey);
        if (!$currentData) {
            Log::warning("No hay datos pre-cargados en Redis para actualizar incrementalmente");
            return;
        }
        
        $decodedData = json_decode($currentData, true);
        $data = $decodedData['data'] ?? [];
        $indexes = $decodedData['indexes'] ?? [];
        
        // Procesar cada cambio
        foreach ($changes as $change) {
            $record = $this->formatChangeRecord($change);
            
            // Buscar si el registro ya existe
            $existingIndex = $this->findExistingRecord($data, $record);
            
            if ($existingIndex !== null) {
                // Actualizar registro existente
                $data[$existingIndex] = $record;
            } else {
                // Agregar nuevo registro
                $data[] = $record;
            }
        }
        
        // Recrear índices
        $newIndexes = $this->recreateIndexes($data);
        
        // Actualizar estadísticas
        $newStats = $this->recalculateStats($data);
        
        // Reconstruir estructura completa
        $updatedData = [
            'data' => $data,
            'indexes' => $newIndexes,
            'stats' => $newStats,
            'metadata' => [
                'created_at' => $decodedData['metadata']['created_at'] ?? now()->toISOString(),
                'updated_at' => now()->toISOString(),
                'expires_at' => now()->addSeconds(7200)->toISOString(),
                'version' => '1.0.0',
                'last_incremental_update' => now()->toISOString()
            ]
        ];
        
        // Actualizar Redis atómicamente
        Redis::setex($preloadCacheKey, 7200, json_encode($updatedData));
        
        // Actualizar índices por separado
        $indexKey = $preloadCacheKey . '_indexes';
        Redis::setex($indexKey, 7200, json_encode($newIndexes));
        
        Log::info("Datos actualizados en Redis", [
            'total_records' => count($data),
            'changes_processed' => count($changes)
        ]);
    }
    
    /**
     * Formatear registro de cambio
     */
    private function formatChangeRecord(object $change): array
    {
        return [
            'plaza' => $change->cplaza,
            'tienda' => $change->ctienda,
            'fecha' => $change->fecha,
            'fecha_vta' => $change->fecha_vta,
            'concepto' => $change->concepto,
            'tipo' => $change->tipo_ref,
            'factura' => $change->no_ref,
            'clave' => $change->clave_cl,
            'rfc' => $change->rfc,
            'nombre' => $change->nombre,
            'monto_fa' => ($change->tipo_ref === 'FA' && $change->concepto !== 'DV') ? (float) $change->IMPORTE : 0,
            'monto_dv' => ($change->tipo_ref === 'FA' && $change->concepto === 'DV') ? (float) $change->IMPORTE : 0,
            'monto_cd' => ($change->tipo_ref === 'CD' && $change->concepto !== 'DV') ? (float) $change->IMPORTE : 0,
            'dias_cred' => (int) $change->dias_cred,
            'dias_vencidos' => (int) (Carbon::parse($change->fecha)->diffInDays(Carbon::parse($change->fecha_venc))),
            'change_timestamp' => $change->change_timestamp,
            'change_type' => $change->change_type
        ];
    }
    
    /**
     * Encontrar registro existente
     */
    private function findExistingRecord(array $data, array $record): ?int
    {
        foreach ($data as $index => $existingRecord) {
            if ($existingRecord['plaza'] === $record['plaza'] &&
                $existingRecord['tienda'] === $record['tienda'] &&
                $existingRecord['tipo'] === $record['tipo'] &&
                $existingRecord['factura'] === $record['factura'] &&
                $existingRecord['clave'] === $record['clave']) {
                return $index;
            }
        }
        return null;
    }
    
    /**
     * Recrear índices
     */
    private function recreateIndexes(array $data): array
    {
        $indexes = [
            'plaza' => [],
            'tienda' => [],
            'fecha' => [],
            'nombre' => [],
            'rfc' => [],
            'factura' => [],
            'clave' => []
        ];
        
        foreach ($data as $position => $record) {
            $this->addToIndex($indexes, 'plaza', $record['plaza'], $position);
            $this->addToIndex($indexes, 'tienda', $record['tienda'], $position);
            $this->addToIndex($indexes, 'fecha', $record['fecha'], $position);
            $this->addToIndex($indexes, 'nombre', strtolower($record['nombre']), $position);
            $this->addToIndex($indexes, 'rfc', strtolower($record['rfc']), $position);
            $this->addToIndex($indexes, 'factura', $record['factura'], $position);
            $this->addToIndex($indexes, 'clave', $record['clave'], $position);
        }
        
        return $indexes;
    }
    
    /**
     * Agregar a índice
     */
    private function addToIndex(array &$indexes, string $field, string $value, int $position): void
    {
        if (!isset($indexes[$field][$value])) {
            $indexes[$field][$value] = [];
        }
        $indexes[$field][$value][] = $position;
    }
    
    /**
     * Recalcular estadísticas
     */
    private function recalculateStats(array $data): array
    {
        $stats = [
            'total_records' => count($data),
            'unique_plazas' => 0,
            'unique_tiendas' => 0,
            'total_monto' => 0,
            'period' => $this->getCurrentPeriod()
        ];
        
        if (!empty($data)) {
            $plazas = array_unique(array_column($data, 'plaza'));
            $tiendas = array_unique(array_column($data, 'tienda'));
            
            $stats['unique_plazas'] = count($plazas);
            $stats['unique_tiendas'] = count($tiendas);
            
            foreach ($data as $record) {
                $stats['total_monto'] += ($record['monto_fa'] + $record['monto_dv'] + $record['monto_cd']);
            }
        }
        
        return $stats;
    }
    
    /**
     * Obtener periodo actual
     */
    private function getCurrentPeriod(): array
    {
        $now = Carbon::now();
        return [
            'start' => $now->copy()->subMonth()->startOfMonth()->toDateString(),
            'end' => $now->copy()->subMonth()->endOfMonth()->toDateString(),
            'name' => $now->copy()->subMonth()->format('Y-m')
        ];
    }
    
    /**
     * Obtener cache key de pre-carga
     */
    private function getPreloadCacheKey(): string
    {
        $period = $this->getCurrentPeriod();
        return 'cartera_ultra_preload_' . $period['name'];
    }
    
    /**
     * Notificar a clientes de actualización
     */
    private function notifyClientsOfUpdate(int $changesCount): void
    {
        // Publicar evento de actualización para clientes conectados
        $notification = [
            'type' => 'incremental_update',
            'changes_count' => $changesCount,
            'timestamp' => now()->toISOString(),
            'message' => "Datos actualizados: {$changesCount} cambios"
        ];
        
        // Usar Redis Pub/Sub para notificar en tiempo real
        Redis::publish('cartera_abonos_updates', json_encode($notification));
        
        Log::info("Notificación enviada a clientes", [
            'changes_count' => $changesCount
        ]);
    }
    
    /**
     * El job falló
     */
    public function failed(\Exception $exception): void
    {
        Log::error("Job de actualización incremental falló permanentemente", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}