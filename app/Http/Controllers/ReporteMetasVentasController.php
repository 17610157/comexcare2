<?php

namespace App\Http\Controllers;

use App\Exports\MetasVentasExport;
use App\Helpers\RoleHelper;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReporteMetasVentasController extends Controller
{
    /**
     * Mostrar la vista principal del reporte - VERSIÓN OPTIMIZADA
     */
    public function index(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        if (! $userFilter['allowed']) {
            return redirect()->route('home')->with('error', $userFilter['message'] ?? 'No autorizado');
        }

        // Fechas por defecto (mes actual)
        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));
        $zonaInput = $request->input('zona', '');

        // Vista dinámica según el tipo de visualización
        $vista_tipo = $request->input('vista_tipo', 'card'); // 'card' por defecto, 'tabla' para tabla

        // Listas para filtros (limitadas por el filtro del usuario)
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

        $zonas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('zona')
            ->orderBy('zona')
            ->pluck('zona')
            ->filter()
            ->map(fn ($z) => trim($z))
            ->filter()
            ->values();

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

        // Convertir arrays a strings
        $plaza = is_array($plazaInput) ? implode(',', $plazaInput) : $plazaInput;
        $tienda = is_array($tiendaInput) ? implode(',', $tiendaInput) : $tiendaInput;
        $zona = is_array($zonaInput) ? implode(',', $zonaInput) : $zonaInput;

        // Vista dinámica según el tipo de visualización
        $vista_tipo = $request->input('vista_tipo', 'card');

        // Inicializar variables
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
                'vista_tipo' => $vista_tipo,
            ];

            // Usar ReportService para cache consistente
            $datos = ReportService::getMetasVentasReport($filtros);
            $resultados = $datos['resultados'];
            $estadisticas = $datos['estadisticas'];
            $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);

            // Log de rendimiento
            \Log::info("Reporte Metas Ventas - Vista: {$vista_tipo}, Tiempo carga: {$tiempo_carga}ms, Registros: ".count($resultados));

        } catch (\Exception $e) {
            $error_msg = 'Error en la consulta: '.$e->getMessage();
            \Log::error('Error Reporte Metas: '.$e->getMessage());
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
            'tiempo_carga',
            'plazas',
            'tiendas',
            'zonas'
        ));
    }

    /**
     * Exportar a Excel
     */
    public function export(Request $request)
    {
        $plazaInput = $request->input('plaza', '');
        $tiendaInput = $request->input('tienda', '');
        $zonaInput = $request->input('zona', '');

        $filtros = [
            'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
            'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
            'plaza' => is_array($plazaInput) ? implode(',', $plazaInput) : $plazaInput,
            'tienda' => is_array($tiendaInput) ? implode(',', $tiendaInput) : $tiendaInput,
            'zona' => is_array($zonaInput) ? implode(',', $zonaInput) : $zonaInput,
        ];

        return Excel::download(new MetasVentasExport($filtros),
            'Reporte_Metas_Ventas_'.date('Ymd_His').'.xlsx'
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
            $plazaInput = $request->input('plaza', '');
            $tiendaInput = $request->input('tienda', '');
            $zonaInput = $request->input('zona', '');
            $vista_tipo = $request->input('vista_tipo', 'card');

            $plaza = is_array($plazaInput) ? implode(',', $plazaInput) : $plazaInput;
            $tienda = is_array($tiendaInput) ? implode(',', $tiendaInput) : $tiendaInput;
            $zona = is_array($zonaInput) ? implode(',', $zonaInput) : $zonaInput;

            // Usar misma lógica que el método index
            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'zona' => $zona,
                'vista_tipo' => $vista_tipo,
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

            return $pdf->download('Reporte_Metas_Ventas_'.date('Ymd_His').'.pdf');

        } catch (\Exception $e) {
            \Log::error('Error al generar PDF: '.$e->getMessage());

            return back()->with('error', 'Error al generar PDF: '.$e->getMessage());
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
            if (! empty($periodo) && ! preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                return response()->json([
                    'error' => 'El periodo debe tener formato YYYY-MM (ej: 2024-01)',
                    'resultados' => [],
                ], 400);
            }

            if ($vista_tipo === 'metas_dias') {
                // Consulta para tabla metas_dias
                $sql = 'SELECT m.plaza, m.tienda, m.fecha, m.meta_dia, m.f.dias_mes 
                        FROM metas m 
                        LEFT JOIN metas_dias f ON (m.periodo = f.periodo) 
                        WHERE m.plaza = ? AND m.tienda = ? AND f.periodo = ?';

                $resultados = \Illuminate\Support\Facades\DB::select($sql, [$plaza, $tienda, $periodo]);

                if (empty($resultados)) {
                    $mensaje_informativo = "No se encontraron datos para el período {$periodo} con los filtros especificados";
                }
            } elseif ($vista_tipo === 'metas_mensual') {
                // Consulta para tabla metas_mensual
                $sql = 'SELECT m.plaza, m.tienda, f.periodo, f.descripcion, 
                           SUM(f.valor_dia) as total_valor_dia, 
                           SUM(f.meta_dia) as total_meta_dia,
                           f.fecha 
                        FROM metas_mensual f 
                        WHERE m.plaza = ? AND m.tienda = ? AND f.periodo = ?
                        GROUP BY m.plaza, m.tienda, f.periodo, f.descripcion, f.fecha
                        ORDER BY f.fecha';

                $resultados = \Illuminate\Support\Facades\DB::select($sql, [$plaza, $tienda, $periodo]);

                if (empty($resultados)) {
                    $mensaje_informativo = "No se encontraron datos para el período {$periodo} con los filtros especificados";
                }
            }

            return response()->json([
                'vista_tipo' => $vista_tipo,
                'periodo' => $periodo,
                'resultados' => $resultados,
                'mensaje_informativo' => $mensaje_informativo,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en consulta personalizada: '.$e->getMessage());

            return response()->json([
                'error' => 'Error en la consulta: '.$e->getMessage(),
                'resultados' => [],
            ], 500);
        }
    }
}
