<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReporteMetasVentas extends Model
{
    protected $table = 'metas';

    /**
     * Obtener datos del reporte con filtros - VERSIÓN OPTIMIZADA Y CORREGIDA
     */
    public static function obtenerReporte($filtros)
    {
        $fecha_inicio = $filtros['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $filtros['fecha_fin'] ?? date('Y-m-d');
        $plaza = $filtros['plaza'] ?? '';
        $tienda = $filtros['tienda'] ?? '';
        $zona = $filtros['zona'] ?? '';

        // Obtener datos de metas
        $datosMetas = self::obtenerDatosMetas($fecha_inicio, $fecha_fin, $plaza, $tienda, $zona);
        
        // Obtener ventas del período completo (para cálculo de acumulados)
        $ventasDiarias = self::obtenerVentasDiariasPeriodo($fecha_inicio, $fecha_fin, $plaza, $tienda);
        
        // Procesar en PHP para calcular venta acumulada CORREGIDA
        $resultados = self::procesarVentasAcumuladas($datosMetas, $ventasDiarias, $fecha_inicio);
        
        return $resultados;
    }

    /**
     * Obtener todas las ventas diarias del período (OPTIMIZADO)
     */
    private static function obtenerVentasDiariasPeriodo($fecha_inicio, $fecha_fin, $plaza = '', $tienda = '')
    {
        // Obtener el primer día del mes de la fecha inicio
        $primer_dia_mes = date('Y-m-01', strtotime($fecha_inicio));
        
        $sql = "
            SELECT 
                cplaza,
                tienda,
                fecha,
                SUM((COALESCE(vtacont, 0) - COALESCE(descont, 0)) + 
                    (COALESCE(vtacred, 0) - COALESCE(descred, 0))) as venta_dia
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
        ";
        
        $params = [$primer_dia_mes, $fecha_fin]; // Desde inicio del mes hasta fecha fin
        
        if (!empty($plaza)) {
            $sql .= " AND cplaza = ?";
            $params[] = $plaza;
        }
        
        if (!empty($tienda)) {
            $sql .= " AND tienda = ?";
            $params[] = $tienda;
        }
        
        $sql .= " GROUP BY cplaza, tienda, fecha
                  ORDER BY cplaza, tienda, fecha";
        
        return DB::select($sql, $params);
    }

    /**
     * Obtener datos de metas (OPTIMIZADO)
     */
    private static function obtenerDatosMetas($fecha_inicio, $fecha_fin, $plaza = '', $tienda = '', $zona = '')
    {
        $sql = "
            SELECT 
                bst.id_plaza,
                bst.clave_tienda,
                bst.nombre AS sucursal,
                m.fecha,
                bst.zona,
                m.meta_total,
                m.dias_total,
                m.valor_dia,
                m.meta_dia
            FROM metas m
            LEFT JOIN bi_sys_tiendas bst
                ON m.plaza = bst.id_plaza
                AND m.tienda = bst.clave_tienda
            WHERE m.fecha BETWEEN ? AND ?
        ";
        
        $params = [$fecha_inicio, $fecha_fin];
        
        if (!empty($plaza)) {
            $sql .= " AND bst.id_plaza = ?";
            $params[] = $plaza;
        }
        
        if (!empty($tienda)) {
            $sql .= " AND bst.clave_tienda = ?";
            $params[] = $tienda;
        }
        
        if (!empty($zona)) {
            $sql .= " AND bst.zona = ?";
            $params[] = $zona;
        }
        
        $sql .= " ORDER BY m.fecha, bst.id_plaza, bst.clave_tienda";
        
        return DB::select($sql, $params);
    }

    /**
     * Procesar ventas y calcular acumulados en PHP - VERSIÓN CORREGIDA
     */
    private static function procesarVentasAcumuladas($datosMetas, $ventasDiarias, $fecha_inicio)
    {
        // Crear array indexado de ventas diarias para búsqueda rápida
        $ventasIndexadas = [];
        foreach ($ventasDiarias as $venta) {
            $key = $venta->cplaza . '|' . $venta->tienda . '|' . $venta->fecha;
            $ventasIndexadas[$key] = floatval($venta->venta_dia);
        }
        
        // Crear array de acumulados por sucursal
        $acumuladosPorSucursal = [];
        $resultados = [];
        
        foreach ($datosMetas as $index => $meta) {
            // Crear clave para búsqueda rápida
            $key = $meta->id_plaza . '|' . $meta->clave_tienda . '|' . $meta->fecha;
            $sucursalKey = $meta->id_plaza . '|' . $meta->clave_tienda;
            
            // Obtener venta del día
            $venta_del_dia = $ventasIndexadas[$key] ?? 0;
            
            // Inicializar acumulado para esta sucursal si no existe
            if (!isset($acumuladosPorSucursal[$sucursalKey])) {
                $acumuladosPorSucursal[$sucursalKey] = 0;
            }
            
            // Sumar venta del día al acumulado
            $acumuladosPorSucursal[$sucursalKey] += $venta_del_dia;
            $venta_acumulada = $acumuladosPorSucursal[$sucursalKey];
            
            // Calcular porcentaje del día
            $porcentaje = ($meta->meta_dia > 0) ? ($venta_del_dia / $meta->meta_dia) * 100 : 0;
            
            // Calcular porcentaje acumulado vs meta total (NUEVA COLUMNA)
            $porcentaje_acumulado = ($meta->meta_total > 0) ? ($venta_acumulada / $meta->meta_total) * 100 : 0;
            
            $resultados[] = (object)[
                'id_plaza' => $meta->id_plaza,
                'clave_tienda' => $meta->clave_tienda,
                'sucursal' => $meta->sucursal,
                'fecha' => $meta->fecha,
                'zona' => $meta->zona,
                'meta_total' => floatval($meta->meta_total),
                'dias_total' => intval($meta->dias_total),
                'valor_dia' => floatval($meta->valor_dia),
                'meta_dia' => floatval($meta->meta_dia),
                'venta_del_dia' => $venta_del_dia,
                'venta_acumulada' => $venta_acumulada,
                'porcentaje' => $porcentaje,
                'porcentaje_acumulado' => $porcentaje_acumulado
            ];
        }
        
        return $resultados;
    }

    /**
     * Obtener estadísticas del reporte - VERSIÓN CORREGIDA
     */
    public static function obtenerEstadisticas($resultados)
    {
        $estadisticas = [
            'total_meta_dia' => 0,
            'total_venta_dia' => 0,
            'total_venta_acumulada' => 0,
            'porcentaje_promedio' => 0,
            'porcentaje_acumulado_promedio' => 0,
            'total_registros' => 0,
            'total_meta_total' => 0
        ];

        if (count($resultados) > 0) {
            $total_meta_dia = 0;
            $total_venta_dia = 0;
            $porcentaje_total = 0;
            $porcentaje_acumulado_total = 0;
            $total_meta_total = 0;
            $contador = 0;
            
            // Para venta acumulada y meta total, tomamos el valor más reciente por sucursal
            $ventas_acumuladas_por_sucursal = [];
            $meta_total_por_sucursal = [];
            
            foreach ($resultados as $item) {
                $total_meta_dia += $item->meta_dia;
                $total_venta_dia += $item->venta_del_dia;
                $porcentaje_total += $item->porcentaje;
                $porcentaje_acumulado_total += $item->porcentaje_acumulado;
                $contador++;
                
                // Para venta acumulada y meta total, tomamos el valor más reciente por sucursal
                $key = $item->id_plaza . '-' . $item->clave_tienda;
                if (!isset($ventas_acumuladas_por_sucursal[$key]) || 
                    strtotime($item->fecha) > strtotime($ventas_acumuladas_por_sucursal[$key]['fecha'])) {
                    $ventas_acumuladas_por_sucursal[$key] = [
                        'fecha' => $item->fecha,
                        'venta_acumulada' => $item->venta_acumulada
                    ];
                    $meta_total_por_sucursal[$key] = $item->meta_total;
                }
            }
            
            // Sumar las ventas acumuladas de cada sucursal (última fecha)
            $total_venta_acumulada = 0;
            foreach ($ventas_acumuladas_por_sucursal as $sucursal) {
                $total_venta_acumulada += $sucursal['venta_acumulada'];
            }
            
            // Sumar meta total de cada sucursal
            $total_meta_total = array_sum($meta_total_por_sucursal);

            $estadisticas['total_meta_dia'] = $total_meta_dia;
            $estadisticas['total_venta_dia'] = $total_venta_dia;
            $estadisticas['total_venta_acumulada'] = $total_venta_acumulada;
            $estadisticas['total_meta_total'] = $total_meta_total;
            $estadisticas['porcentaje_promedio'] = $contador > 0 ? $porcentaje_total / $contador : 0;
            $estadisticas['porcentaje_acumulado_promedio'] = $contador > 0 ? $porcentaje_acumulado_total / $contador : 0;
            $estadisticas['total_registros'] = $contador;
        }

        return $estadisticas;
    }
}