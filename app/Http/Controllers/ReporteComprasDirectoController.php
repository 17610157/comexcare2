<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ComprasDirectoExport;
use Carbon\Carbon;

class ReporteComprasDirectoController extends Controller
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
        $proveedor = $request->input('proveedor', '');

        $resultados = [];
        $error_msg = '';
        $tiempo_carga = 0;

        // Solo procesar si hay fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $inicio_tiempo = microtime(true);

            try {
                $sql = "
                SELECT
                    c.cplaza,
                    c.ctienda,
                    c.tipo_doc,
                    c.no_referen,
                    c.tipo_doc_a,
                    c.no_fact_pr,
                    c.clave_pro,
                    por.nombre,
                    c.cuenta,
                    c.f_emision,
                    ''''||p.clave_art AS clave_art,
                    pr.descripcio,
                    p.cantidad,
                    p.precio_uni,
                    pr.k_agrupa,
                    pr.k_familia,
                    pr.k_subfam,
                    p.cantidad * p.precio_uni as total
                FROM compras c
                JOIN partcomp p ON c.ctienda=p.ctienda AND c.cplaza=p.cplaza AND c.tipo_doc=p.tipo_doc AND c.no_referen=p.no_referen
                JOIN proveed por ON por.clave_pro = c.clave_pro AND c.ctienda=por.ctienda AND c.cplaza=por.cplaza
                JOIN grupos pr ON p.clave_art=pr.clave 
                WHERE c.f_emision BETWEEN ? AND ?
                ";

                $params = [str_replace('-', '', $fecha_inicio), str_replace('-', '', $fecha_fin)];

                // Aplicar filtros
                if (!empty($plaza)) {
                    $sql .= " AND c.cplaza = ?";
                    $params[] = $plaza;
                }
                if (!empty($tienda)) {
                    $sql .= " AND c.ctienda = ?";
                    $params[] = $tienda;
                }
                if (!empty($proveedor)) {
                    $sql .= " AND c.clave_pro = ?";
                    $params[] = $proveedor;
                }

                $sql .= " ORDER BY c.cplaza, c.ctienda, c.f_emision";

                $resultados_raw = DB::select($sql, $params);

                // Debug: Log para verificar datos
                Log::info('SQL Query: ' . $sql);
                Log::info('Parameters: ' . json_encode($params));
                Log::info('Resultados encontrados: ' . count($resultados_raw));

                // Convertir a array con índices numéricos para compatibilidad con la vista
                $resultados = [];
                foreach ($resultados_raw as $index => $item) {
                    $resultados[] = array_merge(['no' => $index + 1], (array)$item);
                }

                // Calcular estadísticas simples
                $total_compras = array_sum(array_column($resultados, 'total'));
                $total_cantidad = array_sum(array_column($resultados, 'cantidad'));
                $total_proveedores = count(array_unique(array_column($resultados, 'clave_pro')));

                $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);

                $estadisticas = [
                    'total_compras' => $total_compras,
                    'total_cantidad' => $total_cantidad,
                    'total_proveedores' => $total_proveedores,
                    'total_registros' => count($resultados),
                    'promedio_precio' => $total_cantidad > 0 ? $total_compras / $total_cantidad : 0
                ];

            } catch (\Exception $e) {
                $error_msg = "Error en la consulta: " . $e->getMessage();
                Log::error('Error en ReporteComprasDirectoController: ' . $e->getMessage());
            }
        }

        return view('reportes.compras.directo.index', compact(
            'fecha_inicio',
            'fecha_fin',
            'plaza',
            'tienda',
            'proveedor',
            'resultados',
            'error_msg',
            'tiempo_carga'
        ) + [
            'total_compras' => $estadisticas['total_compras'] ?? 0,
            'total_cantidad' => $estadisticas['total_cantidad'] ?? 0,
            'total_proveedores' => $estadisticas['total_proveedores'] ?? 0,
            'total_registros' => $estadisticas['total_registros'] ?? 0,
            'promedio_precio' => $estadisticas['promedio_precio'] ?? 0
        ]);
    }

    /**
     * Exportar a Excel o CSV
     */
    public function export(Request $request)
    {
        try {
            $filtros = [
                'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
                'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
                'plaza' => $request->input('plaza', ''),
                'tienda' => $request->input('tienda', ''),
                'proveedor' => $request->input('proveedor', '')
            ];

            $format = $request->input('format', 'xlsx');
            $filename = 'Reporte_Compras_Directo_' . date('Ymd_His');

            if ($format === 'csv') {
                return Excel::download(new ComprasDirectoExport($filtros), 
                    $filename . '.csv', 
                    \Maatwebsite\Excel\Excel::CSV,
                    [
                        'Content-Type' => 'text/csv',
                        'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
                    ]
                );
            } else {
                return Excel::download(new ComprasDirectoExport($filtros), 
                    $filename . '.xlsx'
                );
            }
            
        } catch (\Exception $e) {
            Log::error('Error en export: ' . $e->getMessage());
            return redirect()->route('reportes.compras-directo', $request->all())
                ->with('error', 'Error al exportar: ' . $e->getMessage());
        }
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
            $proveedor = $request->input('proveedor', '');

            $filtros = [
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'proveedor' => $proveedor
            ];

            // Usar la misma consulta del index
            $fecha_inicio = str_replace('-', '', $filtros['fecha_inicio']);
            $fecha_fin = str_replace('-', '', $filtros['fecha_fin']);
            $plaza = $filtros['plaza'];
            $tienda = $filtros['tienda'];
            $proveedor = $filtros['proveedor'];

            $sql = "
                SELECT
                    c.cplaza,
                    c.ctienda,
                    c.tipo_doc,
                    c.no_referen,
                    c.tipo_doc_a,
                    c.no_fact_pr,
                    c.clave_pro,
                    por.nombre,
                    c.cuenta,
                    c.f_emision,
                    ''''||p.clave_art AS clave_art,
                    pr.descripcio,
                    p.cantidad,
                    p.precio_uni,
                    pr.k_agrupa,
                    pr.k_familia,
                    pr.k_subfam,
                    p.cantidad * p.precio_uni as total
                FROM compras c
                JOIN partcomp p ON c.ctienda=p.ctienda AND c.cplaza=p.cplaza AND c.tipo_doc=p.tipo_doc AND c.no_referen=p.no_referen
                JOIN proveed por ON por.clave_pro = c.clave_pro AND c.ctienda=por.ctienda AND c.cplaza=por.cplaza
                JOIN grupos pr ON p.clave_art=pr.clave 
                WHERE c.f_emision BETWEEN ? AND ?
            ";

            $params = [$fecha_inicio, $fecha_fin];

            if (!empty($plaza)) {
                $sql .= " AND c.cplaza = ?";
                $params[] = $plaza;
            }
            if (!empty($tienda)) {
                $sql .= " AND c.ctienda = ?";
                    $params[] = $tienda;
            }
            if (!empty($proveedor)) {
                $sql .= " AND c.clave_pro = ?";
                    $params[] = $proveedor;
            }

            $sql .= " ORDER BY c.cplaza, c.ctienda, c.f_emision";

            $resultados = DB::select($sql, $params);

            // Preparar datos para PDF
            $datos_con_numeracion = [];
            foreach ($resultados as $index => $item) {
                $datos_con_numeracion[] = array_merge(['no' => $index + 1], (array)$item);
            }

            // Calcular estadísticas
            $total_compras = array_sum(array_column($datos_con_numeracion, 'total'));
            $total_cantidad = array_sum(array_column($datos_con_numeracion, 'cantidad'));

            $data = [
                'datos' => $datos_con_numeracion,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'proveedor' => $proveedor,
                'total_compras' => $total_compras,
                'total_cantidad' => $total_cantidad,
                'total_registros' => count($datos_con_numeracion),
                'promedio_precio' => $total_cantidad > 0 ? $total_compras / $total_cantidad : 0,
                'fecha_reporte' => date('d/m/Y H:i:s')
            ];

            $pdf = Pdf::loadView('reportes.compras.directo.pdf', $data);

            return $pdf->download('Reporte_Compras_Directo_' . date('Ymd_His') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Error al generar PDF en ReporteComprasDirectoController: ' . $e->getMessage());
            return redirect()->route('reportes.compras.directo', $request->all())
                ->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    /**
     * API para obtener datos en formato JSON
     */
    public function api(Request $request)
    {
        try {
            $filtros = [
                'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
                'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
                'plaza' => $request->input('plaza', ''),
                'tienda' => $request->input('tienda', ''),
                'proveedor' => $request->input('proveedor', '')
            ];

            // Usar la misma consulta del index
            $fecha_inicio = str_replace('-', '', $filtros['fecha_inicio']);
            $fecha_fin = str_replace('-', '', $filtros['fecha_fin']);
            $plaza = $filtros['plaza'];
            $tienda = $filtros['tienda'];
            $proveedor = $filtros['proveedor'];

            $sql = "
                SELECT
                    c.cplaza,
                    c.ctienda,
                    c.tipo_doc,
                    c.no_referen,
                    c.tipo_doc_a,
                    c.no_fact_pr,
                    c.clave_pro,
                    por.nombre,
                    c.cuenta,
                    c.f_emision,
                    ''''||p.clave_art AS clave_art,
                    pr.descripcio,
                    p.cantidad,
                    p.precio_uni,
                    pr.k_agrupa,
                    pr.k_familia,
                    pr.k_subfam,
                    p.cantidad * p.precio_uni as total
                FROM compras c
                JOIN partcomp p ON c.ctienda=p.ctienda AND c.cplaza=p.cplaza AND c.tipo_doc=p.tipo_doc AND c.no_referen=p.no_referen
                JOIN proveed por ON por.clave_pro = c.clave_pro AND c.ctienda=por.ctienda AND c.cplaza=por.cplaza
                JOIN grupos pr ON p.clave_art=pr.clave 
                WHERE c.f_emision BETWEEN ? AND ?
            ";

            $params = [$fecha_inicio, $fecha_fin];

            if (!empty($plaza)) {
                $sql .= " AND c.cplaza = ?";
                $params[] = $plaza;
            }
            if (!empty($tienda)) {
                $sql .= " AND c.ctienda = ?";
                    $params[] = $tienda;
            }
            if (!empty($proveedor)) {
                $sql .= " AND c.clave_pro = ?";
                    $params[] = $proveedor;
            }

            $sql .= " ORDER BY c.cplaza, c.ctienda, c.f_emision";

            $resultados = DB::select($sql, $params);

            // Convertir a array simple
            $resultados_array = [];
            foreach ($resultados as $index => $item) {
                $resultados_array[] = [
                    'no' => $index + 1,
                    'cplaza' => $item->cplaza,
                    'ctienda' => $item->ctienda,
                    'tipo_doc' => $item->tipo_doc,
                    'no_referen' => $item->no_referen,
                    'tipo_doc_a' => $item->tipo_doc_a,
                    'no_fact_pr' => $item->no_fact_pr,
                    'clave_pro' => $item->clave_pro,
                    'nombre' => $item->nombre,
                    'cuenta' => $item->cuenta,
                    'f_emision' => $item->f_emision,
                    'CLAVE' => $item->CLAVE,
                    'descripcio' => $item->descripcio,
                    'cantidad' => floatval($item->cantidad),
                    'precio_uni' => floatval($item->precio_uni),
                    'k_agrupa' => $item->k_agrupa,
                    'k_familia' => $item->k_familia,
                    'k_subfam' => $item->k_subfam,
                    'total' => floatval($item->total)
                ];
            }

            // Calcular estadísticas
            $total_compras = array_sum(array_column($resultados_array, 'total'));
            $total_cantidad = array_sum(array_column($resultados_array, 'cantidad'));

            return response()->json([
                'success' => true,
                'data' => $resultados_array,
                'estadisticas' => [
                    'total_compras' => $total_compras,
                    'total_cantidad' => $total_cantidad,
                    'total_registros' => count($resultados_array),
                    'promedio_precio' => $total_cantidad > 0 ? $total_compras / $total_cantidad : 0
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en API ReporteComprasDirectoController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Contar registros para validación antes de exportar
     */
    private function contarRegistros($filtros)
    {
        $fecha_inicio = str_replace('-', '', $filtros['fecha_inicio']);
        $fecha_fin = str_replace('-', '', $filtros['fecha_fin']);
        $plaza = $filtros['plaza'] ?? '';
        $tienda = $filtros['tienda'] ?? '';
        $proveedor = $filtros['proveedor'] ?? '';

        $sql = "SELECT COUNT(*) as total
                FROM compras c
                JOIN partcomp p ON c.ctienda=p.ctienda AND c.cplaza=p.cplaza AND c.tipo_doc=p.tipo_doc AND c.no_referen=p.no_referen
                JOIN proveed por ON por.clave_pro = c.clave_pro AND c.ctienda=por.ctienda AND c.cplaza=por.cplaza
                JOIN grupos pr ON p.clave_art=pr.clave 
                WHERE c.f_emision BETWEEN ? AND ?";

        $params = [$fecha_inicio, $fecha_fin];

        if (!empty($plaza)) {
            $sql .= " AND c.cplaza = ?";
            $params[] = $plaza;
        }
        if (!empty($tienda)) {
            $sql .= " AND c.ctienda = ?";
            $params[] = $tienda;
        }
        if (!empty($proveedor)) {
            $sql .= " AND c.clave_pro = ?";
            $params[] = $proveedor;
        }

        $result = DB::select($sql, $params);
        return $result[0]->total ?? 0;
    }

    /**
     * Data para DataTable usando caché
     */
    public function data(Request $request)
    {
        Log::info('ComprasDirecto data request', ['url' => $request->fullUrl(), 'params' => $request->all()]);
        
        $start = Carbon::parse('first day of previous month')->toDateString();
        $end   = Carbon::parse('last day of previous month')->toDateString();
        if ($request->filled('period_start')) {
            $start = $request->input('period_start');
        }
        if ($request->filled('period_end')) {
            $end = $request->input('period_end');
        }

        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $search = $request->input('search.value', '');
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;

        try {
            $query = DB::table('compras_directo_cache');

            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('cplaza', 'ILIKE', '%'.$search.'%')
                      ->orWhere('ctienda', 'ILIKE', '%'.$search.'%')
                      ->orWhere('clave_pro', 'ILIKE', '%'.$search.'%')
                      ->orWhere('nombre_proveedor', 'ILIKE', '%'.$search.'%')
                      ->orWhere('clave_art', 'ILIKE', '%'.$search.'%')
                      ->orWhere('descripcion', 'ILIKE', '%'.$search.'%');
                });
            }

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $query->where('cplaza', trim($request->input('plaza')));
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $query->where('ctienda', trim($request->input('tienda')));
            }

            if ($request->filled('proveedor') && $request->input('proveedor') !== '') {
                $query->where('clave_pro', trim($request->input('proveedor')));
            }

            $query->whereBetween('f_emision', [$start, $end]);

            $total = $query->count();

            $data = $query->orderBy('f_emision')->orderBy('ctienda')->orderBy('no_referen')
                ->offset($offsetInt)
                ->limit($lengthInt)
                ->get();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int)$total,
                'recordsFiltered' => (int)$total,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('ComprasDirecto data error: ' . $e->getMessage());
            return response()->json(['draw' => (int)$request->input('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Sync datos a caché
     */
    public function sync(Request $request)
    {
        $request->validate([
            'type' => 'required|in:lastMonth,lastDays,day,period,full'
        ]);

        $type = $request->input('type');
        $append = $request->boolean('append', false);

        try {
            if (!$append) {
                DB::statement('TRUNCATE TABLE compras_directo_cache RESTART IDENTITY CASCADE');
            }

            $sql = "INSERT INTO compras_directo_cache (
                        cplaza, ctienda, tipo_doc, no_referen, tipo_doc_a, no_fact_pr,
                        clave_pro, nombre_proveedor, cuenta, f_emision, clave_art, descripcion,
                        cantidad, precio_uni, k_agrupa, k_familia, k_subfam, total, updated_at
                    )
                    SELECT
                        c.cplaza,
                        c.ctienda,
                        c.tipo_doc,
                        c.no_referen,
                        c.tipo_doc_a,
                        c.no_fact_pr,
                        c.clave_pro,
                        por.nombre AS nombre_proveedor,
                        c.cuenta,
                        c.f_emision,
                        '''' || p.clave_art AS clave_art,
                        pr.descripcio AS descripcion,
                        p.cantidad,
                        p.precio_uni,
                        pr.k_agrupa,
                        pr.k_familia,
                        pr.k_subfam,
                        p.cantidad * p.precio_uni AS total,
                        NOW() AS updated_at
                    FROM compras c
                    JOIN partcomp p ON c.ctienda = p.ctienda AND c.cplaza = p.cplaza AND c.tipo_doc = p.tipo_doc AND c.no_referen = p.no_referen
                    JOIN proveed por ON por.clave_pro = c.clave_pro AND c.ctienda = por.ctienda AND c.cplaza = por.cplaza
                    JOIN grupos pr ON p.clave_art = pr.clave
                    WHERE c.f_emision >= :start AND c.f_emision <= :end";

            switch ($type) {
                case 'lastMonth':
                    $start = Carbon::parse('first day of previous month')->toDateString();
                    $end = Carbon::parse('last day of previous month')->toDateString();
                    break;
                case 'lastDays':
                    $days = (int) $request->input('lastDays', 30);
                    $end = date('Y-m-d');
                    $start = date('Y-m-d', strtotime("-{$days} days"));
                    break;
                case 'day':
                    $start = $request->input('day');
                    $end = $request->input('day');
                    break;
                case 'period':
                    $start = $request->input('periodStart');
                    $end = $request->input('periodEnd');
                    break;
                case 'full':
                    $start = '2000-01-01';
                    $end = date('Y-m-d');
                    break;
                default:
                    $start = Carbon::parse('first day of previous month')->toDateString();
                    $end = Carbon::parse('last day of previous month')->toDateString();
            }

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('compras_directo_cache')->count();

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. Registros: {$count} (Período: {$start} - {$end})"
            ]);
        } catch (\Exception $e) {
            Log::error('ComprasDirecto sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}