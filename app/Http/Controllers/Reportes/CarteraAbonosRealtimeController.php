<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\CarteraAbonosMaterializedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CarteraAbonosRealtimeController extends Controller
{
    private CarteraAbonosMaterializedService $materializedService;

    public function __construct(CarteraAbonosMaterializedService $materializedService)
    {
        $this->materializedService = $materializedService;
    }

    /**
     * Vista principal (tiempo real)
     */
    public function index()
    {
        return view('reportes.cartera_abonos.index_realtime');
    }

    /**
     * Endpoint de datos en tiempo real (tabla materializada)
     */
    public function data(Request $request)
    {
        $startTime = microtime(true);

        try {
            // Extraer parámetros
            $params = $this->extractParams($request);

            // Validar parámetros
            if (!$this->validateParams($params)) {
                return $this->errorResponse('Parámetros inválidos');
            }

            // Obtener datos de tabla materializada (tiempo real)
            $result = $this->materializedService->getData($params);
            $data = $result['data'] ?? [];

            // Obtener conteo total
            $total = $this->materializedService->getCount($params);

            // Formatear datos
            $formattedData = $this->formatDataForDataTable($data);

            $responseTime = (microtime(true) - $startTime) * 1000;

            Log::info('Cartera Abonos Realtime - Respuesta', [
                'response_time_ms' => round($responseTime, 2),
                'query_time_ms' => round($result['query_time'] ?? 0, 2),
                'records_count' => count($data),
                'total_records' => $total,
                'data_source' => $result['data_source'] ?? 'unknown',
                'last_sync' => $result['last_sync']?->toISOString()
            ]);

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $formattedData,
                'meta' => [
                    'response_time_ms' => round($responseTime, 2),
                    'query_time_ms' => round($result['query_time'] ?? 0, 2),
                    'data_source' => $result['data_source'] ?? 'materialized',
                    'last_sync' => $result['last_sync'] ?? null,
                    'is_fresh_data' => $this->isDataFresh(),
                    'performance_tier' => $this->getPerformanceTier($responseTime)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Cartera Abonos Realtime - Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => $request->all()
            ]);

            return $this->errorResponse('Error interno del servidor');
        }
    }

    /**
     * Endpoint de estadísticas en tiempo real
     */
    public function stats(Request $request)
    {
        try {
            $params = $this->extractParams($request);

            // Estadísticas rápidas desde tabla materializada
            $stats = $this->getRealtimeStats($params);

            // Estadísticas de sincronización
            $syncStats = $this->materializedService->getSyncStats();

            // Health check
            $health = $this->materializedService->healthCheck();

            return response()->json([
                'stats' => $stats,
                'sync' => $syncStats,
                'health' => $health,
                'performance' => [
                    'data_source' => 'materialized',
                    'is_fresh' => $syncStats['is_fresh'] ?? false,
                    'last_sync' => $syncStats['last_sync']?->toISOString(),
                    'pending_changes' => $syncStats['pending_changes'] ?? 0
                ],
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en stats realtime', ['error' => $e->getMessage()]);
            return $this->errorResponse('Error al obtener estadísticas');
        }
    }

    /**
     * Endpoint de health check
     */
    public function health()
    {
        try {
            $health = $this->materializedService->healthCheck();
            
            $statusCode = $health['overall_status'] === 'healthy' ? 200 : 503;

            return response()->json([
                'status' => $health['overall_status'],
                'checks' => $health,
                'timestamp' => now()->toISOString(),
                'version' => '1.0.0'
            ], $statusCode);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Forzar sincronización completa
     */
    public function forceSync(Request $request)
    {
        try {
            $result = $this->materializedService->forceFullSync();

            return response()->json([
                'result' => $result,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error forzando sync', ['error' => $e->getMessage()]);
            return $this->errorResponse('Error al forzar sincronización');
        }
    }

    /**
     * Endpoint de streaming para actualizaciones en tiempo real
     */
    public function stream(Request $request)
    {
        $params = $this->extractParams($request);

        // Headers para Server-Sent Events
        $headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
        ];

        $response = response()->stream(function() use ($params) {
            $lastSyncTime = now();
            $iteration = 0;

            while (true) {
                $iteration++;

                try {
                    // Verificar si hay nuevos datos
                    $syncStats = $this->materializedService->getSyncStats();
                    $currentSyncTime = $syncStats['last_sync'];
                    
                    if ($currentSyncTime && $currentSyncTime->greaterThan($lastSyncTime)) {
                        // Hay nuevos datos, enviar actualización
                        $data = $this->materializedService->getData($params);
                        
                        $this->sendSSEEvent('update', [
                            'data' => $this->formatDataForDataTable($data['data'] ?? []),
                            'sync_time' => $currentSyncTime->toISOString(),
                            'iteration' => $iteration
                        ]);

                        $lastSyncTime = $currentSyncTime;
                    }

                    // Enviar heartbeat cada 30 segundos
                    if ($iteration % 6 === 0) {
                        $this->sendSSEEvent('heartbeat', [
                            'timestamp' => now()->toISOString(),
                            'iteration' => $iteration
                        ]);
                    }

                    // Pequeña pausa
                    sleep(5);

                } catch (\Exception $e) {
                    $this->sendSSEEvent('error', [
                        'error' => $e->getMessage(),
                        'timestamp' => now()->toISOString()
                    ]);
                    break;
                }
            }
        }, 200, $headers);

        return $response;
    }

    /**
     * Obtener estadísticas en tiempo real
     */
    private function getRealtimeStats(array $params): array
    {
        // Query directo a tabla materializada para stats
        $stats = DB::table('cartera_abonos_materialized')
            ->where('sync_status', 'active')
            ->when(!empty($params['start']), function($query) use ($params) {
                $query->where('fecha', '>=', $params['start']);
            })
            ->when(!empty($params['end']), function($query) use ($params) {
                $query->where('fecha', '<=', $params['end']);
            })
            ->when(!empty($params['plaza']), function($query) use ($params) {
                $query->where('plaza', $params['plaza']);
            })
            ->when(!empty($params['tienda']), function($query) use ($params) {
                $query->where('tienda', $params['tienda']);
            })
            ->selectRaw([
            'COUNT(*) as total_abonos',
            'COUNT(DISTINCT plaza) as unique_plazas',
            'COUNT(DISTINCT tienda) as unique_tiendas',
            'COUNT(DISTINCT clave) as unique_clientes',
            'SUM(monto_fa) as total_monto_fa',
            'SUM(monto_dv) as total_monto_dv',
            'SUM(monto_cd) as total_monto_cd',
            'AVG(dias_cred) as avg_dias_cred',
            'MAX(dias_vencidos) as max_dias_vencidos',
            'MIN(fecha) as earliest_date',
            'MAX(fecha) as latest_date'
        ])->first();

        return [
            'total_abonos' => (int) ($stats->total_abonos ?? 0),
            'unique_plazas' => (int) ($stats->unique_plazas ?? 0),
            'unique_tiendas' => (int) ($stats->unique_tiendas ?? 0),
            'unique_clientes' => (int) ($stats->unique_clientes ?? 0),
            'total_monto_fa' => (float) ($stats->total_monto_fa ?? 0),
            'total_monto_dv' => (float) ($stats->total_monto_dv ?? 0),
            'total_monto_cd' => (float) ($stats->total_monto_cd ?? 0),
            'total_general' => (float) (($stats->total_monto_fa ?? 0) + ($stats->total_monto_dv ?? 0) + ($stats->total_monto_cd ?? 0)),
            'avg_dias_cred' => round((float) ($stats->avg_dias_cred ?? 0), 1),
            'max_dias_vencidos' => (int) ($stats->max_dias_vencidos ?? 0),
            'date_range' => [
                'earliest' => $stats->earliest_date,
                'latest' => $stats->latest_date
            ]
        ];
    }

    /**
     * Extraer y normalizar parámetros
     */
    private function extractParams(Request $request): array
    {
        return [
            'start' => $request->input('period_start', Carbon::parse('first day of previous month')->toDateString()),
            'end' => $request->input('period_end', Carbon::parse('last day of previous month')->toDateString()),
            'search' => $request->input('search.value', ''),
            'plaza' => trim(strtoupper($request->input('plaza', ''))),
            'tienda' => trim(strtoupper($request->input('tienda', ''))),
            'offset' => (int) $request->input('start', 0),
            'limit' => (int) $request->input('length', 10)
        ];
    }

    /**
     * Validar parámetros
     */
    private function validateParams(array $params): bool
    {
        // Validar fechas
        if (!strtotime($params['start']) || !strtotime($params['end'])) {
            return false;
        }

        if ($params['start'] > $params['end']) {
            return false;
        }

        // Validar formatos
        if (!empty($params['plaza']) && !preg_match('/^[A-Z0-9]{5}$/', $params['plaza'])) {
            return false;
        }

        if (!empty($params['tienda']) && !preg_match('/^[A-Z0-9]{1,10}$/', $params['tienda'])) {
            return false;
        }

        return true;
    }

    /**
     * Formatear datos para DataTables
     */
    private function formatDataForDataTable(array $data): array
    {
        return array_map(function($row) {
            return [
                'plaza' => $row->plaza ?? '',
                'tienda' => $row->tienda ?? '',
                'fecha' => $row->fecha ?? '',
                'fecha_vta' => $row->fecha_vta ?? '',
                'concepto' => $row->concepto ?? '',
                'tipo' => $row->tipo ?? '',
                'factura' => $row->factura ?? '',
                'clave' => $row->clave ?? '',
                'rfc' => $row->rfc ?? '',
                'nombre' => $row->nombre ?? '',
                'monto_fa' => floatval($row->monto_fa ?? 0),
                'monto_dv' => floatval($row->monto_dv ?? 0),
                'monto_cd' => floatval($row->monto_cd ?? 0),
                'dias_cred' => intval($row->dias_cred ?? 0),
                'dias_vencidos' => intval($row->dias_vencidos ?? 0)
            ];
        }, $data);
    }

    /**
     * Determinar nivel de performance
     */
    private function getPerformanceTier(float $responseTime): string
    {
        if ($responseTime < 100) {
            return 'excellent';
        } elseif ($responseTime < 300) {
            return 'good';
        } elseif ($responseTime < 1000) {
            return 'acceptable';
        } else {
            return 'slow';
        }
    }

    /**
     * Verificar si los datos están frescos
     */
    private function isDataFresh(): bool
    {
        $syncStats = $this->materializedService->getSyncStats();
        return $syncStats['is_fresh'] ?? false;
    }

    /**
     * Enviar evento Server-Sent Events
     */
    private function sendSSEEvent(string $type, array $data): void
    {
        echo "event: {$type}\n";
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }

    /**
     * Respuesta de error estandarizada
     */
    private function errorResponse(string $message, int $status = 400)
    {
        return response()->json([
            'error' => $message,
            'timestamp' => now()->toISOString()
        ], $status);
    }
}