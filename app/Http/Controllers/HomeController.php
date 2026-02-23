<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
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
        $userFilter = RoleHelper::getUserFilter();

        if (! $userFilter['allowed']) {
            return view('home', [
                'error' => $userFilter['message'] ?? 'No autorizado',
                'ventas' => 0,
                'devoluciones' => 0,
                'alcance' => 0,
                'neto' => 0,
                'meta' => 0,
                'periodo' => date('Y-m'),
                'fecha_inicio' => date('Y-m-01'),
                'fecha_fin' => date('Y-m-d'),
                'plaza' => '',
                'tienda' => '',
            ]);
        }

        $fecha_inicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fecha_fin = $request->input('fecha_fin', date('Y-m-d'));

        $plazas = $userFilter['plazas_asignadas'] ?? [];
        $tiendas = $userFilter['tiendas_asignadas'] ?? [];
        $accesoTotal = $userFilter['acceso_todas_tiendas'] ?? false;

        $plazaLabel = ! empty($plazas) ? implode(', ', $plazas) : '';
        $tiendaLabel = ! empty($tiendas) ? implode(', ', $tiendas) : '';

        $metricas = $this->calcularMetricas($fecha_inicio, $fecha_fin, $plazas, $tiendas, $accesoTotal);

        return view('home', [
            'ventas' => $metricas['ventas'],
            'devoluciones' => $metricas['devoluciones'],
            'alcance' => $metricas['alcance'],
            'neto' => $metricas['neto'],
            'meta' => $metricas['meta'],
            'objetivo' => $metricas['objetivo'],
            'vendedores' => $metricas['vendedores'],
            'periodo' => date('Y-m', strtotime($fecha_inicio)),
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'plaza' => $plazaLabel,
            'tienda' => $tiendaLabel,
        ]);
    }

    private function calcularMetricas($fecha_inicio, $fecha_fin, $plazas = [], $tiendas = [], $accesoTotal = false)
    {
        $wherePlaza = '';
        $whereTienda = '';
        $params = [$fecha_inicio, $fecha_fin];

        // Si el usuario tiene plazas asignadas, siempre filtrar por ellas
        // (sin importar si tiene acceso a todas las tiendas de esas plazas)
        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $wherePlaza = " AND cplaza IN ($placeholders)";
            $params = array_merge($params, $plazas);

            if (! empty($tiendas)) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $whereTienda = " AND ctienda IN ($placeholders)";
                $params = array_merge($params, $tiendas);
            }
        } elseif (! $accesoTotal && ! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $whereTienda = " AND ctienda IN ($placeholders)";
            $params = array_merge($params, $tiendas);
        }

        $ventasSql = "
            SELECT COALESCE(SUM(
                (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                (COALESCE(vtacred, 0) - COALESCE(descred, 0))
            ), 0) AS total_ventas
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
            $wherePlaza $whereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
        ";

        $ventasResult = DB::select($ventasSql, $params);
        $ventas = floatval($ventasResult[0]->total_ventas ?? 0);

        $devParams = $params;
        $devSql = "
            SELECT COALESCE(SUM(v.total_brut + v.impuesto), 0) AS total_dev
            FROM venta v
            WHERE v.f_emision BETWEEN ? AND ?
            AND v.tipo_doc = 'DV'
            AND v.estado NOT LIKE '%C%'
            AND v.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND v.ctienda NOT LIKE '%DESC%' AND v.ctienda NOT LIKE '%CEDI%'
        ";

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $devSql .= " AND v.cplaza IN ($placeholders)";
            $devParams = array_merge(array_slice($params, 0, 2), $plazas);
        }

        if (! empty($tiendas) && empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $devSql .= " AND v.ctienda IN ($placeholders)";
            $devParams = array_merge($devParams, $tiendas);
        } elseif (! empty($tiendas) && ! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $devSql .= " AND v.ctienda IN ($placeholders)";
            $devParams = array_merge($devParams, $tiendas);
        }

        $devResult = DB::select($devSql, $devParams);
        $devoluciones = floatval($devResult[0]->total_dev ?? 0);

        $neto = $ventas - $devoluciones;

        $periodo = date('Y-m');

        // Meta: suma de meta_total (un registro por tienda)
        $metaParams = [];
        $metaWherePlaza = '';

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $metaWherePlaza = " AND plaza IN ($placeholders)";
            $metaParams = $plazas;
        }

        $metaTiendaFilter = '';
        if (! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $metaTiendaFilter = " AND tienda IN ($placeholders)";
        } else {
            $metaTiendaFilter = " AND tienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND tienda NOT LIKE '%DESC%' AND tienda NOT LIKE '%CEDI%'";
        }

        // Meta: suma de meta_total (un registro por tienda)
        $metaSql = "
            SELECT COALESCE(SUM(meta_total), 0) AS total_meta
            FROM (
                SELECT DISTINCT ON (tienda) tienda, meta_total
                FROM metas
                WHERE fecha BETWEEN ? AND ?
                $metaWherePlaza $metaTiendaFilter
            ) AS subquery
        ";

        $queryParams = array_merge([$fecha_inicio, $fecha_fin], $metaParams);
        if (! empty($tiendas)) {
            $queryParams = array_merge($queryParams, $tiendas);
        }

        $metaResult = DB::select($metaSql, $queryParams);
        $meta = floatval($metaResult[0]->total_meta ?? 0);

        // Objetivo: suma de (meta_mensual / dias_mes) * valor_dia para cada tienda
        // Para cada tienda: (meta / dias_mes) * suma(valor_dia hasta fecha actual)

        // Obtener suma total de valor_dia de metas_dias hasta fecha actual
        $valorDiaTotal = DB::table('metas_dias')
            ->where('periodo', $periodo)
            ->where('fecha', '<=', $fecha_fin)
            ->sum('valor_dia');

        // Obtener las tiendas con sus metas
        $metaQuery = DB::table('metas_mensual as m')
            ->select('m.plaza', 'm.tienda', 'm.meta')
            ->where('m.periodo', $periodo);

        if (! empty($plazas)) {
            $metaQuery = $metaQuery->whereIn('m.plaza', $plazas);
        }

        if (! empty($tiendas)) {
            $metaQuery = $metaQuery->whereIn('m.tienda', $tiendas);
        } else {
            $metaQuery = $metaQuery->whereNotIn('m.tienda', ['ALMAC', 'BODEG', 'ALTAP', 'CXVEA', '00095', 'GALMA', 'B0001', '00027', '00095', 'GALMA', 'BOVER'])
                ->where('m.tienda', 'NOT LIKE', '%DESC%')
                ->where('m.tienda', 'NOT LIKE', '%CEDI%');
        }

        $tiendasMeta = $metaQuery->get();

        // Obtener dias_total de metas (un solo valor)
        $diasTotal = DB::table('metas')
            ->whereBetween('fecha', [$fecha_inicio, $fecha_fin])
            ->avg('dias_total');

        // Calcular objetivo: SUM((meta / dias_total) * valor_dia_total)
        $objetivo = 0;
        foreach ($tiendasMeta as $tm) {
            $metaTienda = floatval($tm->meta ?? 0);
            $dias = floatval($diasTotal ?? 1);

            if ($dias > 0) {
                $objetivo += ($metaTienda / $dias) * $valorDiaTotal;
            }
        }

        // Alcance = ventas / objetivo
        $alcance = $objetivo > 0 ? ($ventas / $objetivo) * 100 : 0;

        // Tabla de vendedores
        $vendedoresData = $this->getVendedoresData($fecha_inicio, $fecha_fin, $plazas, $tiendas);

        return [
            'ventas' => $ventas,
            'devoluciones' => $devoluciones,
            'neto' => $neto,
            'meta' => $meta,
            'objetivo' => $objetivo,
            'alcance' => $alcance,
            'vendedores' => $vendedoresData,
        ];
    }

    private function getVendedoresData($fecha_inicio, $fecha_fin, $plazas = [], $tiendas = [])
    {
        // Ventas de vendedores_cache - agrupar solo por vendedor
        $query = DB::table('vendedores_cache')
            ->select('vend_clave')
            ->selectRaw('SUM(venta_total) as ventas')
            ->whereBetween('nota_fecha', [$fecha_inicio, $fecha_fin])
            ->whereNotNull('vend_clave')
            ->where('vend_clave', '<>', '')
            ->whereNotIn('vend_clave', ['SUPTI', 'PUBLI']);

        if (! empty($plazas)) {
            $query->whereIn('plaza_ajustada', $plazas);
        }

        if (! empty($tiendas)) {
            $query->whereIn('ctienda', $tiendas);
        } else {
            $query->whereNotIn('ctienda', ['ALMAC', 'BODEG', 'ALTAP', 'CXVEA', '00095', 'GALMA', 'B0001', '00027', '00095', 'GALMA', 'BOVER'])
                ->where('ctienda', 'NOT LIKE', '%DESC%')
                ->where('ctienda', 'NOT LIKE', '%CEDI%');
        }

        $result = $query->groupBy('vend_clave')
            ->orderBy('ventas', 'desc')
            ->get();

        // Obtener lista de tiendas por vendedor
        $tiendasPorVendedor = DB::table('vendedores_cache')
            ->select('vend_clave', 'ctienda')
            ->whereBetween('nota_fecha', [$fecha_inicio, $fecha_fin]);

        if (! empty($plazas)) {
            $tiendasPorVendedor->whereIn('plaza_ajustada', $plazas);
        }

        if (! empty($tiendas)) {
            $tiendasPorVendedor->whereIn('ctienda', $tiendas);
        } else {
            $tiendasPorVendedor->whereNotIn('ctienda', ['ALMAC', 'BODEG', 'ALTAP', 'CXVEA', '00095', 'GALMA', 'B0001', '00027', '00095', 'GALMA', 'BOVER'])
                ->where('ctienda', 'NOT LIKE', '%DESC%')
                ->where('ctienda', 'NOT LIKE', '%CEDI%');
        }

        $tiendasData = $tiendasPorVendedor->groupBy('vend_clave', 'ctienda')
            ->pluck('ctienda', 'vend_clave');

        // Obtener devoluciones por tienda de tabla venta
        $devPorTienda = DB::table('venta as v')
            ->select('v.ctienda')
            ->selectRaw('SUM(v.total_brut + v.impuesto) as devoluciones')
            ->whereBetween('v.f_emision', [$fecha_inicio, $fecha_fin])
            ->where('v.tipo_doc', 'DV')
            ->where('v.estado', 'NOT LIKE', '%C%');

        if (! empty($plazas)) {
            $devPorTienda->whereIn('v.cplaza', $plazas);
        }

        if (! empty($tiendas)) {
            $devPorTienda->whereIn('v.ctienda', $tiendas);
        } else {
            $devPorTienda->whereNotIn('v.ctienda', ['ALMAC', 'BODEG', 'ALTAP', 'CXVEA', '00095', 'GALMA', 'B0001', '00027', '00095', 'GALMA', 'BOVER'])
                ->where('v.ctienda', 'NOT LIKE', '%DESC%')
                ->where('v.ctienda', 'NOT LIKE', '%CEDI%');
        }

        $devPorTienda = $devPorTienda->groupBy('v.ctienda')
            ->pluck('devoluciones', 'ctienda')
            ->toArray();

        // Obtener ventas por tienda para calcular proporción
        $ventasPorTienda = DB::table('vendedores_cache')
            ->select('ctienda')
            ->selectRaw('SUM(venta_total) as ventas')
            ->whereBetween('nota_fecha', [$fecha_inicio, $fecha_fin]);

        if (! empty($plazas)) {
            $ventasPorTienda->whereIn('plaza_ajustada', $plazas);
        }

        if (! empty($tiendas)) {
            $ventasPorTienda->whereIn('ctienda', $tiendas);
        } else {
            $ventasPorTienda->whereNotIn('ctienda', ['ALMAC', 'BODEG', 'ALTAP', 'CXVEA', '00095', 'GALMA', 'B0001', '00027', '00095', 'GALMA', 'BOVER'])
                ->where('ctienda', 'NOT LIKE', '%DESC%')
                ->where('ctienda', 'NOT LIKE', '%CEDI%');
        }

        $ventasPorTienda = $ventasPorTienda->groupBy('ctienda')
            ->pluck('ventas', 'ctienda')
            ->toArray();

        // Construir resultado
        $resultado = [];
        foreach ($result as $r) {
            $clave = $r->vend_clave;
            $ventas = floatval($r->ventas);

            // Calcular devoluciones proporcionales
            $devoluciones = 0;

            // Obtener tiendas del vendedor aplicando filtros del usuario
            $tiendasQuery = DB::table('vendedores_cache')
                ->where('vend_clave', $clave)
                ->whereBetween('nota_fecha', [$fecha_inicio, $fecha_fin]);

            if (! empty($plazas)) {
                $tiendasQuery->whereIn('plaza_ajustada', $plazas);
            }

            if (! empty($tiendas)) {
                $tiendasQuery->whereIn('ctienda', $tiendas);
            } else {
                $tiendasQuery->whereNotIn('ctienda', ['ALMAC', 'BODEG', 'ALTAP', 'CXVEA', '00095', 'GALMA', 'B0001', '00027', '00095', 'GALMA', 'BOVER'])
                    ->where('ctienda', 'NOT LIKE', '%DESC%')
                    ->where('ctienda', 'NOT LIKE', '%CEDI%');
            }

            $tiendasVendedor = $tiendasQuery->distinct()
                ->pluck('ctienda')
                ->toArray();

            foreach ($tiendasVendedor as $tienda) {
                $ventasTienda = $ventasPorTienda[$tienda] ?? 1;
                $devTienda = $devPorTienda[$tienda] ?? 0;

                // Ventas de este vendedor en esta tienda
                $ventasVendedorEnTienda = DB::table('vendedores_cache')
                    ->where('vend_clave', $clave)
                    ->where('ctienda', $tienda)
                    ->whereBetween('nota_fecha', [$fecha_inicio, $fecha_fin])
                    ->sum('venta_total');

                if ($ventasTienda > 0) {
                    $devoluciones += ($ventasVendedorEnTienda / $ventasTienda) * $devTienda;
                }
            }

            $resultado[] = [
                'clave_vendedor' => $clave,
                'tiendas' => $tiendasVendedor,
                'ventas' => $ventas,
                'devoluciones' => $devoluciones,
                'ventas_net' => $ventas - $devoluciones,
            ];
        }

        // Ordenar por ventas descendente
        usort($resultado, function ($a, $b) {
            return $b['ventas'] - $a['ventas'];
        });

        return $resultado;
    }
}
