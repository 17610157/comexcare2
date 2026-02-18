<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ReportService;
use App\Exports\MetasVentasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteMetasVentasController extends Controller
{
    /**
     * Mostrar la vista principal del reporte - VERSIÓN OPTIMIZADA
     */
    public function index(Request $request)
    {
        // Fechas por defecto (mes actual)
        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        $zona = $request->input('zona', '');

        // Vista dinámica según el tipo de visualización
        $vista_tipo = $request->input('vista_tipo', 'card'); // 'card' por defecto, 'tabla' para tabla

        $resultados = [];
        $estadisticas = [];
        $error_msg = '';
        $tiempo_carga = 0;

        // Procesar siempre con fechas default
        $inicio_tiempo = microtime(true);

        try {
            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'zona' => $zona,
                'vista_tipo' => $vista_tipo
            ];

            // Usar ReportService para cache consistente
            $datos = ReportService::getMetasVentasReport($filtros);
            $resultados = $datos['resultados'];
            $estadisticas = $datos['estadisticas'];
            $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);

            // Log de rendimiento
            \Log::info("Reporte Metas Ventas - Vista: {$vista_tipo}, Tiempo carga: {$tiempo_carga}ms, Registros: " . count($resultados));

        } catch (\Exception $e) {
            $error_msg = "Error en la consulta: " . $e->getMessage();
            \Log::error("Error Reporte Metas: " . $e->getMessage());
        }

        return view('reportes.metas_ventas.index', compact(
            'fecha_inicio',
            'fecha_fin',
            'plaza',
            'tienda',
            'zona',
            'vista_tipo',
            'resultados',
            'estadisticas',
            'error_msg',
            'tiempo_carga'
        ));
    }

    /**
     * Exportar a Excel
     */
    public function export(Request $request)
    {
        $filtros = [
            'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
            'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
            'plaza' => $request->input('plaza', ''),
            'tienda' => $request->input('tienda', ''),
            'zona' => $request->input('zona', '')
        ];

        return Excel::download(new MetasVentasExport($filtros), 
            'Reporte_Metas_Ventas_' . date('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            // Obtener datos directamente desde la base de datos
            $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
            $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $zona = $request->input('zona', '');
            
            // Usar misma lógica que el método index
            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'zona' => $zona,
                'vista_tipo' => $vista_tipo
            ];
            
            $datos = ReportService::getMetasVentasReport($filtros);
            $resultados = $datos['resultados'];
            $estadisticas = $datos['estadisticas'];
            
            // Generar PDF usando la misma librería
            $pdf = \Barryvdh\DomPDF\PDF::loadView('reportes.metas_ventas_pdf', compact(
                'resultados',
                'fecha_inicio',
                'fecha_fin',
                'plaza',
                'tienda',
                'zona',
                'estadisticas'
            ))->setPaper('a4')->setOrientation('landscape');
            
            return $pdf->download('Reporte_Metas_Ventas_' . date('Ymd_His') . '.pdf');
            
        } catch (\Exception $e) {
            \Log::error("Error al generar PDF: " . $e->getMessage());
            return back()->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * API para obtener ventas acumuladas
     */
    public function getVentaAcumulada(Request $request)
    {
        $fecha = $request->input('fecha', date('Ym01'));
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        
        try {
            $result = ReportService::getVentaAcumulada($fecha, $plaza, $tienda);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Botón de consulta personalizada para metas_dias y metas_mensual
     */
    public function consultarDatosPersonalizados(Request $request)
    {
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        $periodo = $request->input('periodo', ''); // Formato: YYYY-MM
        $vista_tipo = $request->input('vista_tipo', 'metas_dias'); // 'metas_dias' o 'metas_mensual'

        try {
            $resultados = [];
            $mensaje_informativo = '';
            
            // Validar formato del periodo
            if (!empty($periodo) && !preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                return response()->json([
                    'error' => 'El periodo debe tener formato YYYY-MM (ej: 2024-01)',
                    'resultados' => []
                ], 400);
            }
            
            if ($vista_tipo === 'metas_dias') {
                // Consulta para tabla metas_dias
                $sql = "SELECT m.plaza, m.tienda, m.fecha, m.meta_dia, m.f.dias_mes 
                        FROM metas m 
                        LEFT JOIN metas_dias f ON (m.periodo = f.periodo) 
                        WHERE m.plaza = ? AND m.tienda = ? AND f.periodo = ?";
                
                $resultados = \Illuminate\Support\Facades\DB::select($sql, [$plaza, $tienda, $periodo]);
                
                if (empty($resultados)) {
                    $mensaje_informativo = "No se encontraron datos para el período {$periodo} con los filtros especificados";
                }
            } 
            elseif ($vista_tipo === 'metas_mensual') {
                // Consulta para tabla metas_mensual
                $sql = "SELECT m.plaza, m.tienda, f.periodo, f.descripcion, 
                           SUM(f.valor_dia) as total_valor_dia, 
                           SUM(f.meta_dia) as total_meta_dia,
                           f.fecha 
                        FROM metas_mensual f 
                        WHERE m.plaza = ? AND m.tienda = ? AND f.periodo = ?
                        GROUP BY m.plaza, m.tienda, f.periodo, f.descripcion, f.fecha
                        ORDER BY f.fecha";
                
                $resultados = \Illuminate\Support\Facades\DB::select($sql, [$plaza, $tienda, $periodo]);
                
                if (empty($resultados)) {
                    $mensaje_informativo = "No se encontraron datos para el período {$periodo} con los filtros especificados";
                }
            }
            
            return response()->json([
                'vista_tipo' => $vista_tipo,
                'periodo' => $periodo,
                'resultados' => $resultados,
                'mensaje_informativo' => $mensaje_informativo
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error en consulta personalizada: " . $e->getMessage());
            return response()->json([
                'error' => 'Error en la consulta: ' . $e->getMessage(),
                'resultados' => []
            ], 500);
        }
    }
}