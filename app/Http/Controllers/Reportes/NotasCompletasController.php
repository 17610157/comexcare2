<?php

namespace App\Http\Controllers\Reportes;

use App\Exports\NotasCompletasExport;
use App\Helpers\RoleHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class NotasCompletasController extends Controller
{
    public function index()
    {
        $startDefault = Carbon::parse('first day of previous month')->toDateString();
        $endDefault = Carbon::parse('last day of previous month')->toDateString();

        $plazas = DB::table('notas_completas_cache')
            ->distinct()
            ->orderBy('plaza_ajustada')
            ->pluck('plaza_ajustada')
            ->filter()
            ->values();

        $tiendas = DB::table('notas_completas_cache')
            ->distinct()
            ->orderBy('ctienda')
            ->pluck('ctienda')
            ->filter()
            ->values();

        return view('reportes.notas_completas.index', compact('plazas', 'tiendas', 'startDefault', 'endDefault'));
    }

    public function data(Request $request)
    {
        $userFilter = RoleHelper::getUserFilter();

        if (! $userFilter['allowed']) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        Log::info('NotasCompletas data request', ['url' => $request->fullUrl(), 'params' => $request->all(), 'filter' => $userFilter]);

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

        try {
            $query = DB::table('notas_completas_cache');

            // Filtros según el rol del usuario
            if (! empty($userFilter['plaza'])) {
                $plazaUserFilter = $userFilter['plaza'];
                if (is_array($plazaUserFilter)) {
                    $query->whereIn('plaza_ajustada', $plazaUserFilter);
                } else {
                    $query->where('plaza_ajustada', $plazaUserFilter);
                }
            }

            if (! empty($userFilter['tienda'])) {
                $tiendaUserFilter = $userFilter['tienda'];
                if (is_array($tiendaUserFilter)) {
                    $query->whereIn('ctienda', $tiendaUserFilter);
                } else {
                    $query->where('ctienda', $tiendaUserFilter);
                }
            }

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('plaza_ajustada', 'ILIKE', '%'.$search.'%')
                        ->orWhere('ctienda', 'ILIKE', '%'.$search.'%')
                        ->orWhere('num_referencia', 'ILIKE', '%'.$search.'%')
                        ->orWhere('vend_clave', 'ILIKE', '%'.$search.'%')
                        ->orWhere('factura', 'ILIKE', '%'.$search.'%')
                        ->orWhere('producto', 'ILIKE', '%'.$search.'%')
                        ->orWhere('descripcion', 'ILIKE', '%'.$search.'%');
                });
            }

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $plazaFilter = $request->input('plaza');
                if (is_array($plazaFilter) && count($plazaFilter) > 0) {
                    $query->whereIn('plaza_ajustada', $plazaFilter);
                } elseif (! is_array($plazaFilter)) {
                    $query->where('plaza_ajustada', trim($plazaFilter));
                }
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $tiendaFilter = $request->input('tienda');
                if (is_array($tiendaFilter) && count($tiendaFilter) > 0) {
                    $query->whereIn('ctienda', $tiendaFilter);
                } elseif (! is_array($tiendaFilter)) {
                    $query->where('ctienda', trim($tiendaFilter));
                }
            }

            if ($request->filled('vendedor') && $request->input('vendedor') !== '') {
                $vendedorFilter = $request->input('vendedor');
                if (is_array($vendedorFilter) && count($vendedorFilter) > 0) {
                    $query->whereIn('vend_clave', $vendedorFilter);
                } else {
                    $query->where('vend_clave', trim($vendedorFilter));
                }
            }

            $query->whereBetween('fecha_vta', [$start, $end]);

            $total = $query->count();

            $data = $query->orderBy('fecha_vta')->orderBy('ctienda')->orderBy('num_referencia')
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
            Log::error('NotasCompletas data error: '.$e->getMessage());

            return response()->json(['draw' => (int) $request->input('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    public function exportExcel(Request $request)
    {
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());
        $plaza = $request->input('plaza', '');
        $tienda = $request->input('tienda', '');
        $vendedor = $request->input('vendedor', '');

        try {
            $filename = 'notas_completas_'.str_replace('-', '', $start).'_to_'.str_replace('-', '', $end).'.xlsx';

            return Excel::download(new NotasCompletasExport($start, $end, $plaza, $tienda, $vendedor), $filename);
        } catch (\Exception $e) {
            Log::error('NotasCompletas Excel error: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function exportCsv(Request $request)
    {
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());

        try {
            $query = DB::table('notas_completas_cache')
                ->whereBetween('fecha_vta', [$start, $end]);

            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $query->where('plaza_ajustada', trim($request->input('plaza')));
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $query->where('ctienda', trim($request->input('tienda')));
            }

            if ($request->filled('vendedor') && $request->input('vendedor') !== '') {
                $query->where('vend_clave', trim($request->input('vendedor')));
            }

            $filename = 'notas_completas_'.str_replace('-', '', $start).'_to_'.str_replace('-', '', $end).'.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ];

            $callback = function () use ($query) {
                $file = fopen('php://output', 'w');

                fputcsv($file, [
                    'Plaza', 'Tienda', 'Num Referencia', 'Vendedor', 'Factura',
                    'Nota Club', 'Club TR', 'Club ID', 'Fecha Vta', 'Producto',
                    'Descripcion', 'Piezas', 'Descuento', 'Precio Venta', 'Costo',
                    'Total con IVA', 'Total sin IVA',
                ]);

                $query->orderBy('fecha_vta')->orderBy('ctienda')->orderBy('num_referencia')
                    ->chunk(1000, function ($rows) use ($file) {
                        foreach ($rows as $row) {
                            fputcsv($file, [
                                $row->plaza_ajustada ?? '',
                                $row->ctienda ?? '',
                                $row->num_referencia ?? '',
                                $row->vend_clave ?? '',
                                $row->factura ?? '',
                                $row->nota_club ?? '',
                                $row->club_tr ?? '',
                                $row->club_id ?? '',
                                $row->fecha_vta ?? '',
                                $row->producto ?? '',
                                $row->descripcion ?? '',
                                $row->piezas ?? 0,
                                $row->descuento ?? 0,
                                $row->precio_venta ?? 0,
                                $row->costo ?? 0,
                                $row->total_con_iva ?? 0,
                                $row->total_sin_iva ?? 0,
                            ]);
                        }
                    });

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('NotasCompletas CSV error: '.$e->getMessage());

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
                DB::statement('TRUNCATE TABLE notas_completas_cache RESTART IDENTITY CASCADE');
            } else {
                // Solo eliminar los registros del período seleccionado
                DB::table('notas_completas_cache')
                    ->whereBetween('fecha_vta', [$start, $end])
                    ->delete();
            }

            $sql = "INSERT INTO notas_completas_cache (
                        plaza_ajustada, ctienda, num_referencia, vend_clave, factura,
                        nota_club, club_tr, club_id, fecha_vta, producto, descripcion,
                        piezas, descuento, precio_venta, costo, total_con_iva, total_sin_iva, updated_at
                    )
                    SELECT
                        CASE 
                            WHEN c.ctienda = 'T0014' THEN 'MANZA' 
                            WHEN c.ctienda = 'T0017' THEN 'MANZA' 
                            WHEN c.ctienda = 'T0031' THEN 'MANZA' 
                            WHEN c.vend_clave = '14379' THEN 'MANZA' 
                            ELSE c.cplaza 
                        END AS plaza_ajustada,
                        c.ctienda,
                        c.nota_folio AS num_referencia,
                        c.vend_clave,
                        c.cfolio_r AS factura,
                        TRIM(c.cnodoc) AS nota_club,
                        TRIM(cx.ccampo2) AS club_tr,
                        cx.ccampo3 AS club_id,
                        c.nota_fecha AS fecha_vta,
                        '''' || TRIM(cu.prod_clave) AS producto,
                        cu.cdesc_adi AS descripcion,
                        cu.nota_canti AS piezas,
                        cu.nota_pdesc AS descuento,
                        cu.nota_preci AS precio_venta,
                        cu.ncampo1 AS costo,
                        (cu.nota_canti * cu.nota_preci) AS total_con_iva,
                        ((cu.nota_canti * cu.nota_preci) / ('1' + (cu.nota_pimpu / '100'))) AS total_sin_iva,
                        NOW() AS updated_at
                    FROM canota c
                    INNER JOIN cunota cu ON c.nota_folio = cu.nota_folio AND c.cplaza = cu.cplaza AND c.ctienda = cu.ctienda
                    INNER JOIN canotaex cx ON c.cplaza = cx.cplaza AND c.ctienda = cx.ctienda AND c.nota_folio = cx.nota_folio
                    WHERE c.nota_fecha >= :start AND c.nota_fecha <= :end
                    AND c.ban_status <> 'C'
                    AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
                    AND c.ctienda NOT LIKE '%DESC%'
                    AND c.ctienda NOT LIKE '%CEDI%'";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('notas_completas_cache')->count();

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. Registros: {$count} (Período: {$start} - {$end})",
            ]);
        } catch (\Exception $e) {
            Log::error('NotasCompletas sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
