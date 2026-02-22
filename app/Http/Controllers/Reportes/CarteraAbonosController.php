<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\CarteraAbonosExport;
use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CarteraAbonosController extends Controller
{
    public function index()
    {
        $userFilter = RoleHelper::getUserFilter();

        if (!$userFilter['allowed']) {
            return redirect()->route('home')->with('error', $userFilter['message'] ?? 'No autorizado');
        }

        $startDefault = Carbon::parse('first day of previous month')->toDateString();
        $endDefault = Carbon::parse('last day of previous month')->toDateString();

        // Obtener listas filtradas por asignaciones del usuario
        $listas = RoleHelper::getListasParaFiltros();
        
        $plazas = $listas['plazas'];
        $tiendas = $listas['tiendas'];

        return view('reportes.cartera_abonos.index', compact('plazas', 'tiendas', 'startDefault', 'endDefault'));
    }

    public function data(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        if (! $userFilter['allowed']) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        Log::info('CarteraAbonos data request', ['url' => $request->fullUrl(), 'params' => $request->all(), 'filter' => $userFilter]);

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

        // Obtener tiendas permitidas usando el helper
        $tiendasPermitidas = RoleHelper::getTiendasAcceso();
        $plazasPermitidas = $userFilter['plazas_asignadas'] ?? [];

        try {
            $query = DB::table('cartera_abonos_cache');

            // Filtros según el rol del usuario - plazas
            if (!empty($plazasPermitidas)) {
                $query->whereIn('plaza', $plazasPermitidas);
            }

            // Filtros según el rol del usuario - tiendas específicas
            if (!empty($tiendasPermitidas)) {
                $query->whereIn('tienda', $tiendasPermitidas);
            }

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('plaza', 'ILIKE', '%'.$search.'%')
                        ->orWhere('tienda', 'ILIKE', '%'.$search.'%')
                        ->orWhere('nombre', 'ILIKE', '%'.$search.'%')
                        ->orWhere('rfc', 'ILIKE', '%'.$search.'%')
                        ->orWhere('factura', 'ILIKE', '%'.$search.'%')
                        ->orWhere('clave', 'ILIKE', '%'.$search.'%')
                        ->orWhere('vend_clave', 'ILIKE', '%'.$search.'%');
                });
            }

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $plazaFilter = $request->input('plaza');
                if (is_array($plazaFilter) && count($plazaFilter) > 0) {
                    $query->whereIn('plaza', $plazaFilter);
                } elseif (! is_array($plazaFilter)) {
                    $query->where('plaza', trim($plazaFilter));
                }
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $tiendaFilter = $request->input('tienda');
                if (is_array($tiendaFilter) && count($tiendaFilter) > 0) {
                    $query->whereIn('tienda', $tiendaFilter);
                } elseif (! is_array($tiendaFilter)) {
                    $query->where('tienda', trim($tiendaFilter));
                }
            }

            $query->whereBetween('fecha', [$start, $end]);

            $total = $query->count();

            $data = $query->orderBy('plaza')->orderBy('tienda')->orderBy('fecha')
                ->offset($offsetInt)
                ->limit($lengthInt)
                ->get();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('CarteraAbonos data error: '.$e->getMessage());

            return response()->json(['draw' => (int) $request->input('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    // Export PDF for the current period (or given period_start/period_end)
    public function pdf(Request $request)
    {
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());

        try {
            $query = DB::table('cartera_abonos_cache')
                ->whereBetween('fecha', [$start, $end]);

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $query->where('plaza', trim($request->input('plaza')));
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $query->where('tienda', trim($request->input('tienda')));
            }

            $data = $query->orderBy('plaza')->orderBy('tienda')->orderBy('fecha')->get();

            $filename = 'cartera_abonos_'.str_replace('-', '', $start).'_to_'.str_replace('-', '', $end).'.pdf';
            if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.cartera_abonos.cartera_abonos_pdf', ['data' => $data, 'start' => $start, 'end' => $end]);

                return $pdf->download($filename);
            }

            return view('reportes.cartera_abonos.cartera_abonos_pdf', ['data' => $data, 'start' => $start, 'end' => $end]);
        } catch (\Exception $e) {
            Log::error('CarteraAbonos PDF error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportExcel(Request $request)
    {
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');

        try {
            $filename = 'cartera_abonos_'.str_replace('-', '', $start).'_to_'.str_replace('-', '', $end).'.xlsx';

            return Excel::download(new CarteraAbonosExport($start, $end, $plaza, $tienda), $filename);
        } catch (\Exception $e) {
            Log::error('CarteraAbonos Excel error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportCsv(Request $request)
    {
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());

        try {
            $query = DB::table('cartera_abonos_cache')
                ->whereBetween('fecha', [$start, $end]);

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $query->where('plaza', trim($request->input('plaza')));
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $query->where('tienda', trim($request->input('tienda')));
            }

            $count = $query->count();

            $filename = 'cartera_abonos_'.str_replace('-', '', $start).'_to_'.str_replace('-', '', $end).'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($query) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'Plaza', 'Tienda', 'Fecha', 'Fecha Vta', 'Concepto', 'Tipo', 'Factura',
                    'Clave', 'RFC', 'Nombre', 'Vendedor', 'Monto FA', 'Monto DV', 'Monto CD',
                    'Días Crédito', 'Días Vencidos',
                ]);

                $query->orderBy('plaza')->orderBy('tienda')->orderBy('fecha')
                    ->chunk(1000, function ($rows) use ($file) {
                        foreach ($rows as $row) {
                            fputcsv($file, [
                                $row->plaza ?? '',
                                $row->tienda ?? '',
                                $row->fecha ?? '',
                                $row->fecha_vta ?? '',
                                $row->concepto ?? '',
                                $row->tipo ?? '',
                                $row->factura ?? '',
                                $row->clave ?? '',
                                $row->rfc ?? '',
                                $row->nombre ?? '',
                                $row->vend_clave ?? '',
                                $row->monto_fa ?? 0,
                                $row->monto_dv ?? 0,
                                $row->monto_cd ?? 0,
                                $row->dias_cred ?? 0,
                                $row->dias_vencidos ?? 0,
                            ]);
                        }
                    });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('CarteraAbonos CSV error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sync(Request $request)
    {
        $request->validate([
            'type' => 'required|in:lastMonth,lastDays,day,period,full',
        ]);

        $type = $request->input('type');
        $append = $request->boolean('append', false);

        // Determinar el período primero
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
            // Si es full o no append, truncamos la tabla
            if (! $append || $type === 'full') {
                DB::statement('TRUNCATE TABLE cartera_abonos_cache RESTART IDENTITY CASCADE');
            } else {
                // Solo eliminar los registros del período seleccionado
                DB::table('cartera_abonos_cache')
                    ->whereBetween('fecha', [$start, $end])
                    ->delete();
            }

            $sql = "INSERT INTO cartera_abonos_cache (
                        plaza, tienda, fecha, fecha_vta, concepto, tipo, factura,
                        clave, rfc, nombre, monto_fa, monto_dv, monto_cd,
                        dias_cred, dias_vencidos, vend_clave, updated_at
                    )
                    SELECT
                        c.cplaza AS plaza,
                        c.ctienda AS tienda,
                        c.fecha AS fecha,
                        c2.dfechafac AS fecha_vta,
                        c.concepto AS concepto,
                        c.tipo_ref AS tipo,
                        c.no_ref AS factura,
                        cl.clie_clave AS clave,
                        cl.clie_rfc AS rfc,
                        cl.clie_nombr AS nombre,
                        CASE WHEN c.tipo_ref = 'FA' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_fa,
                        CASE WHEN c.tipo_ref = 'FA' AND c.concepto = 'DV' THEN c.IMPORTE ELSE 0 END AS monto_dv,
                        CASE WHEN c.tipo_ref = 'CD' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_cd,
                        COALESCE(cl.clie_credi, 0) AS dias_cred,
                        (c.fecha - COALESCE(c2.fecha_venc, c.fecha)) AS dias_vencidos,
                        cn.vend_clave AS vend_clave,
                        NOW() AS updated_at
                    FROM cobranza c
                    LEFT JOIN (
                        SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl
                        FROM cobranza co WHERE co.cargo_ab = 'C'
                    ) AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl)
                    LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave)
                    LEFT JOIN canota cn ON (cn.cplaza = c.cplaza AND cn.ctienda = c.ctienda AND cn.cfolio_r = c.no_ref AND cn.ban_status <> 'C')
                    WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' 
                    AND c.fecha >= :start AND c.fecha <= :end";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('cartera_abonos_cache')->count();

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. Registros: {$count} (Período: {$start} - {$end})",
            ]);
        } catch (\Exception $e) {
            Log::error('CarteraAbonos sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
