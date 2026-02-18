<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CarteraAbonosSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1800; // 30 minutos máximo
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    private string $syncType;
    private array $params;
    private string $batchId;

    /**
     * Crear nuevo job de sincronización
     */
    public function __construct(string $syncType = 'incremental', array $params = [])
    {
        $this->syncType = $syncType;
        $this->params = $params;
        $this->batchId = $this->generateBatchId();
        
        // Configurar cola específica para sincronización
        $this->onQueue('sync');
    }

    /**
     * Ejecutar el job
     */
    public function handle(): void
    {
        $startTime = microtime(true);
        
        try {
            Log::info("Iniciando sincronización Cartera Abonos", [
                'batch_id' => $this->batchId,
                'sync_type' => $this->syncType,
                'params' => $this->params
            ]);

            // Determinar tipo de sincronización
            $result = match($this->syncType) {
                'full' => $this->performFullSync(),
                'incremental' => $this->performIncrementalSync(),
                'delta' => $this->performDeltaSync(),
                default => $this->performIncrementalSync()
            };

            $executionTime = (microtime(true) - $startTime) * 1000;

            // Actualizar caché después de sincronización exitosa
            $this->updateCacheAfterSync();

            Log::info("Sincronización Cartera Abonos completada", [
                'batch_id' => $this->batchId,
                'sync_type' => $this->syncType,
                'records_processed' => $result['records_processed'],
                'execution_time_ms' => round($executionTime, 2),
                'memory_usage' => memory_get_peak_usage(true)
            ]);

            // Notificar completion (si es necesario)
            $this->notifySyncCompletion($result);

        } catch (\Exception $e) {
            $this->handleSyncError($e, $startTime);
            throw $e;
        }
    }

    /**
     * Sincronización completa (full refresh)
     */
    private function performFullSync(): array
    {
        Log::info("Ejecutando sincronización completa", ['batch_id' => $this->batchId]);

        // Ejecutar procedimiento almacenado de full sync
        $result = DB::selectOne("SELECT sync_cartera_abonos_full() as records_count");
        
        // Obtener estadísticas de la sincronización
        $stats = $this->getSyncStats($this->batchId);

        return [
            'records_processed' => $result->records_count ?? 0,
            'sync_type' => 'full',
            'stats' => $stats
        ];
    }

    /**
     * Sincronización incremental
     */
    private function performIncrementalSync(): array
    {
        Log::info("Ejecutando sincronización incremental", ['batch_id' => $this->batchId]);

        // Verificar si hay cambios pendientes
        $pendingChanges = $this->getPendingChangesCount();
        
        if ($pendingChanges == 0) {
            Log::info("No hay cambios pendientes", ['batch_id' => $this->batchId]);
            return [
                'records_processed' => 0,
                'sync_type' => 'incremental',
                'skipped' => true
            ];
        }

        // Ejecutar sincronización incremental
        $result = DB::selectOne("SELECT sync_cartera_abonos_incremental() as records_count");
        
        // Obtener estadísticas
        $stats = $this->getSyncStats($this->batchId);

        return [
            'records_processed' => $result->records_count ?? 0,
            'sync_type' => 'incremental',
            'pending_changes' => $pendingChanges,
            'stats' => $stats
        ];
    }

    /**
     * Sincronización delta (solo cambios específicos)
     */
    private function performDeltaSync(): array
    {
        Log::info("Ejecutando sincronización delta", ['batch_id' => $this->batchId]);

        $processedRecords = 0;
        $chunkSize = 1000;

        // Procesar cambios en chunks
        do {
            $changes = $this->getPendingChanges($chunkSize);
            
            if (empty($changes)) {
                break;
            }

            // Procesar cada cambio
            foreach ($changes as $change) {
                $this->processChange($change);
                $processedRecords++;
            }

            // Pequeña pausa para no sobrecargar
            usleep(10000); // 10ms

        } while (count($changes) === $chunkSize);

        return [
            'records_processed' => $processedRecords,
            'sync_type' => 'delta'
        ];
    }

    /**
     * Procesar cambio individual
     */
    private function processChange(object $change): void
    {
        try {
            match($change->action) {
                'INSERT' => $this->processInsert($change),
                'UPDATE' => $this->processUpdate($change),
                'DELETE' => $this->processDelete($change),
                default => Log::warning("Acción desconocida", ['action' => $change->action])
            };

            // Marcar como procesado
            DB::table('cartera_abonos_change_log')
                ->where('id', $change->id)
                ->update([
                    'processed' => true,
                    'processed_at' => now()
                ]);

        } catch (\Exception $e) {
            Log::error("Error procesando cambio", [
                'change_id' => $change->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Procesar inserción
     */
    private function processInsert(object $change): void
    {
        // Obtener datos completos de la fuente
        $sourceData = $this->getSourceData($change->source_id);
        
        if ($sourceData) {
            // Insertar en tabla materializada
            DB::table('cartera_abonos_materialized')->insert([
                'plaza' => $sourceData->cplaza,
                'tienda' => $sourceData->ctienda,
                'fecha' => $sourceData->fecha,
                'concepto' => $sourceData->concepto,
                'tipo' => $sourceData->tipo_ref,
                'factura' => $sourceData->no_ref,
                'clave' => $sourceData->clave_cl,
                'monto_fa' => $this->calculateMontoFA($sourceData),
                'monto_dv' => $this->calculateMontoDV($sourceData),
                'monto_cd' => $this->calculateMontoCD($sourceData),
                'source_id' => $change->source_id,
                'sync_status' => 'active',
                'sync_batch' => $this->batchId,
                'last_updated' => now()
            ]);
        }
    }

    /**
     * Procesar actualización
     */
    private function processUpdate(object $change): void
    {
        $sourceData = $this->getSourceData($change->source_id);
        
        if ($sourceData) {
            DB::table('cartera_abonos_materialized')
                ->where('source_id', $change->source_id)
                ->update([
                    'plaza' => $sourceData->cplaza,
                    'tienda' => $sourceData->ctienda,
                    'fecha' => $sourceData->fecha,
                    'concepto' => $sourceData->concepto,
                    'tipo' => $sourceData->tipo_ref,
                    'factura' => $sourceData->no_ref,
                    'clave' => $sourceData->clave_cl,
                    'monto_fa' => $this->calculateMontoFA($sourceData),
                    'monto_dv' => $this->calculateMontoDV($sourceData),
                    'monto_cd' => $this->calculateMontoCD($sourceData),
                    'sync_status' => 'active',
                    'sync_batch' => $this->batchId,
                    'last_updated' => now()
                ]);
        }
    }

    /**
     * Procesar eliminación
     */
    private function processDelete(object $change): void
    {
        DB::table('cartera_abonos_materialized')
            ->where('source_id', $change->source_id)
            ->update([
                'sync_status' => 'deleted',
                'sync_batch' => $this->batchId,
                'last_updated' => now()
            ]);
    }

    /**
     * Obtener datos de fuente
     */
    private function getSourceData(int $sourceId): ?object
    {
        return DB::table('cobranza as c')
            ->leftJoin('cliente_depurado as cl', function($join) {
                $join->on('c.ctienda', '=', 'cl.ctienda')
                     ->on('c.cplaza', '=', 'cl.cplaza')
                     ->on('c.clave_cl', '=', 'cl.clie_clave');
            })
            ->where('c.id', $sourceId)
            ->where('c.cargo_ab', 'A')
            ->where('c.estado', 'S')
            ->where('c.cborrado', '<>', '1')
            ->select(
                'c.cplaza', 'c.ctienda', 'c.fecha', 'c.concepto',
                'c.tipo_ref', 'c.no_ref', 'c.clave_cl', 'c.IMPORTE',
                'cl.clie_rfc', 'cl.clie_nombr', 'cl.clie_credi'
            )
            ->first();
    }

    /**
     * Obtener cambios pendientes
     */
    private function getPendingChanges(int $limit = 1000): array
    {
        return DB::table('cartera_abonos_change_log')
            ->where('processed', false)
            ->orderBy('changed_at')
            ->limit($limit)
            ->get()
            ->toArray();
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
     * Obtener estadísticas de sincronización
     */
    private function getSyncStats(string $batchId): ?object
    {
        return DB::table('cartera_abonos_sync_control')
            ->where('sync_batch', $batchId)
            ->first();
    }

    /**
     * Actualizar caché después de sincronización
     */
    private function updateCacheAfterSync(): void
    {
        // Invalidar cachés relacionadas
        $patterns = [
            'cartera_abonos_*',
            'reporte_cartera_abonos_*'
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Precargar datos comunes
        $this->preloadCommonData();
    }

    /**
     * Precargar datos comunes en caché
     */
    private function preloadCommonData(): void
    {
        // Precargar estadísticas generales
        $stats = DB::table('cartera_abonos_materialized')
            ->selectRaw('
                COUNT(*) as total_records,
                COUNT(DISTINCT plaza) as unique_plazas,
                COUNT(DISTINCT tienda) as unique_tiendas,
                SUM(monto_fa + monto_dv + monto_cd) as total_monto
            ')
            ->where('sync_status', 'active')
            ->first();

        Cache::put('cartera_abonos_stats', $stats, 300); // 5 minutos
    }

    /**
     * Notificar completion de sincronización
     */
    private function notifySyncCompletion(array $result): void
    {
        // Enviar notificación a sistemas monitoreo
        Cache::put('cartera_abonos_last_sync', [
            'completed_at' => now()->toISOString(),
            'batch_id' => $this->batchId,
            'sync_type' => $this->syncType,
            'records_processed' => $result['records_processed']
        ], 3600); // 1 hora
    }

    /**
     * Manejar errores de sincronización
     */
    private function handleSyncError(\Exception $e, float $startTime): void
    {
        $executionTime = (microtime(true) - $startTime) * 1000;

        Log::error("Error en sincronización Cartera Abonos", [
            'batch_id' => $this->batchId,
            'sync_type' => $this->syncType,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'execution_time_ms' => round($executionTime, 2),
            'attempt' => $this->attempts()
        ]);

        // Actualizar estado en control de sincronización
        DB::table('cartera_abonos_sync_control')
            ->where('sync_batch', $this->batchId)
            ->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now()
            ]);
    }

    /**
     * Generar ID de batch único
     */
    private function generateBatchId(): string
    {
        return strtoupper($this->syncType) . '_' . 
               Carbon::now()->format('Ymd_His') . '_' . 
               uniqid();
    }

    /**
     * Calcular monto FA
     */
    private function calculateMontoFA(object $sourceData): float
    {
        return ($sourceData->tipo_ref === 'FA' && $sourceData->concepto !== 'DV') 
            ? (float) $sourceData->IMPORTE 
            : 0;
    }

    /**
     * Calcular monto DV
     */
    private function calculateMontoDV(object $sourceData): float
    {
        return ($sourceData->tipo_ref === 'FA' && $sourceData->concepto === 'DV') 
            ? (float) $sourceData->IMPORTE 
            : 0;
    }

    /**
     * Calcular monto CD
     */
    private function calculateMontoCD(object $sourceData): float
    {
        return ($sourceData->tipo_ref === 'CD' && $sourceData->concepto !== 'DV') 
            ? (float) $sourceData->IMPORTE 
            : 0;
    }

    /**
     * El job falló
     */
    public function failed(\Exception $exception): void
    {
        Log::error("Job de sincronización Cartera Abonos falló permanentemente", [
            'batch_id' => $this->batchId,
            'sync_type' => $this->syncType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}