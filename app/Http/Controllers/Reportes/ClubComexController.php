<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClubComexController extends Controller
{
    public function index()
    {
        $startDefault = Carbon::parse('first day of previous month')->toDateString();
        $endDefault = Carbon::parse('last day of previous month')->toDateString();

        return view('reportes.club_comex.index', compact('startDefault', 'endDefault'));
    }

    public function sync(Request $request)
    {
        $request->validate([
            'type' => 'required|in:lastMonth,lastDays,day,period,full,staggered',
        ]);

        $type = $request->input('type');

        if ($type === 'staggered') {
            return $this->syncStaggered($request);
        }

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

        $results = [];

        try {
            $results['redenciones'] = $this->syncRedenciones($start, $end);
            $results['acumulaciones'] = $this->syncAcumulaciones($start, $end);
            $results['acumulaciones_ia'] = $this->syncAcumulacionesIa($start, $end);

            return response()->json([
                'success' => true,
                'message' => "Sincronización completada. Período: {$start} - {$end}",
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('ClubComex sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncStaggered(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:'.date('Y'),
        ]);

        $year = (int) $request->input('year');
        $start = "{$year}-01-01";
        $end = $year === (int) date('Y') ? date('Y-m-d') : "{$year}-12-31";

        $results = [];

        try {
            $results['redenciones'] = $this->syncRedenciones($start, $end);
            $results['acumulaciones'] = $this->syncAcumulaciones($start, $end);
            $results['acumulaciones_ia'] = $this->syncAcumulacionesIa($start, $end);

            return response()->json([
                'success' => true,
                'message' => "Sincronización del año {$year} completada. Período: {$start} - {$end}",
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('ClubComex sync error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function syncRedenciones(string $start, string $end): array
    {
        $sql = "INSERT INTO redenciones_clubcomex (
                    plaza, tienda, clave, fecha, referencia, num_ref, importe, ing_egr,
                    campo3, clave_vend, nota_folio, factura, tipo_venta, clie_clave,
                    rfc, nombre, status, fecha2, clav,
                    descripcion, cantidad, precio, subtotal, importefin, costo, sr_recno
                )
                SELECT 
                    COALESCE(notas.cplaza, f.cplaza) AS plaza, 
                    COALESCE(notas.ctienda, f.ctienda) AS tienda, 
                    f.cve_con AS clave, 
                    f.fecha, 
                    f.ref_tipo AS referencia, 
                    f.ref_num AS num_ref, 
                    f.importe, 
                    f.ing_egr, 
                    COALESCE(ca.ccampo3, '') AS campo3, 
                    notas.vend_clave AS clave_vend, 
                    notas.nota_folio, 
                    notas.cfolio_r AS factura, 
                    CASE WHEN notas.nsaldo > 0 THEN 'credito' ELSE 'contado' END AS tipo_venta,
                    notas.clie_clave, 
                    '' AS rfc,
                    '' AS nombre,
                    COALESCE(notas.ban_status, '') AS status, 
                    notas.nota_fecha AS fecha2, 
                    COALESCE(notas.prod_clave, '') AS clav,
                    COALESCE(notas.cdesc_adi, '') AS descripcion, 
                    COALESCE(notas.nota_canti, 0) AS cantidad, 
                    COALESCE(notas.nota_preci, 0) AS precio,
                    CASE WHEN notas.nota_preci IS NULL OR notas.nota_preci = 0 THEN 0 ELSE notas.nota_canti * notas.nota_preci END AS subtotal, 
                    COALESCE(notas.nota_impor, 0) AS importefin,
                    COALESCE(notas.ncampo1, 0) AS costo,
                    COALESCE(notas.sr_recno, 0) AS sr_recno
                FROM 
                    (SELECT cplaza, ctienda, cve_con, fecha, ref_tipo, ref_num, importe, ing_egr 
                     FROM flujores
                     WHERE fecha >= :start_red AND fecha <= :end_red AND cve_con = 'PP' AND cborrado <> '1') f
                LEFT JOIN (
                    SELECT 
                        c.cplaza, c.ctienda, c.nota_folio, c.vend_clave, c.cfolio_r, c.nsaldo, c.clie_clave, c.ban_status, c.nota_fecha, c.nota_impor,
                        cu.prod_clave, cu.cdesc_adi, cu.nota_canti, cu.nota_preci, cu.sr_recno, cu.ncampo1
                    FROM 
                        canota c 
                    INNER JOIN 
                        (SELECT cplaza, ctienda, nota_folio, prod_clave, cdesc_adi, nota_canti, nota_preci, sr_recno, ncampo1
                         FROM cunota) cu ON cu.nota_folio = c.nota_folio AND c.cplaza = cu.cplaza AND c.ctienda = cu.ctienda
                    WHERE c.nota_fecha >= :start_red2 AND c.nota_fecha <= :end_red2
                ) notas ON notas.nota_folio = f.ref_num AND notas.cplaza = f.cplaza AND notas.ctienda = f.ctienda
                LEFT JOIN (SELECT cplaza, ctienda, nota_folio, ccampo3 FROM canotaex) ca
                    ON notas.nota_folio = ca.nota_folio AND notas.cplaza = ca.cplaza AND notas.ctienda = ca.ctienda";

        DB::insert($sql, [
            'start_red' => $start,
            'end_red' => $end,
            'start_red2' => $start,
            'end_red2' => $end,
        ]);

        $count = DB::table('redenciones_clubcomex')->whereBetween('fecha', [$start, $end])->count();

        return ['success' => true, 'count' => $count];
    }

    public function syncAcumulaciones(string $start, string $end): array
    {
        $sql = "INSERT INTO acumulaciones_clubcomex (
                    plaza, tienda, fecha, status, folio, vendedor, factura, clave_cliente,
                    rfc, nombre, importe, cnodoc, ccampo3, clave_prod, descripcion,
                    cantidad, precio, tipo_venta, subtotal, sr_recno, descuento
                )
                SELECT 
                    c.cplaza AS plaza, 
                    c.ctienda AS tienda, 
                    c.nota_fecha AS fecha, 
                    c.ban_status AS status, 
                    c.nota_folio AS folio, 
                    c.vend_clave AS vendedor, 
                    c.cfolio_r AS factura, 
                    c.clie_clave AS clave_cliente,
                    '' AS rfc,
                    '' AS nombre,
                    c.nota_impor AS importe, 
                    c.cnodoc, 
                    cx.ccampo3, 
                    cu.prod_clave AS clave_prod, 
                    cu.cdesc_adi AS descripcion,
                    cu.nota_canti AS cantidad, 
                    cu.nota_preci AS precio,
                    CASE WHEN c.nsaldo > 0 THEN 'credito' ELSE 'contado' END AS tipo_venta, 
                    CASE WHEN cu.nota_preci IS NULL OR cu.nota_preci = 0 THEN 0 ELSE cu.nota_canti * cu.nota_preci END AS subtotal,
                    cu.sr_recno, 
                    cu.nota_pdesc AS descuento
                FROM 
                    (SELECT cplaza, ctienda, nota_fecha, ban_status, clie_clave, nota_folio, vend_clave, 
                            cfolio_r, nota_impor, cnodoc, nsaldo 
                     FROM canota
                     WHERE nota_fecha >= :start_acc AND nota_fecha <= :end_acc AND cnodoc IS NOT NULL) c
                INNER JOIN (SELECT cplaza, ctienda, nota_folio, prod_clave, cdesc_adi, nota_canti, nota_preci, sr_recno, nota_pdesc 
                      FROM cunota) cu ON c.cplaza = cu.cplaza AND c.ctienda = cu.ctienda AND c.nota_folio = cu.nota_folio
                INNER JOIN (SELECT cplaza, ctienda, nota_folio, ccampo3, ccampo2 FROM canotaex) cx 
                    ON c.cplaza = cx.cplaza AND c.ctienda = cx.ctienda AND c.nota_folio = cx.nota_folio";

        DB::insert($sql, ['start_acc' => $start, 'end_acc' => $end]);

        $count = DB::table('acumulaciones_clubcomex')->whereBetween('fecha', [$start, $end])->count();

        return ['success' => true, 'count' => $count];
    }

    public function syncAcumulacionesIa(string $start, string $end): array
    {
        $sql = "INSERT INTO acumulaciones_clubcomex_ia (
                    plaza, tienda, fecha, status, folio, vendedor, factura, clave_cliente,
                    rfc, nombre, importe, cnodoc, ccampo3, tipo_venta
                )
                SELECT 
                    c.cplaza AS plaza, 
                    c.ctienda AS tienda, 
                    c.nota_fecha AS fecha, 
                    c.ban_status AS status, 
                    c.nota_folio AS folio, 
                    c.vend_clave AS vendedor, 
                    c.cfolio_r AS factura, 
                    c.clie_clave AS clave_cliente,
                    '' AS rfc,
                    '' AS nombre,
                    c.nota_impor AS importe, 
                    c.cnodoc, 
                    cx.ccampo3, 
                    CASE WHEN c.nsaldo > 0 THEN 'credito' ELSE 'contado' END AS tipo_venta 
                FROM 
                    (SELECT cplaza, ctienda, nota_fecha, ban_status, clie_clave, nota_folio, vend_clave, 
                            cfolio_r, nota_impor, cnodoc, nsaldo 
                     FROM canota
                     WHERE nota_fecha >= :start_ia AND nota_fecha <= :end_ia AND cnodoc IS NOT NULL) c
                INNER JOIN (SELECT cplaza, ctienda, nota_folio, ccampo3, ccampo2 FROM canotaex) cx 
                    ON c.cplaza = cx.cplaza AND c.ctienda = cx.ctienda AND c.nota_folio = cx.nota_folio";

        DB::insert($sql, ['start_ia' => $start, 'end_ia' => $end]);

        $count = DB::table('acumulaciones_clubcomex_ia')->whereBetween('fecha', [$start, $end])->count();

        return ['success' => true, 'count' => $count];
    }

    public function search(Request $request)
    {
        Log::info('ClubComex search request', $request->all());

        $request->validate([
            'ccampo3' => 'required|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
        ]);

        $ccampo3 = ltrim($request->input('ccampo3'), "'");
        $start = $request->input('period_start');
        $end = $request->input('period_end');

        try {
            $ccampo3Clean = ltrim($ccampo3, "'");

            $resumen = DB::table('acumulaciones_clubcomex')
                ->select('plaza', 'tienda')
                ->selectRaw("'$ccampo3Clean' as ccampo3")
                ->selectRaw('COUNT(DISTINCT clave_cliente) as cantidad_clientes')
                ->selectRaw('COUNT(DISTINCT folio) as cantidad_acumulaciones')
                ->selectRaw('SUM(importe) as total_acumulaciones')
                ->whereRaw('(ccampo3 LIKE ? OR cnodoc LIKE ?)', ['%'.$ccampo3Clean.'%', '%'.$ccampo3Clean.'%'])
                ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                ->groupBy('plaza', 'tienda')
                ->get();

            foreach ($resumen as &$row) {
                $rfcCount = DB::table('redenciones_clubcomex')
                    ->whereRaw('(campo3 LIKE ? OR campo3 LIKE ?)', ['%'.$ccampo3Clean.'%', "'%".$ccampo3Clean."%'"])
                    ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                    ->distinct()
                    ->count('clie_clave');

                $cveCount = DB::table('redenciones_clubcomex')
                    ->whereRaw('(campo3 LIKE ? OR campo3 LIKE ?)', ['%'.$ccampo3Clean.'%', "'%".$ccampo3Clean."%'"])
                    ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                    ->distinct()
                    ->count('clave');

                $redCount = DB::table('redenciones_clubcomex')
                    ->whereRaw('(campo3 LIKE ? OR campo3 LIKE ?)', ['%'.$ccampo3Clean.'%', "'%".$ccampo3Clean."%'"])
                    ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                    ->distinct()
                    ->count('num_ref');

                $redTotal = DB::table('redenciones_clubcomex')
                    ->whereRaw('(campo3 LIKE ? OR campo3 LIKE ?)', ['%'.$ccampo3Clean.'%', "'%".$ccampo3Clean."%'"])
                    ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                    ->sum('importefin');

                $row->cantidad_rfc = $rfcCount;
                $row->cantidad_cve_con = $cveCount;
                $row->cantidad_redenciones = $redCount;
                $row->total_redenciones = $redTotal ?? 0;
            }

            return response()->json([
                'success' => true,
                'data' => $resumen,
            ]);
        } catch (\Exception $e) {
            Log::error('ClubComex search error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function exportCsv(Request $request)
    {
        $request->validate([
            'ccampo3' => 'required|string',
            'period_start' => 'required|date',
            'period_end' => 'required|date',
        ]);

        $ccampo3 = $request->input('ccampo3');
        $start = $request->input('period_start');
        $end = $request->input('period_end');

        $ccampo3 = ltrim($request->input('ccampo3'), "'");

        $filename = 'club_comex_'.$ccampo3.'_'.date('Ymd').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($ccampo3, $start, $end) {
            $file = fopen('php://output', 'w');

            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, ['RESUMEN']);
            fputcsv($file, []);

            $ccampo3Clean = ltrim($ccampo3, "'");

            $resumen = DB::table('acumulaciones_clubcomex')
                ->select('plaza', 'tienda')
                ->selectRaw("'$ccampo3Clean' as ccampo3")
                ->selectRaw('COUNT(DISTINCT clave_cliente) as cantidad_clientes')
                ->selectRaw('COUNT(DISTINCT folio) as cantidad_acumulaciones')
                ->selectRaw('SUM(importe) as total_acumulaciones')
                ->whereRaw("(ccampo3 LIKE '%$ccampo3Clean%' OR cnodoc LIKE '%$ccampo3Clean%')")
                ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                ->groupBy('plaza', 'tienda')
                ->get();

            foreach ($resumen as &$row) {
                $acumfolios = DB::table('acumulaciones_clubcomex')
                    ->select('folio', 'plaza', 'tienda', 'clave_cliente')
                    ->whereRaw("(ccampo3 LIKE '%$ccampo3Clean%' OR cnodoc LIKE '%$ccampo3Clean%')")
                    ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                    ->distinct()
                    ->get();

                $folioList = [];
                $plazaList = [];
                $tiendaList = [];
                $claveClienteList = [];
                foreach ($acumfolios as $f) {
                    $folioList[] = $f->folio;
                    $plazaList[] = $f->plaza;
                    $tiendaList[] = $f->tienda;
                    if ($f->clave_cliente) {
                        $claveClienteList[] = $f->clave_cliente;
                    }
                }

                $rfcCount = DB::table('cliente_depurado as cl')
                    ->whereIn('cl.cplaza', array_unique($plazaList))
                    ->whereIn('cl.ctienda', array_unique($tiendaList))
                    ->whereIn('cl.clie_clave', array_unique($claveClienteList))
                    ->distinct()
                    ->count('cl.clie_rfc');

                $cveCount = DB::table('flujores as f')
                    ->whereIn('f.cplaza', array_unique($plazaList))
                    ->whereIn('f.ctienda', array_unique($tiendaList))
                    ->whereIn('f.ref_num', array_unique($folioList))
                    ->where('f.fecha', '>=', $start)
                    ->where('f.fecha', '<=', $end)
                    ->distinct()
                    ->count('f.cve_con');

                $redCount = DB::table('redenciones_clubcomex')
                    ->whereRaw("(campo3 LIKE '%$ccampo3Clean%' OR campo3 LIKE '%$ccampo3Clean')")
                    ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                    ->distinct()
                    ->count('num_ref');

                $redTotal = DB::table('redenciones_clubcomex')
                    ->whereRaw("(campo3 LIKE '%$ccampo3Clean%' OR campo3 LIKE '%$ccampo3Clean')")
                    ->whereRaw("fecha BETWEEN '$start' AND '$end'")
                    ->sum('importefin');

                $row->cantidad_rfc = $rfcCount;
                $row->cantidad_cve_con = $cveCount;
                $row->cantidad_redenciones = $redCount;
                $row->total_redenciones = $redTotal ?? 0;
            }

            fputcsv($file, ['Plaza', 'Tienda', 'CCampo3', 'Cantidad Clientes', 'Cantidad RFC', 'Cantidad Cve Con', 'Cantidad Redenciones', 'Cantidad Acumulaciones', 'Total Redenciones', 'Total Acumulaciones']);
            foreach ($resumen as $row) {
                fputcsv($file, [
                    $row->plaza ?? '',
                    $row->tienda ?? '',
                    $row->ccampo3 ?? '',
                    $row->cantidad_clientes ?? 0,
                    $row->cantidad_rfc ?? 0,
                    $row->cantidad_cve_con ?? 0,
                    $row->cantidad_redenciones ?? 0,
                    $row->cantidad_acumulaciones ?? 0,
                    $row->total_redenciones ?? 0,
                    $row->total_acumulaciones ?? 0,
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['ACUMULACIONES']);
            fputcsv($file, []);

            $ccampo3Clean = ltrim($ccampo3, "'");

            $acumulaciones = DB::table('acumulaciones_clubcomex as a')
                ->selectRaw('
                    DISTINCT ON (a.plaza, a.tienda, a.fecha, a.folio, a.clave_prod)
                    a.plaza, a.tienda, a.fecha, a.status, a.folio, a.vendedor, a.factura,
                    a.cnodoc,
                    cl.clie_rfc,
                    f.cve_con,
                    a.ccampo3, a.clave_prod, a.descripcion, a.cantidad, 
                    a.precio, a.descuento, a.tipo_venta, a.subtotal
                ')
                ->leftJoin('cliente_depurado as cl', function ($join) {
                    $join->on('a.plaza', '=', 'cl.cplaza')
                        ->on('a.tienda', '=', 'cl.ctienda')
                        ->on('a.clave_cliente', '=', 'cl.clie_clave');
                })
                ->leftJoin('flujores as f', function ($join) use ($start, $end) {
                    $join->on('a.plaza', '=', 'f.cplaza')
                        ->on('a.tienda', '=', 'f.ctienda')
                        ->on('a.folio', '=', 'f.ref_num')
                        ->where('f.fecha', '>=', DB::raw("'$start'"))
                        ->where('f.fecha', '<=', DB::raw("'$end'"));
                })
                ->whereRaw("(a.ccampo3 LIKE '%$ccampo3Clean%' OR a.cnodoc LIKE '%$ccampo3Clean%')")
                ->whereRaw("a.fecha BETWEEN '$start' AND '$end'")
                ->orderBy('a.plaza')->orderBy('a.tienda')->orderBy('a.fecha')->orderBy('a.folio')->orderBy('a.clave_prod')
                ->get();

            fputcsv($file, ['Plaza', 'Tienda', 'Fecha', 'Status', 'Folio', 'Vendedor', 'Factura', 'CNodoc', 'RFC', 'Cve Con', 'CCampo3', 'Producto', 'Descripcion', 'Cantidad', 'Precio', 'Descuento', 'Tipo Venta', 'Subtotal']);
            foreach ($acumulaciones as $row) {
                fputcsv($file, [
                    $row->plaza ?? '',
                    $row->tienda ?? '',
                    $row->fecha ?? '',
                    $row->status ?? '',
                    $row->folio ?? '',
                    $row->vendedor ?? '',
                    $row->factura ?? '',
                    $row->cnodoc ?? '',
                    $row->clie_rfc ?? '',
                    $row->cve_con ?? '',
                    $row->ccampo3 ?? '',
                    $row->clave_prod ?? '',
                    $row->descripcion ?? '',
                    $row->cantidad ?? 0,
                    $row->precio ?? 0,
                    $row->descuento ?? 0,
                    $row->tipo_venta ?? '',
                    $row->subtotal ?? 0,
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['REDENCIONES']);
            fputcsv($file, []);

            $redenciones = DB::table('redenciones_clubcomex as r')
                ->selectRaw('
                    DISTINCT ON (r.plaza, r.tienda, r.fecha, r.num_ref, r.clav)
                    r.plaza, r.tienda, r.clave, r.fecha, r.referencia, r.num_ref, r.importe, r.ing_egr, r.campo3, r.clave_vend,
                    r.nota_folio, r.factura, r.tipo_venta, r.clie_clave, r.status, r.fecha2, r.clav, r.descripcion, 
                    r.cantidad, r.precio, r.subtotal, r.importefin
                ')
                ->whereRaw("(r.campo3 LIKE '%$ccampo3Clean%' OR r.campo3 LIKE '%$ccampo3Clean')")
                ->whereRaw("r.fecha BETWEEN '$start' AND '$end'")
                ->orderBy('r.plaza')->orderBy('r.tienda')->orderBy('r.fecha')->orderBy('r.num_ref')->orderBy('r.clav')
                ->get();

            fputcsv($file, ['Plaza', 'Tienda', 'Clave', 'Fecha', 'Referencia', 'Num Ref', 'Importe', 'Ing Egr', 'Campo3', 'Clave Vend', 'Nota Folio', 'Factura', 'Tipo Venta', 'Cliente', 'Status', 'Fecha2', 'Producto', 'Descripcion', 'Cantidad', 'Precio', 'Subtotal', 'Importe Fin']);
            foreach ($redenciones as $row) {
                fputcsv($file, [
                    $row->plaza ?? '',
                    $row->tienda ?? '',
                    $row->clave ?? '',
                    $row->fecha ?? '',
                    $row->referencia ?? '',
                    $row->num_ref ?? '',
                    $row->importe ?? 0,
                    $row->ing_egr ?? '',
                    $row->campo3 ?? '',
                    $row->clave_vend ?? '',
                    $row->nota_folio ?? '',
                    $row->factura ?? '',
                    $row->tipo_venta ?? '',
                    $row->clie_clave ?? '',
                    $row->status ?? '',
                    $row->fecha2 ?? '',
                    $row->clav ?? '',
                    $row->descripcion ?? '',
                    $row->cantidad ?? 0,
                    $row->precio ?? 0,
                    $row->subtotal ?? 0,
                    $row->importefin ?? 0,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
