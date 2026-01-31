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
        return view('reportes.cartera_abonos.index');
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
        $search = $request->input('search.value', '');
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;

        try {
            // Build base query with multi-field search
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
                    WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= :start AND c.fecha <= :end";

            $params = ['start' => $start, 'end' => $end];

            // Add multi-field search if provided
            if (!empty($search)) {
                $sql .= " AND (
                    c.cplaza ILIKE :search OR
                    c.ctienda ILIKE :search OR
                    cl.clie_nombr ILIKE :search OR
                    cl.clie_rfc ILIKE :search OR
                    c.no_ref ILIKE :search OR
                    cl.clie_clave ILIKE :search
                )";
                $params['search'] = '%' . $search . '%';
            }

            // Add plaza filter if provided (búsqueda exacta por código)
        if ($request->filled('plaza') && $request->input('plaza') !== '') {
            $sql .= " AND c.cplaza = :plaza";
            $params['plaza'] = trim($request->input('plaza'));
        }

        // Add tienda filter if provided (búsqueda exacta por código)
        if ($request->filled('tienda') && $request->input('tienda') !== '') {
            $sql .= " AND c.ctienda = :tienda";
            $params['tienda'] = trim($request->input('tienda'));
        }

            // Add tienda filter if provided (búsqueda exacta por código)
            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $sql .= " AND c.ctienda = :tienda";
                $params['tienda'] = trim($request->input('tienda'));
            }

            // Get total count for pagination - proper query construction
            $countSql = "SELECT COUNT(*) as count
                        FROM cobranza c
                        LEFT JOIN (
                          SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl
                          FROM cobranza co WHERE co.cargo_ab = 'C'
                        ) AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl)
                        LEFT JOIN zona z ON z.plaza=c.cplaza AND z.tienda=c.ctienda
                        LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave)
                        WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= :start AND c.fecha <= :end";

            $countParams = ['start' => $start, 'end' => $end];

            // Add same search conditions to count
            if (!empty($search)) {
                $countSql .= " AND (
                    c.cplaza ILIKE :search OR
                    c.ctienda ILIKE :search OR
                    cl.clie_nombr ILIKE :search OR
                    cl.clie_rfc ILIKE :search OR
                    c.no_ref ILIKE :search OR
                    cl.clie_clave ILIKE :search
                )";
                $countParams['search'] = '%' . $search . '%';
            }

            // Add same filters to count (búsqueda exacta por código)
            if ($request->filled('plaza') && $request->input('plaza') !== '') {
                $countSql .= " AND c.cplaza = :plaza";
                $countParams['plaza'] = trim($request->input('plaza'));
            }

            if ($request->filled('tienda') && $request->input('tienda') !== '') {
                $countSql .= " AND c.ctienda = :tienda";
                $countParams['tienda'] = trim($request->input('tienda'));
            }

            $total = DB::selectOne($countSql, $countParams)->count ?? 0;

            $sql .= " ORDER BY plaza, tienda, fecha LIMIT :length OFFSET :offset";
            $params['length'] = $lengthInt;
            $params['offset'] = $offsetInt;

            $rows = DB::select($sql, $params);

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
                WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= :start AND c.fecha <= :end";

        $params = ['start' => $start, 'end' => $end];

        // Add plaza filter if provided (búsqueda exacta por código)
        if ($request->filled('plaza') && $request->input('plaza') !== '') {
            $sql .= " AND c.cplaza = :plaza";
            $params['plaza'] = trim($request->input('plaza'));
        }

        // Add tienda filter if provided (búsqueda exacta por código)
        if ($request->filled('tienda') && $request->input('tienda') !== '') {
            $sql .= " AND c.ctienda = :tienda";
            $params['tienda'] = trim($request->input('tienda'));
        }

        $sql .= " ORDER BY plaza, tienda, fecha";

        $rows = DB::select($sql, $params);
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

    /**
     * Exportar a Excel
     */
    public function exportExcel(Request $request)
    {
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());

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
                WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= :start AND c.fecha <= :end";

        $params = ['start' => $start, 'end' => $end];

        if ($request->filled('plaza') && $request->input('plaza') !== '') {
            $sql .= " AND c.cplaza = :plaza";
            $params['plaza'] = trim($request->input('plaza'));
        }

        if ($request->filled('tienda') && $request->input('tienda') !== '') {
            $sql .= " AND c.ctienda = :tienda";
            $params['tienda'] = trim($request->input('tienda'));
        }

        $sql .= " ORDER BY plaza, tienda, fecha";
        $rows = DB::select($sql, $params);

        $filename = 'cartera_abonos_'.str_replace('-','',$start).'_to_'.str_replace('-','',$end).'.xlsx';

        if (class_exists('\Maatwebsite\Excel\Facades\Excel')) {
            return \Maatwebsite\Excel\Facades\Excel::download(
                new class($rows) implements \Maatwebsite\Excel\Concerns\FromArray {
                    private $rows;
                    public function __construct($rows) { $this->rows = $rows; }
                    public function array(): array { 
                        $data = [];
                        foreach ($this->rows as $row) {
                            $data[] = [
                                'Plaza' => $row->plaza ?? '',
                                'Tienda' => $row->tienda ?? '',
                                'Fecha' => $row->fecha ?? '',
                                'Fecha Vta' => $row->fecha_vta ?? '',
                                'Concepto' => $row->concepto ?? '',
                                'Tipo' => $row->tipo ?? '',
                                'Factura' => $row->factura ?? '',
                                'Clave' => $row->clave ?? '',
                                'RFC' => $row->rfc ?? '',
                                'Nombre' => $row->nombre ?? '',
                                'Monto FA' => $row->monto_fa ?? 0,
                                'Monto DV' => $row->monto_dv ?? 0,
                                'Monto CD' => $row->monto_cd ?? 0,
                                'Días Crédito' => $row->dias_cred ?? 0,
                                'Días Vencidos' => $row->dias_vencidos ?? 0,
                            ];
                        }
                        return $data;
                    }
                }, 
                $filename
            );
        }

        // Fallback CSV
        return $this->exportCsv($request);
    }

    /**
     * Exportar a CSV
     */
    public function exportCsv(Request $request)
    {
        $start = $request->input('period_start', Carbon::parse('first day of previous month')->toDateString());
        $end = $request->input('period_end', Carbon::parse('last day of previous month')->toDateString());

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
                WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= :start AND c.fecha <= :end";

        $params = ['start' => $start, 'end' => $end];

        if ($request->filled('plaza') && $request->input('plaza') !== '') {
            $sql .= " AND c.cplaza = :plaza";
            $params['plaza'] = trim($request->input('plaza'));
        }

        if ($request->filled('tienda') && $request->input('tienda') !== '') {
            $sql .= " AND c.ctienda = :tienda";
            $params['tienda'] = trim($request->input('tienda'));
        }

        $sql .= " ORDER BY plaza, tienda, fecha";
        $rows = DB::select($sql, $params);

        $filename = 'cartera_abonos_'.str_replace('-','',$start).'_to_'.str_replace('-','',$end).'.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function() use ($rows) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Plaza', 'Tienda', 'Fecha', 'Fecha Vta', 'Concepto', 'Tipo', 'Factura',
                'Clave', 'RFC', 'Nombre', 'Monto FA', 'Monto DV', 'Monto CD',
                'Días Crédito', 'Días Vencidos'
            ]);
            
            // Data
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
                    $row->monto_fa ?? 0,
                    $row->monto_dv ?? 0,
                    $row->monto_cd ?? 0,
                    $row->dias_cred ?? 0,
                    $row->dias_vencidos ?? 0
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
