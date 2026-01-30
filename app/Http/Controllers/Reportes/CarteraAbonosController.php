<?php
namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CarteraAbonosController extends Controller
{
    public function index()
    {
        return view('reportes.cartera_abonos.cartera_abonos');
    }

    public function data(Request $request)
    {
        Log::info('CarteraAbonos data request', ['url' => $request->fullUrl(), 'params' => $request->all()]);
        // Default period: previous month; optionally override with period_start/period_end
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
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;

        try {
            $sql = "SELECT
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
                      (c.fecha - COALESCE(c2.fecha_venc, c.fecha)) AS dias_vencidos
                    FROM cobranza c
                    LEFT JOIN (
                      SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl
                      FROM cobranza co WHERE co.cargo_ab = 'C'
                    ) AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl)
                    LEFT JOIN zona z ON z.plaza=c.cplaza AND z.tienda=c.ctienda
                    LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave)
                    WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= :start AND c.fecha <= :end
                    ORDER BY plaza, tienda, fecha
                    LIMIT :length OFFSET :offset";

            $rows = DB::select($sql, ['start'=>$start, 'end'=>$end, 'length'=>$lengthInt, 'offset'=>$offsetInt]);

            $total = DB::table('cobranza')
                ->where('cargo_ab','A')
                ->where('estado','S')
                ->where('cborrado','<>','1')
                ->whereBetween('fecha', [$start, $end])
                ->count();

            $data = [];
            foreach ($rows as $row) {
                $data[] = [
                    'plaza' => $row->plaza ?? '',
                    'tienda' => $row->tienda ?? '',
                    'fecha' => $row->fecha ?? '',
                    'fecha_vta' => $row->fecha_vta ?? '',
                    'concepto' => $row->concepto ?? '',
                    'tipo' => $row->tipo ?? '',
                    'factura' => $row->factura ?? '',
                    'clave' => $row->clave ?? '',
                    'rfc' => $row->rfc ?? '',
                    'nombre' => $row->nombre ?? '',
                    'monto_fa' => $row->monto_fa ?? 0,
                    'monto_dv' => $row->monto_dv ?? 0,
                    'monto_cd' => $row->monto_cd ?? 0,
                    'dias_cred' => $row->dias_cred ?? 0,
                    'dias_vencidos' => $row->dias_vencidos ?? 0
                ];
            }

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int)$total,
                'recordsFiltered' => (int)$total,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            Log::error('CarteraAbonos data error: ' . $e->getMessage());
            return response()->json(['draw' => (int)$request->input('draw', 1), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => [], 'error' => $e->getMessage()]);
        }
    }

    // Export PDF for the current period (or given period_start/period_end)
    public function pdf(Request $request)
    {
        // Determine period
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end   = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());

        // SQL without pagination, to export all matching rows
        $sql = "SELECT
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
                  (c.fecha - COALESCE(c2.fecha_venc, c.fecha)) AS dias_vencidos
                FROM cobranza c
                LEFT JOIN (
                  SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl
                  FROM cobranza co WHERE co.cargo_ab = 'C'
                ) AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl)
                LEFT JOIN zona z ON z.plaza=c.cplaza AND z.tienda=c.ctienda
                LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave)
                WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= :start AND c.fecha <= :end
                ORDER BY plaza, tienda, fecha";

        $rows = DB::select($sql, ['start'=>$start, 'end'=>$end]);
        $data = $rows;

        // Try to generate a PDF if the package is installed
        $filename = 'cartera_abonos_'.str_replace('-','',$start).'_to_'.str_replace('-','',$end).'.pdf';
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reportes.cartera_abonos.cartera_abonos_pdf', ['data' => $data, 'start' => $start, 'end' => $end]);
            return $pdf->download($filename);
        }
        // Fallback: render HTML view for debugging if PDF not installed
        return view('reportes.cartera_abonos.cartera_abonos_pdf', ['data' => $data, 'start' => $start, 'end' => $end]);
    }
}
