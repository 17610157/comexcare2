<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use App\Services\CarteraAbonosCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CarteraAbonosOptimizedController extends Controller
{
    private CarteraAbonosCacheService $cacheService;
    
    public function __construct(CarteraAbonosCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }
    
    /**
     * Vista principal (sin cambios)
     */
    public function index()
    {
        return view('reportes.cartera_abonos.index');
    }
    
    /**
     * Endpoint de datos optimizado para tiempo real
     */
    public function data(Request $request)
    {
        $startTime = microtime(true);
        
        try {
            // Parámetros de entrada
            $params = $this->extractParams($request);
            
            // Validación rápida
            if (!$this->validateParams($params)) {
                return $this->errorResponse('Parámetros inválidos');
            }
            
            // Obtener datos cacheados
            $cachedData = $this->cacheService->getCachedData($params);
            $data = $cachedData['data'] ?? [];
            
            // Obtener conteo total cacheado
            $total = $this->cacheService->getCachedCount($params);
            
            // Formatear datos para DataTables
            $formattedData = $this->formatDataForDataTable($data);
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            Log::info('Cartera Abonos - Respuesta optimizada', [
                'response_time_ms' => round($responseTime, 2),
                'query_time_ms' => round($cachedData['query_time'] ?? 0, 2),
                'results_count' => count($data),
                'total_records' => $total,
                'cache_hit' => isset($cachedData['cache_key']),
                'params' => $params
            ]);
            
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $formattedData,
                'performance' => [
                    'response_time_ms' => round($responseTime, 2),
                    'query_time_ms' => round($cachedData['query_time'] ?? 0, 2),
                    'cache_hit' => isset($cachedData['cache_key'])
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cartera Abonos - Error en endpoint optimizado', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => $request->all()
            ]);
            
            return $this->errorResponse('Error interno del servidor');
        }
    }
    
    /**
     * Endpoint streaming para datasets grandes
     */
    public function dataStream(Request $request)
    {
        $params = $this->extractParams($request);
        
        // Headers para streaming
        $headers = [
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Connection' => 'keep-alive',
        ];
        
        $response = response()->stream(function() use ($params) {
            // Enviar chunk inicial
            $this->streamChunk(['status' => 'started', 'timestamp' => now()->toISOString()]);
            
            try {
                // Obtener datos en chunks
                $chunkSize = 1000;
                $offset = 0;
                $hasMore = true;
                
                while ($hasMore) {
                    $chunkParams = array_merge($params, [
                        'offset' => $offset,
                        'limit' => $chunkSize
                    ]);
                    
                    $chunkData = $this->cacheService->getCachedData($chunkParams);
                    $data = $chunkData['data'] ?? [];
                    
                    if (empty($data)) {
                        $hasMore = false;
                    } else {
                        // Enviar chunk de datos
                        $this->streamChunk([
                            'type' => 'data',
                            'offset' => $offset,
                            'count' => count($data),
                            'data' => $this->formatDataForDataTable($data)
                        ]);
                        
                        $offset += $chunkSize;
                        
                        // Pequeña pausa para no sobrecargar
                        usleep(10000); // 10ms
                    }
                }
                
                // Enviar chunk final
                $this->streamChunk(['status' => 'completed', 'timestamp' => now()->toISOString()]);
                
            } catch (\Exception $e) {
                $this->streamChunk([
                    'status' => 'error',
                    'error' => $e->getMessage(),
                    'timestamp' => now()->toISOString()
                ]);
            }
        }, 200, $headers);
        
        return $response;
    }
    
    /**
     * Endpoint de estadísticas en tiempo real
     */
    public function stats(Request $request)
    {
        $params = $this->extractParams($request);
        
        try {
            // Query de estadísticas optimizado
            $sql = "
            WITH abonos_stats AS (
                SELECT 
                    COUNT(*) as total_abonos,
                    SUM(CASE WHEN tipo_ref = 'FA' AND concepto <> 'DV' THEN IMPORTE ELSE 0 END) as total_monto_fa,
                    SUM(CASE WHEN tipo_ref = 'FA' AND concepto = 'DV' THEN IMPORTE ELSE 0 END) as total_monto_dv,
                    SUM(CASE WHEN tipo_ref = 'CD' AND concepto <> 'DV' THEN IMPORTE ELSE 0 END) as total_monto_cd,
                    AVG(COALESCE(cl.clie_credi, 0)) as avg_dias_cred,
                    COUNT(DISTINCT c.cplaza) as unique_plazas,
                    COUNT(DISTINCT c.ctienda) as unique_tiendas,
                    COUNT(DISTINCT c.clave_cl) as unique_clientes
                FROM cobranza c
                LEFT JOIN cliente_depurado cl ON (
                    c.ctienda = cl.ctienda 
                    AND c.cplaza = cl.cplaza 
                    AND c.clave_cl = cl.clie_clave
                )
                WHERE c.cargo_ab = 'A' 
                AND c.estado = 'S' 
                AND c.cborrado <> '1'
                AND c.fecha BETWEEN :start AND :end
                AND (:plaza = '' OR c.cplaza = :plaza)
                AND (:tienda = '' OR c.ctienda = :tienda)
            )
            SELECT 
                total_abonos,
                total_monto_fa,
                total_monto_dv,
                total_monto_cd,
                total_monto_fa + total_monto_dv + total_monto_cd as total_general,
                avg_dias_cred,
                unique_plazas,
                unique_tiendas,
                unique_clientes,
                ROUND((total_monto_fa + total_monto_dv + total_monto_cd) / NULLIF(total_abonos, 0), 2) as avg_monto_abono
            FROM abonos_stats";
            
            $bindings = [
                'start' => $params['start'],
                'end' => $params['end'],
                'plaza' => $params['plaza'],
                'tienda' => $params['tienda']
            ];
            
            $stats = DB::selectOne($sql, $bindings);
            
            // Obtener estadísticas de caché
            $cacheStats = $this->cacheService->getCacheStats();
            
            return response()->json([
                'stats' => $stats,
                'cache' => $cacheStats,
                'params' => $params,
                'timestamp' => now()->toISOString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error en stats de Cartera Abonos', ['error' => $e->getMessage()]);
            return $this->errorResponse('Error al obtener estadísticas');
        }
    }
    
    /**
     * Invalidar caché (para llamadas después de actualizaciones)
     */
    public function invalidateCache(Request $request)
    {
        $this->cacheService->invalidateCache();
        
        return response()->json([
            'message' => 'Caché invalidado exitosamente',
            'timestamp' => now()->toISOString()
        ]);
    }
    
    /**
     * Exportaciones optimadas (reutilizar lógica existente)
     */
    public function pdf(Request $request)
    {
        // Reutilizar lógica del controller original pero con datos cacheados
        return app(CarteraAbonosController::class)->pdf($request);
    }
    
    public function exportExcel(Request $request)
    {
        return app(CarteraAbonosController::class)->exportExcel($request);
    }
    
    public function exportCsv(Request $request)
    {
        return app(CarteraAbonosController::class)->exportCsv($request);
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
     * Validar parámetros de entrada
     */
    private function validateParams(array $params): bool
    {
        // Validar fechas
        if (!strtotime($params['start']) || !strtotime($params['end'])) {
            return false;
        }
        
        // Validar que start <= end
        if ($params['start'] > $params['end']) {
            return false;
        }
        
        // Validar formato de plaza (si se proporciona)
        if (!empty($params['plaza']) && !preg_match('/^[A-Z0-9]{5}$/', $params['plaza'])) {
            return false;
        }
        
        // Validar formato de tienda (si se proporciona)
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
        $formatted = [];
        
        foreach ($data as $row) {
            $formatted[] = [
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
        }
        
        return $formatted;
    }
    
    /**
     * Enviar chunk para streaming
     */
    private function streamChunk(array $data): void
    {
        echo json_encode($data) . "\n";
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