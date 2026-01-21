<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\ReportService;
use App\Exports\VendedoresExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteVendedoresController extends Controller
{
    /**
     * Mostrar la vista principal del reporte
     */
    public function index(Request $request)
    {
        // Fechas por defecto
        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        $vendedor = $request->input('vendedor', '');

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
                    'vendedor' => $vendedor
                ];

                $resultados_collection = ReportService::getVendedoresReport($filtros);
                $estadisticas = ReportService::calcularEstadisticasVendedores($resultados_collection);

                // Convertir a array con índices numéricos para compatibilidad con la vista
                $resultados = $resultados_collection->map(function ($item, $index) {
                    return array_merge(['no' => $index + 1], $item);
                })->toArray();

                $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);

            } catch (\Exception $e) {
                $error_msg = "Error en la consulta: " . $e->getMessage();
            }
        }

        return view('reportes.vendedores.index', compact(
            'fecha_inicio',
            'fecha_fin',
            'plaza',
            'tienda',
            'vendedor',
            'resultados',
            'error_msg',
            'tiempo_carga'
        ) + [
            'total_ventas' => $estadisticas['total_ventas'] ?? 0,
            'total_devoluciones' => $estadisticas['total_devoluciones'] ?? 0,
            'total_neto' => $estadisticas['total_neto'] ?? 0
        ]);
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
            'vendedor' => $request->input('vendedor', '')
        ];

        return Excel::download(new VendedoresExport($filtros), 
            'Reporte_Vendedores_' . date('Ymd_His') . '.xlsx'
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
            'vendedor' => $request->input('vendedor', '')
        ];

        return Excel::download(new VendedoresExport($filtros), 
            'Reporte_Vendedores_' . date('Ymd_His') . '.csv', 
            \Maatwebsite\Excel\Excel::CSV,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="Reporte_Vendedores_' . date('Ymd_His') . '.csv"',
            ]
        );
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
            $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $vendedor = $request->input('vendedor', '');

            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'vendedor' => $vendedor
            ];

            $resultados_collection = ReportService::getVendedoresReport($filtros);
            $estadisticas = ReportService::calcularEstadisticasVendedores($resultados_collection);

            $datos = $resultados_collection->map(function ($item, $index) {
                return array_merge(['no' => $index + 1], $item);
            })->toArray();

            $data = [
                'datos' => $datos,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'vendedor' => $vendedor,
                'total_ventas' => $estadisticas['total_ventas'],
                'total_devoluciones' => $estadisticas['total_devoluciones'],
                'total_neto' => $estadisticas['total_neto'],
                'total_registros' => $estadisticas['total_registros'],
                'fecha_reporte' => date('d/m/Y H:i:s')
            ];

            $pdf = Pdf::loadView('reportes.vendedores.pdf', $data);

            return $pdf->download('Reporte_Vendedores_' . date('Ymd_His') . '.pdf');

        } catch (\Exception $e) {
            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }
}