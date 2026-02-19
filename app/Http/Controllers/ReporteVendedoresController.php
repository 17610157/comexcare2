<?php

namespace App\Http\Controllers;

use App\Exports\VendedoresExport;
use App\Helpers\RoleHelper;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReporteVendedoresController extends Controller
{
    /**
     * Mostrar la vista principal del reporte
     */
    public function index(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        if (! $userFilter['allowed']) {
            return redirect()->route('home')->with('error', $userFilter['message'] ?? 'No autorizado');
        }

        // Fechas por defecto
        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
        $vendedor = $request->input('vendedor', '');

        // Obtener listas para filtros (limitadas por el filtro del usuario)
        $plazasQuery = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza');

        $tiendasQuery = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('clave_tienda')
            ->orderBy('clave_tienda');

        // Aplicar filtro de plazas asignadas al usuario
        $plazasAsignadas = $userFilter['plazas_asignadas'] ?? [];
        $tiendasAsignadas = $userFilter['tiendas_asignadas'] ?? [];

        if (! empty($plazasAsignadas)) {
            $plazasQuery->whereIn('id_plaza', $plazasAsignadas);
            $tiendasQuery->whereIn('id_plaza', $plazasAsignadas);
        }

        if (! empty($tiendasAsignadas)) {
            $tiendasQuery->whereIn('clave_tienda', $tiendasAsignadas);
        }

        $plazas = $plazasQuery->pluck('id_plaza')->filter()->values();
        $tiendas = $tiendasQuery->pluck('clave_tienda')->filter()->values();

        // Procesar valores del request, validando contra asignaciones del usuario
        $plazaInput = $request->input('plaza', '');
        $tiendaInput = $request->input('tienda', '');

        // Si tiene plazas/tiendas asignadas, validar que los valores estén permitidos
        if (! empty($plazasAsignadas)) {
            if (empty($plazaInput)) {
                $plazaInput = $plazasAsignadas;
            } else {
                $plazaValues = is_array($plazaInput) ? $plazaInput : explode(',', $plazaInput);
                $plazaValues = array_filter($plazaValues, fn ($p) => in_array($p, $plazasAsignadas));
                $plazaInput = ! empty($plazaValues) ? array_values($plazaValues) : $plazasAsignadas;
            }
        }

        if (! empty($tiendasAsignadas)) {
            if (empty($tiendaInput)) {
                $tiendaInput = $tiendasAsignadas;
            } else {
                $tiendaValues = is_array($tiendaInput) ? $tiendaInput : explode(',', $tiendaInput);
                $tiendaValues = array_filter($tiendaValues, fn ($t) => in_array($t, $tiendasAsignadas));
                $tiendaInput = ! empty($tiendaValues) ? array_values($tiendaValues) : $tiendasAsignadas;
            }
        }

        // Convertir arrays a strings para compatibilidad
        $plaza = is_array($plazaInput) ? implode(',', $plazaInput) : $plazaInput;
        $tienda = is_array($tiendaInput) ? implode(',', $tiendaInput) : $tiendaInput;

        // Inicializar variables
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
                    'vendedor' => $vendedor,
                ];

                $resultados_collection = ReportService::getVendedoresReport($filtros);
                $estadisticas = ReportService::calcularEstadisticasVendedores($resultados_collection);

                // Convertir a array con índices numéricos para compatibilidad con la vista
                $resultados = $resultados_collection->map(function ($item, $index) {
                    return array_merge(['no' => $index + 1], $item);
                })->toArray();

                $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);

            } catch (\Exception $e) {
                $error_msg = 'Error en la consulta: '.$e->getMessage();
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
            'tiempo_carga',
            'plazas',
            'tiendas'
        ) + [
            'total_ventas' => $estadisticas['total_ventas'] ?? 0,
            'total_devoluciones' => $estadisticas['total_devoluciones'] ?? 0,
            'total_neto' => $estadisticas['total_neto'] ?? 0,
        ]);
    }

    /**
     * Exportar a Excel
     */
    public function export(Request $request)
    {
        $plazaInput = $request->input('plaza', '');
        $tiendaInput = $request->input('tienda', '');

        $filtros = [
            'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
            'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
            'plaza' => is_array($plazaInput) ? implode(',', $plazaInput) : $plazaInput,
            'tienda' => is_array($tiendaInput) ? implode(',', $tiendaInput) : $tiendaInput,
            'vendedor' => $request->input('vendedor', ''),
        ];

        return Excel::download(new VendedoresExport($filtros),
            'Reporte_Vendedores_'.date('Ymd_His').'.xlsx'
        );
    }

    /**
     * Exportar a CSV
     */
    public function exportCsv(Request $request)
    {
        $plazaInput = $request->input('plaza', '');
        $tiendaInput = $request->input('tienda', '');

        $filtros = [
            'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
            'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
            'plaza' => is_array($plazaInput) ? implode(',', $plazaInput) : $plazaInput,
            'tienda' => is_array($tiendaInput) ? implode(',', $tiendaInput) : $tiendaInput,
            'vendedor' => $request->input('vendedor', ''),
        ];

        return Excel::download(new VendedoresExport($filtros),
            'Reporte_Vendedores_'.date('Ymd_His').'.csv',
            \Maatwebsite\Excel\Excel::CSV,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="Reporte_Vendedores_'.date('Ymd_His').'.csv"',
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
                'vendedor' => $vendedor,
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
                'fecha_reporte' => date('d/m/Y H:i:s'),
            ];

            $pdf = Pdf::loadView('reportes.vendedores.pdf', $data);

            return $pdf->download('Reporte_Vendedores_'.date('Ymd_His').'.pdf');

        } catch (\Exception $e) {
            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al generar PDF: '.$e->getMessage());
        }
    }
}
