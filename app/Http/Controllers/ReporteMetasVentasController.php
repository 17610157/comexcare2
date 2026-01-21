<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ReporteMetasVentas;
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

                // Usar ReportService para cache consistente
                $datos = ReportService::getMetasVentasReport($filtros);
                $resultados = $datos['resultados'];
                $estadisticas = $datos['estadisticas'];
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

            // Usar ReportService para cache consistente
            $datos = ReportService::getMetasVentasReport($filtros);
            $resultados = $datos['resultados'];
            $estadisticas = $datos['estadisticas'];

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
     * API para obtener venta acumulada hasta una fecha específica (OPTIMIZADA CON CACHE)
     */
    public function getVentaAcumulada(Request $request)
    {
        $fecha = $request->input('fecha', date('Y-m-d'));
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');

        try {
            $resultados = ReportService::getVentaAcumulada($fecha, $plaza, $tienda);
            return response()->json($resultados);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener venta acumulada: ' . $e->getMessage()
            ], 500);
        }
    }
}