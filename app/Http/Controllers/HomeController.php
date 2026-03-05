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
            'tickets' => $metricas['tickets'] ?? 0,
            'ticket_promedio' => $metricas['ticket_promedio'] ?? 0,
            'porc_devoluciones' => $metricas['porc_devoluciones'] ?? 0,
            'venta_contado' => $metricas['venta_contado'] ?? 0,
            'venta_credito' => $metricas['venta_credito'] ?? 0,
            'ventas_plaza' => $metricas['ventas_plaza'] ?? [],
            'ventas_tienda' => $metricas['ventas_tienda'] ?? [],
            'cartera_cargos' => $metricas['cartera_cargos'] ?? 0,
            'cartera_abonos' => $metricas['cartera_abonos'] ?? 0,
            'cartera_total' => $metricas['cartera_total'] ?? 0,
            'cartera_plaza' => $metricas['cartera_plaza'] ?? [],
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
            ), 0) AS total_ventas,
            COALESCE(SUM(
                (COALESCE(vtacont, 0) - COALESCE(descont, 0))
            ), 0) AS venta_contado,
            COALESCE(SUM(
                (COALESCE(vtacred, 0) - COALESCE(descred, 0))
            ), 0) AS venta_credito,
            COUNT(*) AS total_tickets
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
            $wherePlaza $whereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
        ";

        $ventasResult = DB::select($ventasSql, $params);
        $ventas = floatval($ventasResult[0]->total_ventas ?? 0);
        $ventaContado = floatval($ventasResult[0]->venta_contado ?? 0);
        $ventaCredito = floatval($ventasResult[0]->venta_credito ?? 0);
        $tickets = intval($ventasResult[0]->total_tickets ?? 0);
        $ticketPromedio = $tickets > 0 ? $ventas / $tickets : 0;

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
        $porcDevoluciones = $ventas > 0 ? ($devoluciones / $ventas) * 100 : 0;

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

        // Ventas por Plaza
        $ventasPlazaData = $this->getVentasPlazaData($fecha_inicio, $fecha_fin, $plazas, $tiendas);

        // Ventas por Tienda
        $ventasTiendaData = $this->getVentasTiendaData($fecha_inicio, $fecha_fin, $plazas, $tiendas);

        // Cartera y Abonos
        $carteraAbonosData = $this->getCarteraAbonosData($fecha_inicio, $fecha_fin, $plazas, $tiendas);

        return [
            'ventas' => $ventas,
            'devoluciones' => $devoluciones,
            'neto' => $neto,
            'meta' => $meta,
            'objetivo' => $objetivo,
            'alcance' => $alcance,
            'vendedores' => $vendedoresData,
            'tickets' => $tickets,
            'ticket_promedio' => $ticketPromedio,
            'porc_devoluciones' => $porcDevoluciones,
            'venta_contado' => $ventaContado,
            'venta_credito' => $ventaCredito,
            'ventas_plaza' => $ventasPlazaData,
            'ventas_tienda' => $ventasTiendaData,
            'cartera_cargos' => $carteraAbonosData['cargos'],
            'cartera_abonos' => $carteraAbonosData['abonos'],
            'cartera_total' => $carteraAbonosData['total'],
            'cartera_plaza' => $carteraAbonosData['por_plaza'],
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

    private function getVentasPlazaData($fecha_inicio, $fecha_fin, $plazas = [], $tiendas = [])
    {
        $wherePlaza = '';
        $whereTienda = '';
        $params = [$fecha_inicio, $fecha_fin];

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $wherePlaza = " AND cplaza IN ($placeholders)";
            $params = array_merge($params, $plazas);

            if (! empty($tiendas)) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $whereTienda = " AND ctienda IN ($placeholders)";
                $params = array_merge($params, $tiendas);
            }
        } elseif (! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $whereTienda = " AND ctienda IN ($placeholders)";
            $params = array_merge($params, $tiendas);
        }

        $sql = "
            SELECT 
                cplaza AS plaza,
                SUM(
                    (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                    (COALESCE(vtacred, 0) - COALESCE(descred, 0))
                ) AS ventas
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
            $wherePlaza $whereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
            GROUP BY cplaza
            ORDER BY ventas DESC
        ";

        $result = DB::select($sql, $params);
        $ventasPorPlaza = [];
        foreach ($result as $row) {
            $ventasPorPlaza[$row->plaza] = floatval($row->ventas);
        }

        $devParams = [$fecha_inicio, $fecha_fin];
        $devWherePlaza = '';
        $devWhereTienda = '';

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $devWherePlaza = " AND cplaza IN ($placeholders)";
            $devParams = array_merge($devParams, $plazas);

            if (! empty($tiendas)) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $devWhereTienda = " AND ctienda IN ($placeholders)";
                $devParams = array_merge($devParams, $tiendas);
            }
        } elseif (! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $devWhereTienda = " AND ctienda IN ($placeholders)";
            $devParams = array_merge($devParams, $tiendas);
        }

        $devSql = "
            SELECT 
                cplaza AS plaza,
                COALESCE(SUM(total_brut + impuesto), 0) AS devoluciones
            FROM venta 
            WHERE f_emision BETWEEN ? AND ?
            AND tipo_doc = 'DV'
            AND estado NOT LIKE '%C%'
            $devWherePlaza $devWhereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
            GROUP BY cplaza
        ";

        $devResult = DB::select($devSql, $devParams);
        $devPorPlaza = [];
        foreach ($devResult as $row) {
            $devPorPlaza[$row->plaza] = floatval($row->devoluciones);
        }

        $plazasKeys = array_unique(array_merge(array_keys($ventasPorPlaza), array_keys($devPorPlaza)));

        $resultado = [];
        foreach ($plazasKeys as $plaza) {
            $ventas = $ventasPorPlaza[$plaza] ?? 0;
            $devoluciones = $devPorPlaza[$plaza] ?? 0;
            $resultado[] = [
                'plaza' => $plaza,
                'ventas' => $ventas,
                'devoluciones' => $devoluciones,
                'neto' => $ventas - $devoluciones,
            ];
        }

        usort($resultado, function ($a, $b) {
            return $b['ventas'] - $a['ventas'];
        });

        return $resultado;
    }

    private function getVentasTiendaData($fecha_inicio, $fecha_fin, $plazas = [], $tiendas = [])
    {
        $wherePlaza = '';
        $whereTienda = '';
        $params = [$fecha_inicio, $fecha_fin];

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $wherePlaza = " AND cplaza IN ($placeholders)";
            $params = array_merge($params, $plazas);

            if (! empty($tiendas)) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $whereTienda = " AND ctienda IN ($placeholders)";
                $params = array_merge($params, $tiendas);
            }
        } elseif (! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $whereTienda = " AND ctienda IN ($placeholders)";
            $params = array_merge($params, $tiendas);
        }

        $sql = "
            SELECT 
                ctienda AS tienda,
                cplaza AS plaza,
                SUM(
                    (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                    (COALESCE(vtacred, 0) - COALESCE(descred, 0))
                ) AS ventas
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
            $wherePlaza $whereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
            GROUP BY ctienda, cplaza
            ORDER BY ventas DESC
            LIMIT 20
        ";

        $result = DB::select($sql, $params);
        $ventasPorTienda = [];
        foreach ($result as $row) {
            $ventasPorTienda[$row->tienda] = floatval($row->ventas);
        }

        $devParams = [$fecha_inicio, $fecha_fin];
        $devWherePlaza = '';
        $devWhereTienda = '';

        if (! empty($plazas)) {
            $placeholders = implode(',', array_fill(0, count($plazas), '?'));
            $devWherePlaza = " AND cplaza IN ($placeholders)";
            $devParams = array_merge($devParams, $plazas);

            if (! empty($tiendas)) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $devWhereTienda = " AND ctienda IN ($placeholders)";
                $devParams = array_merge($devParams, $tiendas);
            }
        } elseif (! empty($tiendas)) {
            $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
            $devWhereTienda = " AND ctienda IN ($placeholders)";
            $devParams = array_merge($devParams, $tiendas);
        }

        $devSql = "
            SELECT 
                ctienda AS tienda,
                COALESCE(SUM(total_brut + impuesto), 0) AS devoluciones
            FROM venta 
            WHERE f_emision BETWEEN ? AND ?
            AND tipo_doc = 'DV'
            AND estado NOT LIKE '%C%'
            $devWherePlaza $devWhereTienda
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
            AND ctienda NOT LIKE '%DESC%' AND ctienda NOT LIKE '%CEDI%'
            GROUP BY ctienda
        ";

        $devResult = DB::select($devSql, $devParams);
        $devPorTienda = [];
        foreach ($devResult as $row) {
            $devPorTienda[$row->tienda] = floatval($row->devoluciones);
        }

        $tiendasKeys = array_unique(array_merge(array_keys($ventasPorTienda), array_keys($devPorTienda)));

        $resultado = [];
        foreach ($tiendasKeys as $tienda) {
            $ventas = $ventasPorTienda[$tienda] ?? 0;
            $devoluciones = $devPorTienda[$tienda] ?? 0;
            $resultado[] = [
                'tienda' => $tienda,
                'ventas' => $ventas,
                'devoluciones' => $devoluciones,
                'neto' => $ventas - $devoluciones,
            ];
        }

        usort($resultado, function ($a, $b) {
            return $b['ventas'] - $a['ventas'];
        });

        return $resultado;
    }

    private function getCarteraAbonosData($fecha_inicio, $fecha_fin, $plazas = [], $tiendas = [])
    {
        $query = DB::table('cartera_abonos_cache')
            ->selectRaw('
                COALESCE(SUM(monto_fa), 0) AS cargos,
                COALESCE(SUM(monto_cd), 0) AS abonos,
                COALESCE(SUM(monto_fa + monto_dv + monto_cd), 0) AS total
            ')
            ->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);

        if (! empty($plazas)) {
            $query->whereIn('plaza', $plazas);
        }

        if (! empty($tiendas)) {
            $query->whereIn('tienda', $tiendas);
        }

        $result = $query->first();

        $cargos = floatval($result->cargos ?? 0);
        $abonos = floatval($result->abonos ?? 0);
        $total = floatval($result->total ?? 0);

        $porPlaza = DB::table('cartera_abonos_cache')
            ->select('plaza')
            ->selectRaw('
                COALESCE(SUM(monto_fa), 0) AS cargos,
                COALESCE(SUM(monto_cd), 0) AS abonos,
                COALESCE(SUM(monto_fa + monto_dv + monto_cd), 0) AS total
            ')
            ->whereBetween('fecha', [$fecha_inicio, $fecha_fin]);

        if (! empty($plazas)) {
            $porPlaza->whereIn('plaza', $plazas);
        }

        if (! empty($tiendas)) {
            $porPlaza->whereIn('tienda', $tiendas);
        }

        $porPlaza = $porPlaza->groupBy('plaza')
            ->orderBy('total', 'desc')
            ->get();

        $porPlazaArray = array_map(function ($row) {
            return [
                'plaza' => $row->plaza,
                'cargos' => floatval($row->cargos),
                'abonos' => floatval($row->abonos),
                'total' => floatval($row->total),
            ];
        }, $porPlaza->toArray());

        return [
            'cargos' => $cargos,
            'abonos' => $abonos,
            'total' => $total,
            'por_plaza' => $porPlazaArray,
        ];
    }
}
