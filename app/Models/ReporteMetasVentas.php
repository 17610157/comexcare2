<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReporteMetasVentas extends Model
{
    protected $table = 'metas';

    /**
     * Obtener datos del reporte con filtros - VERSIÓN CORREGIDA
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
        
        // Procesar en PHP para calcular acumulados CORRECTAMENTE
        $resultados = self::procesarAcumulados($datosMetas, $ventasDiarias);
        
        return $resultados;
    }

    /**
     * Obtener todas las ventas diarias del período
     */
    private static function obtenerVentasDiariasPeriodo($fecha_inicio, $fecha_fin, $plaza = '', $tienda = '')
    {
        // Obtener el primer día del mes de la fecha inicio
        $primer_dia_mes = date('Y-m-01', strtotime($fecha_inicio));
        
        $sql = "
            SELECT
                cplaza,
                ctienda as tienda,
                fecha,
                SUM((COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                    (COALESCE(vtacred, 0) - COALESCE(descred, 0))) as venta_dia
            FROM xcorte 
            WHERE fecha BETWEEN ? AND ?
        ";
        
        $params = [$primer_dia_mes, $fecha_fin];
        
        if (!empty($plaza)) {
            $sql .= " AND cplaza = ?";
            $params[] = $plaza;
        }
        
        if (!empty($tienda)) {
            $sql .= " AND tienda = ?";
            $params[] = $tienda;
        }
        
        $sql .= " GROUP BY cplaza, ctienda, fecha
                   ORDER BY cplaza, ctienda, fecha";
        
        return DB::select($sql, $params);
    }

    /**
     * Obtener datos de metas (con meta_total y meta_dia)
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
        
        $sql .= " ORDER BY bst.id_plaza, bst.clave_tienda, m.fecha";
        
        return DB::select($sql, $params);
    }

    /**
     * Procesar acumulados - VERSIÓN CORREGIDA
     */
    private static function procesarAcumulados($datosMetas, $ventasDiarias)
    {
        // Crear array indexado de ventas diarias para búsqueda rápida
        $ventasIndexadas = [];
        foreach ($ventasDiarias as $venta) {
            $key = $venta->cplaza . '|' . $venta->tienda . '|' . $venta->fecha;
            $ventasIndexadas[$key] = floatval($venta->venta_dia);
        }
        
        // Arrays para acumulados por sucursal
        $acumuladoVentasPorSucursal = [];
        $resultados = [];
        
        foreach ($datosMetas as $index => $meta) {
            // Crear clave para búsqueda rápida
            $key = $meta->id_plaza . '|' . $meta->clave_tienda . '|' . $meta->fecha;
            $sucursalKey = $meta->id_plaza . '|' . $meta->clave_tienda;
            
            // Obtener venta del día
            $venta_del_dia = $ventasIndexadas[$key] ?? 0;
            
            // Inicializar acumulado para esta sucursal si no existe
            if (!isset($acumuladoVentasPorSucursal[$sucursalKey])) {
                $acumuladoVentasPorSucursal[$sucursalKey] = 0;
            }
            
            // Acumular venta del día
            $acumuladoVentasPorSucursal[$sucursalKey] += $venta_del_dia;
            $venta_acumulada = $acumuladoVentasPorSucursal[$sucursalKey];
            
            // Calcular porcentaje del día (venta_del_dia / meta_dia)
            $porcentaje = ($meta->meta_dia > 0) ? ($venta_del_dia / $meta->meta_dia) * 100 : 0;
            
            // Calcular porcentaje acumulado (venta_acumulada / meta_total)
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
     * Obtener estadísticas del reporte - VERSIÓN CORREGIDA CON CÁLCULO DE TOTALES
     */
    public static function obtenerEstadisticas($resultados)
    {
        $estadisticas = [
            'total_meta_dia' => 0,
            'total_venta_dia' => 0,
            'total_venta_acumulada' => 0,
            'porcentaje_promedio' => 0,
            'porcentaje_acumulado_global' => 0, // NUEVO: % acumulado global
            'total_registros' => 0,
            'total_meta_total' => 0
        ];

        if (count($resultados) > 0) {
            $total_meta_dia = 0;
            $total_venta_dia = 0;
            $porcentaje_total = 0;
            $contador = 0;
            
            // Para acumulados y meta total, tomamos el valor más reciente por sucursal
            $acumulados_por_sucursal = [];
            $meta_total_por_sucursal = [];
            
            foreach ($resultados as $item) {
                $total_meta_dia += $item->meta_dia;
                $total_venta_dia += $item->venta_del_dia;
                $porcentaje_total += $item->porcentaje;
                $contador++;
                
                // Para venta acumulada, tomamos el valor más reciente por sucursal
                $key = $item->id_plaza . '-' . $item->clave_tienda;
                if (!isset($acumulados_por_sucursal[$key]) || 
                    strtotime($item->fecha) > strtotime($acumulados_por_sucursal[$key]['fecha'])) {
                    $acumulados_por_sucursal[$key] = [
                        'fecha' => $item->fecha,
                        'venta_acumulada' => $item->venta_acumulada
                    ];
                }
                
                // Para meta total, solo tomamos una vez por sucursal
                if (!isset($meta_total_por_sucursal[$key])) {
                    $meta_total_por_sucursal[$key] = $item->meta_total;
                }
            }
            
            // Sumar las ventas acumuladas de cada sucursal (última fecha)
            $total_venta_acumulada = 0;
            foreach ($acumulados_por_sucursal as $sucursal) {
                $total_venta_acumulada += $sucursal['venta_acumulada'];
            }
            
            // Sumar meta total de cada sucursal (sin duplicar)
            $total_meta_total = array_sum($meta_total_por_sucursal);

            // Calcular % acumulado GLOBAL: (Total Venta Acumulada / Total Meta Día) × 100
            $porcentaje_acumulado_global = ($total_meta_dia > 0) ? 
                ($total_venta_acumulada / $total_meta_dia) * 100 : 0;

            $estadisticas['total_meta_dia'] = $total_meta_dia;
            $estadisticas['total_venta_dia'] = $total_venta_dia;
            $estadisticas['total_venta_acumulada'] = $total_venta_acumulada;
            $estadisticas['total_meta_total'] = $total_meta_total;
            $estadisticas['porcentaje_promedio'] = $contador > 0 ? $porcentaje_total / $contador : 0;
            $estadisticas['porcentaje_acumulado_global'] = $porcentaje_acumulado_global; // Esto es lo que necesitas
            $estadisticas['total_registros'] = $contador;
        }

        return $estadisticas;
    }
}