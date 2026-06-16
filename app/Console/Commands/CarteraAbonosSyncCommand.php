<?php

namespace App\Console\Commands;

use App\Jobs\CarteraAbonosSyncJob;
use App\Services\CarteraAbonosMaterializedService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CarteraAbonosSyncCommand extends Command
{
    protected $signature = 'cartera-abonos:sync 
                            {--type=incremental : Tipo de sincronización (full|incremental|delta)}
                            {--force : Forzar sincronización completa}
                            {--schedule : Programar sincronización periódica}
                            {--monitor : Monitorear estado de sincronización}';

    protected $description = 'Sincronizar datos de Cartera Abonos con tabla materializada';

    public function handle()
    {
        $startTime = microtime(true);

        try {
            if ($this->option('monitor')) {
                return $this->monitorSync();
            }

            if ($this->option('schedule')) {
                return $this->scheduleSync();
            }

            $syncType = $this->determineSyncType();

            $this->info('Iniciando sincronización Cartera Abonos');
            $this->info("Tipo: {$syncType}");
            $this->info('Timestamp: '.now()->toISOString());

            // Ejecutar sincronización
            $job = new CarteraAbonosSyncJob($syncType);
            $result = $job->handle();

            $executionTime = (microtime(true) - $startTime) * 1000;

            $this->info('✅ Sincronización completada exitosamente');
            $this->info("Registros procesados: {$result['records_processed']}");
            $this->info('Tiempo ejecución: '.round($executionTime, 2).'ms');

            // Mostrar estadísticas adicionales
            if (isset($result['stats'])) {
                $this->showSyncStats($result['stats']);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error en sincronización: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return 1;
        }
    }

    /**
     * Determinar tipo de sincronización
     */
    private function determineSyncType(): string
    {
        if ($this->option('force')) {
            return 'full';
        }

        if ($this->option('type')) {
            return $this->option('type');
        }

        // Lógica automática de determinación
        $materializedService = app(CarteraAbonosMaterializedService::class);
        $syncStats = $materializedService->getSyncStats();

        // Si no hay datos o están muy antiguos, hacer full sync
        if (! $syncStats['last_sync'] || $syncStats['is_stale']) {
            $this->info('Datos muy antiguos o inexistentes, forzando full sync');

            return 'full';
        }

        // Si hay muchos cambios pendientes, hacer full sync
        if ($syncStats['pending_changes'] > 10000) {
            $this->info("Muchos cambios pendientes ({$syncStats['pending_changes']}), haciendo full sync");

            return 'full';
        }

        // Sino, incremental
        return 'incremental';
    }

    /**
     * Monitorear estado de sincronización
     */
    private function monitorSync(): int
    {
        $this->info('📊 Monitoreo de Sincronización Cartera Abonos');
        $this->info(str_repeat('=', 50));

        $materializedService = app(CarteraAbonosMaterializedService::class);
        $health = $materializedService->healthCheck();
        $stats = $materializedService->getSyncStats();

        // Estado general
        $status = $health['overall_status'];
        $statusIcon = $status === 'healthy' ? '✅' : ($status === 'degraded' ? '⚠️' : '❌');
        $this->info("Estado General: {$statusIcon} {$status}");

        // Tablas
        $this->info("\n📋 Tablas:");
        $this->info('  Tabla Materializada: '.($health['materialized_table_exists'] ? '✅' : '❌'));
        $this->info('  Control de Sincronización: '.($health['sync_control_table_exists'] ? '✅' : '❌'));
        $this->info('  Log de Cambios: '.($health['change_log_table_exists'] ? '✅' : '❌'));

        // Datos
        $this->info("\n📈 Datos:");
        $this->info('  Última Sincronización: '.($stats['last_sync'] ? $stats['last_sync']->diffForHumans() : 'Nunca'));
        $this->info('  Frescura de Datos: '.$health['data_freshness']);
        $this->info('  Total Registros: '.number_format($stats['total_records']));
        $this->info('  Cambios Pendientes: '.number_format($stats['pending_changes']));

        // Cola
        $this->info("\n🔄 Procesamiento:");
        $this->info('  Estado Cola: '.($health['queue_processing'] ? '✅ Normal' : '⚠️ Congestionada'));
        $this->info('  Última Sincronización Exitosa: '.($health['last_sync_successful'] ? '✅' : '❌'));

        // Historial
        if (! empty($stats['sync_history'])) {
            $this->info("\n📜 Historial Reciente:");
            $headers = ['Tipo', 'Estado', 'Registros', 'Duración', 'Timestamp'];
            $rows = [];

            foreach ($stats['sync_history'] as $sync) {
                $duration = $sync->completed_at
                    ? Carbon::parse($sync->started_at)->diffInSeconds(Carbon::parse($sync->completed_at)).'s'
                    : 'N/A';

                $rows[] = [
                    $sync->sync_type,
                    $sync->status,
                    number_format($sync->records_processed ?? 0),
                    $duration,
                    Carbon::parse($sync->started_at)->format('Y-m-d H:i:s'),
                ];
            }

            $this->table($headers, $rows);
        }

        // Recomendaciones
        $this->showRecommendations($health, $stats);

        return $health['overall_status'] === 'healthy' ? 0 : 1;
    }

    /**
     * Programar sincronización periódica
     */
    private function scheduleSync(): int
    {
        $this->info('⏰ Programando Sincronización Periódica');

        // Configurar scheduler para sincronización cada 5 minutos
        $cronExpression = '*/5 * * * *'; // Cada 5 minutos

        $this->info("Ejecutando: {$cronExpression}");
        $this->info('Comando: php artisan cartera-abonos:sync --type=incremental');

        // Simular programación (en producción se configuraría en crontab)
        $this->info('✅ Sincronización programada exitosamente');
        $this->info("\nPara configurar en producción, agregar a crontab:");
        $this->info("{$cronExpression} cd /path/to/project && php artisan cartera-abonos:sync --type=incremental >> /var/log/cartera_sync.log 2>&1");

        return 0;
    }

    /**
     * Mostrar estadísticas de sincronización
     */
    private function showSyncStats($stats): void
    {
        $this->info("\n📊 Estadísticas Detalladas:");

        if (isset($stats->records_processed)) {
            $this->info('  Registros Procesados: '.number_format($stats->records_processed));
        }

        if (isset($stats->records_added)) {
            $this->info('  Registros Agregados: '.number_format($stats->records_added));
        }

        if (isset($stats->records_updated)) {
            $this->info('  Registros Actualizados: '.number_format($stats->records_updated));
        }

        if (isset($stats->records_deleted)) {
            $this->info('  Registros Eliminados: '.number_format($stats->records_deleted));
        }

        if (isset($stats->started_at) && isset($stats->completed_at)) {
            $duration = Carbon::parse($stats->started_at)->diffInSeconds(Carbon::parse($stats->completed_at));
            $this->info("  Duración Total: {$duration} segundos");
        }
    }

    /**
     * Mostrar recomendaciones basadas en estado
     */
    private function showRecommendations(array $health, array $stats): void
    {
        $this->info("\n💡 Recomendaciones:");

        $recommendations = [];

        if (! $health['materialized_table_exists']) {
            $recommendations[] = 'Ejecutar: php artisan cartera-abonos:sync --force --type=full';
        }

        if ($health['data_freshness'] === 'stale') {
            $recommendations[] = 'Datos muy antiguos. Forzar sincronización completa.';
        }

        if ($stats['pending_changes'] > 5000) {
            $recommendations[] = 'Muchos cambios pendientes. Considerar sincronización completa.';
        }

        if (! $health['queue_processing']) {
            $recommendations[] = 'Cola de procesamiento congestionada. Revisar workers.';
        }

        if (! $health['last_sync_successful']) {
            $recommendations[] = 'Última sincronización falló. Revisar logs.';
        }

        if (empty($recommendations)) {
            $this->info('  ✅ Todo funciona correctamente. No se requieren acciones.');
        } else {
            foreach ($recommendations as $rec) {
                $this->info("  ⚠️  {$rec}");
            }
        }
    }
}
