<?php

namespace App\Http\Controllers;

use App\Models\Meta;
use Illuminate\Http\Request;
use App\Exports\MetasVentasExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteMetasVentasController extends Controller
{
    /**
     * Mostrar el reporte de metas vs ventas
     */
    public function index(Request $request)
    {
        // Filtros por defecto
        $fecha_inicio = $request->get('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->get('fecha_fin', date('Y-m-d'));
        $plaza = $request->get('plaza', '');
        $tienda = $request->get('tienda', '');
        $zona = $request->get('zona', '');
        
        $filtros = [
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'plaza' => $plaza,
            'tienda' => $tienda,
            'zona' => $zona,
        ];
        
        // Obtener resultados
        $resultados = Meta::obtenerReporteMetasVentas($filtros);
        
        // Calcular totales
        $total_meta = 0;
        $total_vendido = 0;
        
        foreach ($resultados as $item) {
            $total_meta += $item->meta_dia ?? 0;
            $total_vendido += $item->total_vendido ?? 0;
        }
        
        // Calcular porcentaje promedio
        $porcentaje_promedio = ($total_meta > 0) ? ($total_vendido / $total_meta) * 100 : 0;
        
        return view('reportes.metas_ventas.index', [
            'resultados' => $resultados,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'plaza' => $plaza,
            'tienda' => $tienda,
            'zona' => $zona,
            'total_meta' => $total_meta,
            'total_vendido' => $total_vendido,
            'porcentaje_promedio' => $porcentaje_promedio,
        ]);
    }
    
    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $filtros = [
            'fecha_inicio' => $request->get('fecha_inicio'),
            'fecha_fin' => $request->get('fecha_fin'),
            'plaza' => $request->get('plaza'),
            'tienda' => $request->get('tienda'),
            'zona' => $request->get('zona'),
        ];
        
        return Excel::download(new MetasVentasExport($filtros), 'reporte_metas_ventas_' . date('Ymd_His') . '.xlsx');
    }
    
    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        $filtros = [
            'fecha_inicio' => $request->get('fecha_inicio'),
            'fecha_fin' => $request->get('fecha_fin'),
            'plaza' => $request->get('plaza'),
            'tienda' => $request->get('tienda'),
            'zona' => $request->get('zona'),
        ];
        
        $resultados = Meta::obtenerReporteMetasVentas($filtros);
        
        // Calcular totales para el PDF
        $total_meta = 0;
        $total_vendido = 0;
        
        foreach ($resultados as $item) {
            $total_meta += $item->meta_dia ?? 0;
            $total_vendido += $item->total_vendido ?? 0;
        }
        
        $porcentaje_promedio = ($total_meta > 0) ? ($total_vendido / $total_meta) * 100 : 0;
        
        $pdf = PDF::loadView('reportes.metas_ventas.pdf', [
            'resultados' => $resultados,
            'fecha_inicio' => $filtros['fecha_inicio'],
            'fecha_fin' => $filtros['fecha_fin'],
            'plaza' => $filtros['plaza'],
            'tienda' => $filtros['tienda'],
            'zona' => $filtros['zona'],
            'total_meta' => $total_meta,
            'total_vendido' => $total_vendido,
            'porcentaje_promedio' => $porcentaje_promedio,
            'fecha_reporte' => date('d/m/Y H:i:s'),
        ]);
        
        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download('reporte_metas_ventas_' . date('Ymd_His') . '.pdf');
    }
    
    /**
     * Exportar a CSV
     */
    public function exportCsv(Request $request)
    {
        $filtros = [
            'fecha_inicio' => $request->get('fecha_inicio'),
            'fecha_fin' => $request->get('fecha_fin'),
            'plaza' => $request->get('plaza'),
            'tienda' => $request->get('tienda'),
            'zona' => $request->get('zona'),
        ];
        
        return Excel::download(new MetasVentasExport($filtros), 'reporte_metas_ventas_' . date('Ymd_His') . '.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}