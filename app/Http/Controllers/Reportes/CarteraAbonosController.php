<?php
namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Reportes\CarteraAbonos;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Reportes\CarteraAbonosExport;
use PDF;
use Carbon\Carbon;

class CarteraAbonosController extends Controller
{
    // Vista del reporte
    public function index()
    {
        return view('reportes.cartera_abonos');
    }

    // Datos para DataTables (server-side paging)
    public function data(Request $request)
    {
        // Mes anterior
        $start = Carbon::parse('first day of previous month')->toDateString();
        $end   = Carbon::parse('last day of previous month')->toDateString();

        // DataTables parameters
        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        $columns = $request->input('columns', []);
        $order = $request->input('order', []);

        // Orden por columnas simples si se especifica
        $orderClause = 'Plaza ASC, Tienda ASC, Fecha ASC';
        if (!empty($order) && isset($order[0]['column'], $columns[$order[0]['column']])) {
            $colName = $columns[$order[0]['column']]['data'];
            $mapping = [
                'Plaza' => 'c.cplaza',
                'Tienda' => 'c.ctienda',
                'Fecha' => 'c.fecha',
                'Fecha_vta' => 'c2.fecha_venc',
                'Concepto' => 'c.concepto',
                'Tipo' => 'c.tipo_ref',
                'Factura' => 'c.no_ref',
                'Clave' => 'c.clave_cl',
                'RFC' => 'cl.clie_rfc',
                'Nombre' => 'cl.clie_nombr'
            ];
            if (isset($mapping[$colName])) {
                $orderClause = $mapping[$colName] . ' ' . strtoupper($order[0]['dir'] ?? 'ASC');
            }
        }

        $sql = "".
            " SELECT ".
            "   c.cplaza AS Plaza, " .
            "   c.ctienda AS Tienda, " .
            "   c.fecha AS Fecha, " .
            "   c2.dfechafac AS Fecha_vta, " .
            "   c.concepto AS Concepto, " .
            "   c.tipo_ref AS Tipo, " .
            "   c.no_ref AS Factura, " .
            "   cl.clie_clave AS Clave, " .
            "   cl.clie_rfc AS RFC, " .
            "   cl.clie_nombr AS Nombre, " .
            "   CASE WHEN c.tipo_ref = 'FA' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_fa, " .
            "   CASE WHEN c.tipo_ref = 'FA' AND c.concepto = 'DV' THEN c.IMPORTE ELSE 0 END AS monto_dv, " .
            "   CASE WHEN c.tipo_ref = 'CD' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_cd, " .
            "   COALESCE(cl.clie_credi, 0) AS Dias_Cred, " .
            "   (c.fecha - COALESCE(c2.fecha_venc, c.fecha)) AS Dias_Vencidos " .
            " FROM cobranza c " .
            " LEFT JOIN ( " .
            "   SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl " .
            "   FROM cobranza co WHERE co.cargo_ab = 'C' " .
            " ) AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl) " .
            " LEFT JOIN zona z ON z.plaza=c.cplaza AND z.tienda=c.ctienda " .
            " LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave) " .
            " WHERE c.cargo_ab='A' AND c.estado='S' AND c.cborrado<>'1' AND c.fecha >= :start AND c.fecha <= :end " .
            " ORDER BY $orderClause " .
            " LIMIT :length OFFSET :offset " .
            "";

        $rows = DB::select($sql, ['start'=>$start,'end'=>$end,'length'=>$length,'offset'=>$startIdx]);

        // Total registros (simplificado, para DataTables): cuenta base sin pagination
        $countSql = "SELECT COUNT(*) AS total FROM cobranza c WHERE c.cargo_ab='A' AND c.estado='S' AND c.cborrado<>'1' AND c.fecha >= :start AND c.fecha <= :end";
        $total = DB::selectOne($countSql, ['start'=>$start,'end'=>$end])->total ?? 0;

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => (int)$total,
            'recordsFiltered' => (int)$total,
            'data' => $rows
        ]);
    }

    // Export Excel
    public function exportExcel(Request $request)
    {
        $start = Carbon::parse('first day of previous month')->toDateString();
        $end   = Carbon::parse('last day of previous month')->toDateString();
        return Excel::download(new CarteraAbonosExport($start, $end), 'cartera_abonos.xlsx');
    }

    // Export CSV
    public function exportCsv(Request $request)
    {
        $start = Carbon::parse('first day of previous month')->toDateString();
        $end   = Carbon::parse('last day of previous month')->toDateString();
        return Excel::download(new CarteraAbonosExport($start, $end), 'cartera_abonos.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    // Export PDF
    public function exportPdf(Request $request)
    {
        $start = Carbon::parse('first day of previous month')->toDateString();
        $end   = Carbon::parse('last day of previous month')->toDateString();
        $data = DB::select("SELECT c.cplaza AS Plaza, c.ctienda AS Tienda, c.fecha AS Fecha, c2.dfechafac AS Fecha_vta, c.concepto AS Concepto, c.tipo_ref AS Tipo, c.no_ref AS Factura, cl.clie_clave AS Clave, cl.clie_rfc AS RFC, cl.clie_nombr AS Nombre, CASE WHEN c.tipo_ref = 'FA' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_fa, CASE WHEN c.tipo_ref = 'FA' AND c.concepto = 'DV' THEN c.IMPORTE ELSE 0 END AS monto_dv, CASE WHEN c.tipo_ref = 'CD' AND c.concepto <> 'DV' THEN C.IMPORTE ELSE 0 END AS monto_cd, COALESCE(cl.clie_credi, 0) AS Dias_Cred, (c.fecha - COALESCE(c2.fecha_venc, c.fecha)) AS Dias_Vencidos FROM cobranza c LEFT JOIN ( SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl FROM cobranza co WHERE co.cargo_ab='C') AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl) LEFT JOIN zona z ON z.plaza=c.cplaza AND z.tienda=c.ctienda LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave) WHERE c.cargo_ab='A' AND c.estado='S' AND c.cborrado<>'1' AND c.fecha >= :start AND c.fecha <= :end", ['start'=>$start, 'end'=>$end]);
        $pdf = PDF::loadView('reportes.cartera_abonos_pdf', ['data' => $data]);
        return $pdf->download('cartera_abonos.pdf');
    }
}
