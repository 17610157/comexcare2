<?php

namespace App\Http\Controllers;

use App\Exports\VendedoresExport;
use App\Helpers\RoleHelper;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

        $startDefault = Carbon::parse('first day of previous month')->toDateString();
        $endDefault = Carbon::parse('last day of previous month')->toDateString();

        $listas = RoleHelper::getListasParaFiltros();

        $plazas = $listas['plazas'];
        $tiendas = $listas['tiendas'];

        return view('reportes.vendedores.index', compact('plazas', 'tiendas', 'startDefault', 'endDefault'));
    }

    /**
     * Data para DataTable
     */
    public function data(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        if (! $userFilter['allowed']) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        Log::info('Vendedores data request', ['url' => $request->fullUrl(), 'params' => $request->all(), 'filter' => $userFilter]);

        $start = Carbon::parse('first day of previous month')->toDateString();
        $end = Carbon::parse('last day of previous month')->toDateString();
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

        $tiendasPermitidas = RoleHelper::getTiendasAcceso();
        $plazasPermitidas = $userFilter['plazas_asignadas'] ?? [];
        $accesoTodasTiendas = $userFilter['acceso_todas_tiendas'] ?? false;

        try {
            $filtros = [
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'plaza' => '',
                'tienda' => '',
                'vendedor' => '',
            ];

            if (! empty($plazasPermitidas) && ! $accesoTodasTiendas) {
                $filtros['plaza'] = implode(',', $plazasPermitidas);
            }

            if (! empty($tiendasPermitidas) && ! $accesoTodasTiendas) {
                $filtros['tienda'] = implode(',', $tiendasPermitidas);
            }

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $plazaFilter = $request->input('plaza');
                if (is_array($plazaFilter) && count($plazaFilter) > 0) {
                    $filtros['plaza'] = implode(',', $plazaFilter);
                } elseif (! is_array($plazaFilter)) {
                    $filtros['plaza'] = trim($plazaFilter);
                }
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $tiendaFilter = $request->input('tienda');
                if (is_array($tiendaFilter) && count($tiendaFilter) > 0) {
                    $filtros['tienda'] = implode(',', $tiendaFilter);
                } elseif (! is_array($tiendaFilter)) {
                    $filtros['tienda'] = trim($tiendaFilter);
                }
            }

            if ($request->filled('vendedor') && $request->input('vendedor') !== '') {
                $filtros['vendedor'] = trim($request->input('vendedor'));
            }

            $resultados = ReportService::getVendedoresReport($filtros);

            if (! empty($search)) {
                $resultados = $resultados->filter(function ($item) use ($search) {
                    $searchLower = strtolower($search);

                    return str_contains(strtolower($item['tienda_vendedor'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['vendedor_dia'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['plaza_ajustada'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['ctienda'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['vend_clave'] ?? ''), $searchLower)
                        || str_contains(strtolower($item['fecha'] ?? ''), $searchLower);
                });
            }

            $total = $resultados->count();

            $data = $resultados->slice($offsetInt, $lengthInt)->map(function ($item, $index) use ($offsetInt) {
                return [
                    'no' => $offsetInt + $index + 1,
                    'tienda_vendedor' => $item['tienda_vendedor'] ?? '',
                    'vendedor_dia' => $item['vendedor_dia'] ?? '',
                    'plaza_ajustada' => $item['plaza_ajustada'] ?? '',
                    'ctienda' => $item['ctienda'] ?? '',
                    'vend_clave' => $item['vend_clave'] ?? '',
                    'fecha' => $item['fecha'] ?? '',
                    'venta_total' => $item['venta_total'] ?? 0,
                    'devolucion' => $item['devolucion'] ?? 0,
                    'venta_neta' => $item['venta_neta'] ?? 0,
                ];
            })->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('Vendedores data error: '.$e->getMessage());

            return response()->json(['draw' => (int) $request->input('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Exportar a Excel
     */
    public function export(Request $request)
    {
        try {
            $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
            $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $vendedor = $request->input('vendedor', '');

            $filtros = [
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'plaza' => is_array($plaza) ? implode(',', $plaza) : $plaza,
                'tienda' => is_array($tienda) ? implode(',', $tienda) : $tienda,
                'vendedor' => $vendedor,
            ];

            return Excel::download(new VendedoresExport($filtros),
                'Reporte_Vendedores_'.date('Ymd_His').'.xlsx'
            );

        } catch (\Exception $e) {
            Log::error('Error en export: '.$e->getMessage());

            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }

    /**
     * Exportar a CSV
     */
    public function exportCsv(Request $request)
    {
        try {
            $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
            $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $vendedor = $request->input('vendedor', '');

            $filtros = [
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'plaza' => is_array($plaza) ? implode(',', $plaza) : $plaza,
                'tienda' => is_array($tienda) ? implode(',', $tienda) : $tienda,
                'vendedor' => $vendedor,
            ];

            $resultados = ReportService::getVendedoresReport($filtros);

            $filename = 'Reporte_Vendedores_'.date('Ymd_His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($resultados) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'Tienda-Vendedor', 'Vendedor-Día', 'Plaza Ajustada', 'Tienda', 'Vendedor', 'Fecha', 'Venta Total', 'Devolución', 'Venta Neta',
                ]);

                foreach ($resultados as $row) {
                    fputcsv($file, [
                        $row['tienda_vendedor'] ?? '',
                        $row['vendedor_dia'] ?? '',
                        $row['plaza_ajustada'] ?? '',
                        $row['ctienda'] ?? '',
                        $row['vend_clave'] ?? '',
                        $row['fecha'] ?? '',
                        $row['venta_total'] ?? 0,
                        $row['devolucion'] ?? 0,
                        $row['venta_neta'] ?? 0,
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error en exportCsv: '.$e->getMessage());

            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al exportar CSV: '.$e->getMessage());
        }
    }

    /**
     * Exportar a PDF
     */
    public function exportPdf(Request $request)
    {
        try {
            $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
            $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $vendedor = $request->input('vendedor', '');

            $filtros = [
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'plaza' => is_array($plaza) ? implode(',', $plaza) : $plaza,
                'tienda' => is_array($tienda) ? implode(',', $tienda) : $tienda,
                'vendedor' => $vendedor,
            ];

            $resultados = ReportService::getVendedoresReport($filtros);
            $estadisticas = ReportService::calcularEstadisticasVendedores($resultados);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.vendedores.pdf', compact('resultados', 'estadisticas', 'filtros'))
                ->setPaper('a4', 'landscape');

            return $pdf->download('Reporte_Vendedores_'.date('Ymd_His').'.pdf');

        } catch (\Exception $e) {
            Log::error('Error en exportPdf: '.$e->getMessage());

            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al exportar PDF: '.$e->getMessage());
        }
    }

    public function sync(Request $request)
    {
        $request->validate([
            'type' => 'required|in:lastMonth,lastDays,day,period,full',
        ]);

        $type = $request->input('type');
        $append = $request->boolean('append', false);

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

        try {

            if (! $append && $type !== 'full') {
                DB::table('vendedores_cache')
                    ->whereBetween('fecha', [$start, $end])
                    ->delete();
            } elseif ($type === 'full' || ! $append) {
                DB::statement('TRUNCATE TABLE vendedores_cache RESTART IDENTITY CASCADE');
            }

            $sql = "INSERT INTO vendedores_cache (
                        tienda_vendedor, vendedor_dia, plaza_ajustada, ctienda, vend_clave,
                        fecha, venta_total, devolucion, venta_neta, created_at
                    )
                    SELECT
                        c.ctienda || '-' || c.vend_clave AS tienda_vendedor,
                        c.vend_clave || '-' || EXTRACT(DAY FROM c.nota_fecha) AS vendedor_dia,
                        CASE
                            WHEN c.ctienda IN ('T0014', 'T0017', 'T0031') THEN 'MANZA'
                            WHEN c.vend_clave = '14379' THEN 'MANZA'
                            ELSE c.cplaza
                        END AS plaza_ajustada,
                        c.ctienda,
                        c.vend_clave,
                        c.nota_fecha AS fecha,
                        SUM(c.nota_impor) AS venta_total,
                        COALESCE((
                            SELECT SUM(v.total_brut + v.impuesto)
                            FROM venta v
                            WHERE v.f_emision = c.nota_fecha
                            AND v.clave_vend = c.vend_clave
                            AND v.cplaza = c.cplaza
                            AND v.ctienda = c.ctienda
                            AND v.tipo_doc = 'DV'
                            AND v.estado NOT LIKE '%C%'
                            AND EXISTS (
                                SELECT 1 FROM partvta p
                                WHERE v.no_referen = p.no_referen
                                AND v.cplaza = p.cplaza
                                AND v.ctienda = p.ctienda
                                AND p.clave_art NOT LIKE '%CAMBIODOC%'
                                AND p.totxpart IS NOT NULL
                            )
                        ), 0) AS devolucion,
                        SUM(c.nota_impor) - COALESCE((
                            SELECT SUM(v.total_brut + v.impuesto)
                            FROM venta v
                            WHERE v.f_emision = c.nota_fecha
                            AND v.clave_vend = c.vend_clave
                            AND v.cplaza = c.cplaza
                            AND v.ctienda = c.ctienda
                            AND v.tipo_doc = 'DV'
                            AND v.estado NOT LIKE '%C%'
                            AND EXISTS (
                                SELECT 1 FROM partvta p
                                WHERE v.no_referen = p.no_referen
                                AND v.cplaza = p.cplaza
                                AND v.ctienda = p.ctienda
                                AND p.clave_art NOT LIKE '%CAMBIODOC%'
                                AND p.totxpart IS NOT NULL
                            )
                        ), 0) AS venta_neta,
                        NOW() AS created_at
                    FROM canota c
                    WHERE c.ban_status <> 'C'
                    AND c.nota_fecha BETWEEN :start AND :end
                    AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
                    AND c.ctienda NOT LIKE '%DESC%'
                    AND c.ctienda NOT LIKE '%CEDI%'
                    GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('vendedores_cache')->count();

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. Registros: {$count} (Período: {$start} - {$end})",
            ]);
        } catch (\Exception $e) {
            Log::error('Vendedores sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
