<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        // Fechas del período actual (mes en curso)
        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-d');

        // Calcular métricas directamente (todas las plazas/tiendas)
        $metricas = $this->calcularMetricas($fecha_inicio, $fecha_fin);

        return view('home', [
            'ventas' => $metricas['ventas'],
            'devoluciones' => $metricas['devoluciones'],
            'alcance' => $metricas['alcance'],
            'neto' => $metricas['neto'],
            'meta' => $metricas['meta'],
            'periodo' => date('Y-m'),
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'plaza' => '',
            'tienda' => '',
        ]);
    }

    private function calcularMetricas($fecha_inicio, $fecha_fin, $plaza = '', $tienda = '')
    {
        // Ventas de xcorte
        $ventasSql = '
            SELECT COALESCE(SUM(
                (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                (COALESCE(vtacred, 0) - COALESCE(descred, 0))
            ), 0) AS total_ventas
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
        ';

        $params = [$fecha_inicio, $fecha_fin];

        $ventasResult = DB::select($ventasSql, $params);
        $ventas = floatval($ventasResult[0]->total_ventas ?? 0);

        // Devoluciones de tabla venta
        $devSql = "
            SELECT COALESCE(SUM(v.total_brut + v.impuesto), 0) AS total_dev
            FROM venta v
            WHERE v.f_emision BETWEEN ? AND ?
            AND v.tipo_doc = 'DV'
            AND v.estado NOT LIKE '%C%'
        ";

        $devParams = [$fecha_inicio, $fecha_fin];
        $devResult = DB::select($devSql, $devParams);
        $devoluciones = floatval($devResult[0]->total_dev ?? 0);

        // Calcular neto
        $neto = $ventas - $devoluciones;

        // Meta de tabla metas
        $metaSql = '
            SELECT COALESCE(SUM(meta_total), 0) AS total_meta
            FROM metas
            WHERE fecha BETWEEN ? AND ?
        ';

        $metaParams = [$fecha_inicio, $fecha_fin];
        $metaResult = DB::select($metaSql, $metaParams);
        $meta = floatval($metaResult[0]->total_meta ?? 0);

        // Calcular alcance
        $alcance = $meta > 0 ? ($ventas / $meta) * 100 : 0;

        return [
            'ventas' => $ventas,
            'devoluciones' => $devoluciones,
            'neto' => $neto,
            'meta' => $meta,
            'alcance' => $alcance,
        ];
    }
}
