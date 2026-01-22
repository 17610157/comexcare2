<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ReportService;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MetasMatricialExport;

class ReporteMetasMatricialController extends Controller
{
    /**
     * Mostrar reporte metas matricial
     */
    public function index(Request $request)
    {
        // Sin verificación de permisos - versión básica
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

        try {
            $inicio_tiempo = microtime(true);
            $datos = ReportService::getMetasMatricialReport($filtros);
            $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);

            return view('reportes.metas_matricial.index', compact(
                'fecha_inicio',
                'fecha_fin',
                'plaza',
                'tienda',
                'zona',
                'datos',
                'tiempo_carga'
            ));

        } catch (\Exception $e) {
            Log::error('Error en reporte metas matricial: ' . $e->getMessage());
            return back()->with('error', 'Error al generar reporte: ' . $e->getMessage());
        }
    }

    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        // Sin verificación de permisos - versión básica
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'plaza', 'tienda', 'zona']);
        $filtros['fecha_inicio'] = $filtros['fecha_inicio'] ?? date('Y-m-01');
        $filtros['fecha_fin'] = $filtros['fecha_fin'] ?? date('Y-m-d');

        return Excel::download(
            new MetasMatricialExport($filtros),
            'Metas_Matricial_' . date('Ymd_His') . '.xlsx'
        );
    }
}