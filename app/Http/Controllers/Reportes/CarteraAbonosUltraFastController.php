<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\CarteraAbonosUltraFastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CarteraAbonosUltraFastController extends Controller
{
    private CarteraAbonosUltraFastService $ultraFastService;

    public function __construct(CarteraAbonosUltraFastService $ultraFastService)
    {
        $this->ultraFastService = $ultraFastService;
    }

    /**
     * Vista principal (sin carga automática)
     */
    public function index()
    {
        return view('reportes.cartera_abonos.index_ultra_fast');
    }

    /**
     * Endpoint de pre-carga de datos (única llamada)
     */
    public function preload(Request $request)
    {
        $startTime = microtime(true);

        try {
            // Obtener datos pre-cargados (desde Redis, sin queries a BD)
            $data = $this->ultraFastService->getPreloadedData();
            
            $responseTime = (microtime(true) - $startTime) * 1000;

            Log::info('Pre-carga ultra-fast completada', [
                'response_time_ms' => round($responseTime, 2),
                'records_count' => count($data['data'] ?? []),
                'data_source' => 'redis_preload',
                'memory_usage' => memory_get_usage(true)
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'meta' => [
                    'response_time_ms' => round($responseTime, 2),
                    'data_source' => 'redis_preload',
                    'cache_hit' => true,
                    'records_count' => count($data['data'] ?? []),
                    'period' => $data['stats']['period'] ?? null,
                    'expires_at' => $data['metadata']['expires_at'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en pre-carga ultra-fast', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al cargar datos pre-cargados',
                'meta' => [
                    'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
                    'data_source' => 'error'
                ]
            ], 500);
        }
    }

    /**
     * Forzar pre-carga de datos (admin)
     */
    public function forcePreload(Request $request)
    {
        try {
            $result = $this->ultraFastService->forcePreload();

            Log::info('Pre-carga forzada completada', [
                'result' => $result
            ]);

            return response()->json([
                'status' => 'success',
                'result' => $result,
                'message' => 'Pre-carga forzada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error forzando pre-carga', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al forzar pre-carga: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar estado del sistema
     */
    public function status(Request $request)
    {
        try {
            $stats = $this->ultraFastService->getSystemStats();

            return response()->json([
                'status' => 'success',
                'stats' => $stats,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estado del sistema', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener estado del sistema'
            ], 500);
        }
    }

    /**
     * Actualización incremental (background)
     */
    public function incrementalUpdate(Request $request)
    {
        try {
            $result = $this->ultraFastService->incrementalUpdate();

            return response()->json([
                'status' => 'success',
                'result' => $result,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('Error en actualización incremental', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error en actualización incremental'
            ], 500);
        }
    }

    /**
     * Endpoint de búsqueda (cliente-side - solo para compatibilidad)
     */
    public function search(Request $request)
    {
        // Este endpoint no se usa realmente - toda la lógica es cliente-side
        // Se mantiene por compatibilidad con DataTables
        
        return response()->json([
            'status' => 'client_side_only',
            'message' => 'Use client-side filtering with preloaded data',
            'meta' => [
                'data_source' => 'client_side',
                'cache_hit' => true
            ]
        ]);
    }

    /**
     * Exportaciones (usando datos pre-cargados)
     */
    public function exportData(Request $request)
    {
        try {
            // Obtener datos pre-cargados
            $preloadedData = $this->ultraFastService->getPreloadedData();
            $data = $preloadedData['data'] ?? [];

            // Aplicar filtros cliente-side si se proporcionan
            if ($request->has('filters')) {
                $data = $this->applyClientSideFilters($data, $request->get('filters'));
            }

            return response()->json([
                'status' => 'success',
                'data' => $data,
                'meta' => [
                    'records_count' => count($data),
                    'data_source' => 'redis_preload',
                    'export_timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en exportación de datos', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al exportar datos'
            ], 500);
        }
    }

    /**
     * Health check del sistema ultra-fast
     */
    public function health(Request $request)
    {
        try {
            $stats = $this->ultraFastService->getSystemStats();
            
            $isHealthy = $stats['has_preloaded_data'] && 
                        !empty($stats['cache_keys']) && 
                        $stats['concurrent_users'] < 500;

            return response()->json([
                'status' => $isHealthy ? 'healthy' : 'degraded',
                'checks' => [
                    'has_preloaded_data' => $stats['has_preloaded_data'],
                    'cache_keys_count' => count($stats['cache_keys']),
                    'concurrent_users' => $stats['concurrent_users'],
                    'redis_memory' => $stats['redis_memory']
                ],
                'stats' => $stats,
                'timestamp' => now()->toISOString()
            ], $isHealthy ? 200 : 503);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 503);
        }
    }

    /**
     * Aplicar filtros cliente-side (para exportaciones)
     */
    private function applyClientSideFilters(array $data, array $filters): array
    {
        return array_filter($data, function($record) use ($filters) {
            // Filtro de plaza
            if (!empty($filters['plaza']) && 
                strtolower($record['plaza']) !== strtolower($filters['plaza'])) {
                return false;
            }

            // Filtro de tienda
            if (!empty($filters['tienda']) && 
                strtolower($record['tienda']) !== strtolower($filters['tienda'])) {
                return false;
            }

            // Filtro de fechas
            if (!empty($filters['start']) && $record['fecha'] < $filters['start']) {
                return false;
            }

            if (!empty($filters['end']) && $record['fecha'] > $filters['end']) {
                return false;
            }

            // Filtro de búsqueda general
            if (!empty($filters['search'])) {
                $search = strtolower($filters['search']);
                $searchable = [
                    strtolower($record['plaza'] ?? ''),
                    strtolower($record['tienda'] ?? ''),
                    strtolower($record['nombre'] ?? ''),
                    strtolower($record['rfc'] ?? ''),
                    strtolower($record['factura'] ?? ''),
                    strtolower($record['clave'] ?? '')
                ];

                $found = false;
                foreach ($searchable as $field) {
                    if (strpos($field, $search) !== false) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    return false;
                }
            }

            return true;
        });
    }
}