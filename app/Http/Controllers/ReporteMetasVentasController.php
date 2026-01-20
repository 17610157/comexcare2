<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReporteMetasVentas;
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

        $resultados = [];
        $estadisticas = [];
        $error_msg = '';
        $tiempo_carga = 0;

        // Solo procesar si hay fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $inicio_tiempo = microtime(true);

            try {
                $filtros = [
                    'fecha_inicio' => $fecha_inicio,
                    'fecha_fin' => $fecha_fin,
                    'plaza' => $plaza,
                    'tienda' => $tienda,
                    'zona' => $zona
                ];

                // USAR VERSIÓN OPTIMIZADA
                $resultados = ReporteMetasVentas::obtenerReporte($filtros);
                // O si prefieres la versión SQL pura optimizada:
                // $resultados = ReporteMetasVentas::obtenerReporteOptimizadoSQL($filtros);
                
                $estadisticas = ReporteMetasVentas::obtenerEstadisticas($resultados);
                $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);
                
                // Log de rendimiento
                \Log::info("Reporte Metas Ventas - Tiempo carga: {$tiempo_carga}ms, Registros: " . count($resultados));
                
            } catch (\Exception $e) {
                $error_msg = "Error en la consulta: " . $e->getMessage();
                \Log::error("Error Reporte Metas: " . $e->getMessage());
            }
        }

        return view('reportes.metas_ventas.index', compact(
            'fecha_inicio', 
            'fecha_fin', 
            'plaza', 
            'tienda', 
            'zona',
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
     * Exportar a CSV
     */
    public function exportCsv(Request $request)
    {
        $filtros = [
            'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
            'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
            'plaza' => $request->input('plaza', ''),
            'tienda' => $request->input('tienda', ''),
            'zona' => $request->input('zona', '')
        ];

        return Excel::download(new MetasVentasExport($filtros), 
            'Reporte_Metas_Ventas_' . date('Ymd_His') . '.csv', 
            \Maatwebsite\Excel\Excel::CSV,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="Reporte_Metas_Ventas_' . date('Ymd_His') . '.csv"',
            ]
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

            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'zona' => $zona
            ];

            // Usar versión optimizada
            $resultados = ReporteMetasVentas::obtenerReporte($filtros);
            $estadisticas = ReporteMetasVentas::obtenerEstadisticas($resultados);

            $data = [
                'resultados' => $resultados,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'zona' => $zona,
                'estadisticas' => $estadisticas,
                'fecha_reporte' => date('d/m/Y H:i:s')
            ];
            
            $pdf = Pdf::loadView('reportes.metas_ventas.pdf', $data);
            
            return $pdf->download('Reporte_Metas_Ventas_' . date('Ymd_His') . '.pdf');
            
        } catch (\Exception $e) {
            // Si hay error, redirigir con mensaje
            return redirect()->route('reportes.metas-ventas', $request->all())
                ->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * API para obtener venta acumulada hasta una fecha específica (OPTIMIZADA)
     */
    public function getVentaAcumulada(Request $request)
    {
        $fecha = $request->input('fecha', date('Y-m-d'));
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        
        // Obtener el primer día del mes
        $primer_dia_mes = date('Y-m-01', strtotime($fecha));
        
        $sql = "
            SELECT 
                cplaza,
                tienda,
                SUM(
                    (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                    (COALESCE(vtacred, 0) - COALESCE(descred, 0))
                ) AS venta_acumulada_mes
            FROM xcorte
            WHERE fecha BETWEEN ? AND ?
        ";
        
        $params = [$primer_dia_mes, $fecha];
        
        if (!empty($plaza)) {
            $sql .= " AND cplaza = ?";
            $params[] = $plaza;
        }
        
        if (!empty($tienda)) {
            $sql .= " AND tienda = ?";
            $params[] = $tienda;
        }
        
        $sql .= " GROUP BY cplaza, tienda";
        
        $resultados = DB::select($sql, $params);
        
        return response()->json([
            'success' => true,
            'fecha' => $fecha,
            'primer_dia_mes' => $primer_dia_mes,
            'data' => $resultados,
            'total_acumulado' => array_sum(array_column($resultados, 'venta_acumulada_mes'))
        ]);
    }
}