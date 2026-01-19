<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\VendedoresExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteVendedoresController extends Controller
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
        $vendedor = $request->input('vendedor', '');

        // Convertir fechas
        $f_inicio = str_replace('-', '', $fecha_inicio);
        $f_fin = str_replace('-', '', $fecha_fin);

        $resultados = [];
        $total_ventas = 0;
        $total_devoluciones = 0;
        $total_neto = 0;
        $error_msg = '';
        $tiempo_carga = 0;

        // Solo procesar si hay fechas
        if ($request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $inicio_tiempo = microtime(true);

            try {
                $sql = "
                SELECT 
                    c.ctienda || '-' || c.vend_clave AS tienda_vendedor,
                    c.vend_clave || '-' || EXTRACT(DAY FROM c.nota_fecha::date) AS vendedor_dia,
                    CASE
                        WHEN c.ctienda IN ('T0014', 'T0017', 'T0031') THEN 'MANZA'
                        WHEN c.vend_clave = '14379' THEN 'MANZA'
                        ELSE c.cplaza
                    END AS plaza_ajustada,
                    c.ctienda, 
                    c.vend_clave,
                    c.nota_fecha,
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
                    ), 0) AS devolucion
                FROM canota c 
                WHERE c.ban_status <> 'C' 
                  AND c.nota_fecha BETWEEN ? AND ?
                  AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
                  AND c.ctienda NOT LIKE '%DESC%' 
                  AND c.ctienda NOT LIKE '%CEDI%' 
                ";

                $params = [$f_inicio, $f_fin];
                
                if (!empty($plaza)) {
                    $sql .= " AND c.cplaza = ?";
                    $params[] = $plaza;
                }
                
                if (!empty($tienda)) {
                    $sql .= " AND c.ctienda = ?";
                    $params[] = $tienda;
                }
                
                if (!empty($vendedor)) {
                    $sql .= " AND c.vend_clave = ?";
                    $params[] = $vendedor;
                }

                $sql .= " GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave
                          ORDER BY c.ctienda || '-' || c.vend_clave, 
                                   c.vend_clave || '-' || TO_CHAR(TO_DATE(c.nota_fecha::text, 'YYYYMMDD'), 'DD')";

                $resultados_raw = DB::select($sql, $params);

                // Procesar resultados
                $contador = 0;
                foreach ($resultados_raw as $row) {
                    $contador++;
                    
                    // Formatear fecha
                    $fecha_str = (string)$row->nota_fecha;
                    if (strlen($fecha_str) == 8) {
                        $fecha = substr($fecha_str, 0, 4) . '-' . substr($fecha_str, 4, 2) . '-' . substr($fecha_str, 6, 2);
                    } else {
                        $fecha = $fecha_str;
                    }
                    
                    $venta_total = floatval($row->venta_total);
                    $devolucion = floatval($row->devolucion);
                    $venta_neta = $venta_total - $devolucion;
                    
                    // Asegurar que vendedor_dia tenga el formato correcto
                    $vendedor_dia = $row->vendedor_dia;
                    if (strpos($vendedor_dia, '-') !== false && strlen($fecha_str) == 8) {
                        $partes = explode('-', $vendedor_dia);
                        if (count($partes) == 2 && (strlen($partes[1]) == 0 || $partes[1] == '0' || $partes[1] == '1')) {
                            $dia = substr($fecha_str, 6, 2);
                            $vendedor_dia = $partes[0] . '-' . $dia;
                        }
                    }
                    
                    $resultados[] = [
                        'no' => $contador,
                        'tienda_vendedor' => $row->tienda_vendedor,
                        'vendedor_dia' => $vendedor_dia,
                        'plaza_ajustada' => $row->plaza_ajustada,
                        'ctienda' => $row->ctienda,
                        'vend_clave' => $row->vend_clave,
                        'fecha' => $fecha,
                        'venta_total' => $venta_total,
                        'devolucion' => $devolucion,
                        'venta_neta' => $venta_neta
                    ];
                    
                    // Acumular totales
                    $total_ventas += $venta_total;
                    $total_devoluciones += $devolucion;
                    $total_neto += $venta_neta;
                }
                
                $tiempo_carga = round((microtime(true) - $inicio_tiempo) * 1000, 2);
                
            } catch (\Exception $e) {
                $error_msg = "Error en la consulta: " . $e->getMessage();
            }
        }

        return view('reportes.vendedores.index', compact(
            'fecha_inicio', 
            'fecha_fin', 
            'plaza', 
            'tienda', 
            'vendedor', 
            'resultados', 
            'total_ventas', 
            'total_devoluciones', 
            'total_neto', 
            'error_msg', 
            'tiempo_carga'
        ));
    }

    /**
     * Exportar a Excel
     */
    public function export(Request $request)
    {
        $filtros = [
            'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
            'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
            'plaza' => $request->input('plaza', ''),
            'tienda' => $request->input('tienda', ''),
            'vendedor' => $request->input('vendedor', '')
        ];

        return Excel::download(new VendedoresExport($filtros), 
            'Reporte_Vendedores_' . date('Ymd_His') . '.xlsx'
        );
    }

    /**
     * Exportar a CSV
     */
    public function exportCsv(Request $request)
    {
        $filtros = [
            'fecha_inicio' => $request->input('fecha_inicio', date('Y-m-01')),
            'fecha_fin' => $request->input('fecha_fin', date('Y-m-d')),
            'plaza' => $request->input('plaza', ''),
            'tienda' => $request->input('tienda', ''),
            'vendedor' => $request->input('vendedor', '')
        ];

        return Excel::download(new VendedoresExport($filtros), 
            'Reporte_Vendedores_' . date('Ymd_His') . '.csv', 
            \Maatwebsite\Excel\Excel::CSV,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="Reporte_Vendedores_' . date('Ymd_His') . '.csv"',
            ]
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
            $plaza = $request->input('plaza', '');
            $tienda = $request->input('tienda', '');
            $vendedor = $request->input('vendedor', '');

            $f_inicio = str_replace('-', '', $fecha_inicio);
            $f_fin = str_replace('-', '', $fecha_fin);

            $sql = "
            SELECT 
                c.ctienda || '-' || c.vend_clave AS tienda_vendedor,
                c.vend_clave || '-' || EXTRACT(DAY FROM c.nota_fecha::date) AS vendedor_dia,
                CASE
                    WHEN c.ctienda IN ('T0014', 'T0017', 'T0031') THEN 'MANZA'
                    WHEN c.vend_clave = '14379' THEN 'MANZA'
                    ELSE c.cplaza
                END AS plaza_ajustada,
                c.ctienda, 
                c.vend_clave,
                c.nota_fecha,
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
                ), 0) AS devolucion
            FROM canota c 
            WHERE c.ban_status <> 'C' 
              AND c.nota_fecha BETWEEN ? AND ?
              AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
              AND c.ctienda NOT LIKE '%DESC%' 
              AND c.ctienda NOT LIKE '%CEDI%' 
            ";

            $params = [$f_inicio, $f_fin];
            
            if (!empty($plaza)) {
                $sql .= " AND c.cplaza = ?";
                $params[] = $plaza;
            }
            
            if (!empty($tienda)) {
                $sql .= " AND c.ctienda = ?";
                $params[] = $tienda;
            }
            
            if (!empty($vendedor)) {
                $sql .= " AND c.vend_clave = ?";
                $params[] = $vendedor;
            }

            $sql .= " GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave
                      ORDER BY c.ctienda || '-' || c.vend_clave, 
                               c.vend_clave || '-' || TO_CHAR(TO_DATE(c.nota_fecha::text, 'YYYYMMDD'), 'DD')";

            $resultados_raw = DB::select($sql, $params);

            $datos = [];
            $contador = 0;
            $total_ventas = 0;
            $total_devoluciones = 0;
            $total_neto = 0;

            foreach ($resultados_raw as $row) {
                $contador++;
                
                $fecha_str = (string)$row->nota_fecha;
                if (strlen($fecha_str) == 8) {
                    $fecha = substr($fecha_str, 0, 4) . '-' . substr($fecha_str, 4, 2) . '-' . substr($fecha_str, 6, 2);
                } else {
                    $fecha = $fecha_str;
                }
                
                $venta_total = floatval($row->venta_total);
                $devolucion = floatval($row->devolucion);
                $venta_neta = $venta_total - $devolucion;
                
                $vendedor_dia = $row->vendedor_dia;
                if (strpos($vendedor_dia, '-') !== false && strlen($fecha_str) == 8) {
                    $partes = explode('-', $vendedor_dia);
                    if (count($partes) == 2 && (strlen($partes[1]) == 0 || $partes[1] == '0' || $partes[1] == '1')) {
                        $dia = substr($fecha_str, 6, 2);
                        $vendedor_dia = $partes[0] . '-' . $dia;
                    }
                }
                
                $datos[] = [
                    'no' => $contador,
                    'tienda_vendedor' => $row->tienda_vendedor,
                    'vendedor_dia' => $vendedor_dia,
                    'plaza_ajustada' => $row->plaza_ajustada,
                    'ctienda' => $row->ctienda,
                    'vend_clave' => $row->vend_clave,
                    'fecha' => $fecha,
                    'venta_total' => $venta_total,
                    'devolucion' => $devolucion,
                    'venta_neta' => $venta_neta
                ];
                
                $total_ventas += $venta_total;
                $total_devoluciones += $devolucion;
                $total_neto += $venta_neta;
            }

            $data = [
                'datos' => $datos,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'plaza' => $plaza,
                'tienda' => $tienda,
                'vendedor' => $vendedor,
                'total_ventas' => $total_ventas,
                'total_devoluciones' => $total_devoluciones,
                'total_neto' => $total_neto,
                'total_registros' => $contador,
                'fecha_reporte' => date('d/m/Y H:i:s')
            ];
            
            $pdf = Pdf::loadView('reportes.vendedores.pdf', $data);
            
            return $pdf->download('Reporte_Vendedores_' . date('Ymd_His') . '.pdf');
            
        } catch (\Exception $e) {
            // Si hay error, redirigir con mensaje
            return redirect()->route('reportes.vendedores', $request->all())
                ->with('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }
}