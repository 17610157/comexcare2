<?php

namespace App\Http\Controllers;

use App\Helpers\RoleHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // Obtener fechas del mes actual
        $fecha_inicio = date('Y-m-01');
        $fecha_fin = date('Y-m-d');

        // Obtener filtros del usuario
        $plaza = '';
        $tienda = '';
        $error = null;

        if ($user) {
            $userFilter = RoleHelper::getUserFilter();

            // Si tiene asignaciones específicas, usarlas
            if (! empty($userFilter['plazas_asignadas'])) {
                $plaza = implode(',', $userFilter['plazas_asignadas']);
            }

            if (! empty($userFilter['tiendas_asignadas'])) {
                $tienda = implode(',', $userFilter['tiendas_asignadas']);
            }

            // Verificar si tiene acceso
            $accesoTotal = $userFilter['acceso_todas_tiendas'] ?? false;
            if (! $userFilter['allowed'] && ! $accesoTotal) {
                $error = $userFilter['message'] ?? 'No autorizado';
            }
        }

        // Calcular métricas (siempre, si es admin sin restricciones será con filtros vacíos = todos)
        $metricas = $this->calcularMetricas($fecha_inicio, $fecha_fin, $plaza, $tienda);

        return view('home', [
            'error' => $error,
            'ventas' => $metricas['ventas'],
            'devoluciones' => $metricas['devoluciones'],
            'alcance' => $metricas['alcance'],
            'neto' => $metricas['neto'],
            'meta' => $metricas['meta'],
            'periodo' => date('Y-m'),
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'plaza' => $plaza,
            'tienda' => $tienda,
        ]);
    }

    private function calcularMetricas($fecha_inicio, $fecha_fin, $plaza = '', $tienda = '')
    {
        // Ventas de canota (ventas de mostrador)
        $ventasSql = "
            SELECT COALESCE(SUM(nota_impor), 0) AS total_ventas
            FROM canota 
            WHERE ban_status <> 'C'
            AND nota_fecha BETWEEN ? AND ?
            AND ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
            AND ctienda NOT LIKE '%DESC%'
            AND ctienda NOT LIKE '%CEDI%'
        ";

        $params = [$fecha_inicio, $fecha_fin];

        if (! empty($plaza)) {
            if (strpos($plaza, ',') !== false) {
                $plazas = explode(',', $plaza);
                $placeholders = implode(',', array_fill(0, count($plazas), '?'));
                $ventasSql .= " AND cplaza IN ($placeholders)";
                $params = array_merge($params, $plazas);
            } else {
                $ventasSql .= ' AND cplaza = ?';
                $params[] = $plaza;
            }
        }

        if (! empty($tienda)) {
            if (strpos($tienda, ',') !== false) {
                $tiendas = explode(',', $tienda);
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $ventasSql .= " AND ctienda IN ($placeholders)";
                $params = array_merge($params, $tiendas);
            } else {
                $ventasSql .= ' AND ctienda = ?';
                $params[] = $tienda;
            }
        }

        $ventasResult = DB::select($ventasSql, $params);
        $ventas = floatval($ventasResult[0]->total_ventas ?? 0);

        // Devoluciones de tabla venta (tipo_doc = 'DV')
        $devSql = "
            SELECT COALESCE(SUM(v.total_brut + v.impuesto), 0) AS total_dev
            FROM venta v
            WHERE v.f_emision BETWEEN ? AND ?
            AND v.tipo_doc = 'DV'
            AND v.estado NOT LIKE '%C%'
        ";

        $devParams = [$fecha_inicio, $fecha_fin];

        if (! empty($plaza)) {
            if (strpos($plaza, ',') !== false) {
                $plazas = explode(',', $plaza);
                $placeholders = implode(',', array_fill(0, count($plazas), '?'));
                $devSql .= " AND v.cplaza IN ($placeholders)";
                $devParams = array_merge($devParams, $plazas);
            } else {
                $devSql .= ' AND v.cplaza = ?';
                $devParams[] = $plaza;
            }
        }

        if (! empty($tienda)) {
            if (strpos($tienda, ',') !== false) {
                $tiendas = explode(',', $tienda);
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $devSql .= " AND v.ctienda IN ($placeholders)";
                $devParams = array_merge($devParams, $tiendas);
            } else {
                $devSql .= ' AND v.ctienda = ?';
                $devParams[] = $tienda;
            }
        }

        $devResult = DB::select($devSql, $devParams);
        $devoluciones = floatval($devResult[0]->total_dev ?? 0);

        // Calcular neto
        $neto = $ventas - $devoluciones;

        // Obtener meta del período
        $metaSql = '
            SELECT COALESCE(SUM(m.meta_total), 0) AS total_meta
            FROM metas m
            WHERE m.fecha BETWEEN ? AND ?
        ';

        $metaParams = [$fecha_inicio, $fecha_fin];

        if (! empty($plaza)) {
            if (strpos($plaza, ',') !== false) {
                $plazas = explode(',', $plaza);
                $placeholders = implode(',', array_fill(0, count($plazas), '?'));
                $metaSql .= " AND m.plaza IN ($placeholders)";
                $metaParams = array_merge($metaParams, $plazas);
            } else {
                $metaSql .= ' AND m.plaza = ?';
                $metaParams[] = $plaza;
            }
        }

        if (! empty($tienda)) {
            if (strpos($tienda, ',') !== false) {
                $tiendas = explode(',', $tienda);
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $metaSql .= " AND m.tienda IN ($placeholders)";
                $metaParams = array_merge($metaParams, $tiendas);
            } else {
                $metaSql .= ' AND m.tienda = ?';
                $metaParams[] = $tienda;
            }
        }

        $metaResult = DB::select($metaSql, $metaParams);
        $meta = floatval($metaResult[0]->total_meta ?? 0);

        // Calcular alcance (porcentaje)
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
