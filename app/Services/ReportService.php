<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Obtener datos del reporte de vendedores con filtros - VERSIÓN ULTRA OPTIMIZADA CON CACHE
     */
    public static function getVendedoresReport(array $filtros): Collection
    {
        // Crear clave de cache única basada en los filtros
        $cacheKey = 'vendedores_report_' . md5(serialize($filtros));

        try {
            return Cache::remember($cacheKey, 3600, function () use ($filtros) { // Cache por 1 hora
            $fecha_inicio = str_replace('-', '', $filtros['fecha_inicio']);
            $fecha_fin = str_replace('-', '', $filtros['fecha_fin']);
            $plaza = $filtros['plaza'] ?? '';
            $tienda = $filtros['tienda'] ?? '';
            $vendedor = $filtros['vendedor'] ?? '';

            // Query ULTRA OPTIMIZADA: Subquery correlacionada más eficiente que CTE para PostgreSQL
            $sql = "
            SELECT
                c.ctienda || '-' || c.vend_clave AS tienda_vendedor,
                c.vend_clave || '-' || EXTRACT(DAY FROM TO_DATE(c.nota_fecha::text, 'YYYYMMDD')) AS vendedor_dia,
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

            $params = [$fecha_inicio, $fecha_fin];

            // Aplicar filtros
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

            // Procesar resultados usando collections para mejor rendimiento
            return collect($resultados_raw)->map(function ($row) {
                $fecha_str = (string)$row->nota_fecha;
                $fecha = strlen($fecha_str) == 8 ?
                    substr($fecha_str, 0, 4) . '-' . substr($fecha_str, 4, 2) . '-' . substr($fecha_str, 6, 2) :
                    $fecha_str;

                $venta_total = floatval($row->venta_total);
                $devolucion = floatval($row->devolucion);
                $venta_neta = $venta_total - $devolucion;

                // Ajustar vendedor_dia
                $vendedor_dia = $row->vendedor_dia;
                if (strpos($vendedor_dia, '-') !== false && strlen($fecha_str) == 8) {
                    $partes = explode('-', $vendedor_dia);
                    if (count($partes) == 2 && (strlen($partes[1]) == 0 || $partes[1] == '0' || $partes[1] == '1')) {
                        $dia = substr($fecha_str, 6, 2);
                        $vendedor_dia = $partes[0] . '-' . $dia;
                    }
                }

                return [
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
            });
        });
        } catch (\Exception $e) {
            // Si hay error de cache (ej: tabla no existe), ejecutar sin cache
            Log::warning('Error de cache en getVendedoresReport, ejecutando sin cache: ' . $e->getMessage());

            // Ejecutar la consulta sin cache
            $fecha_inicio = str_replace('-', '', $filtros['fecha_inicio']);
            $fecha_fin = str_replace('-', '', $filtros['fecha_fin']);
            $plaza = $filtros['plaza'] ?? '';
            $tienda = $filtros['tienda'] ?? '';
            $vendedor = $filtros['vendedor'] ?? '';

            // Query completamente optimizada usando CTE para pre-calcular devoluciones
            $sql = "
            WITH devoluciones_precalculadas AS (
                SELECT
                    v.f_emision,
                    v.clave_vend,
                    v.cplaza,
                    v.ctienda,
                    SUM(v.total_brut + v.impuesto) as devolucion_total
                FROM venta v
                INNER JOIN partvta p ON v.no_referen = p.no_referen
                    AND v.cplaza = p.cplaza
                    AND v.ctienda = p.ctienda
                WHERE v.tipo_doc = 'DV'
                  AND v.estado NOT LIKE '%C%'
                  AND p.clave_art NOT LIKE '%CAMBIODOC%'
                  AND p.totxpart IS NOT NULL
                  AND v.f_emision BETWEEN ? AND ?
            ";

            $params = [$fecha_inicio, $fecha_fin];

            // Aplicar filtros a la CTE para reducir el conjunto de datos desde el inicio
            if (!empty($plaza)) {
                $sql .= " AND v.cplaza = ?";
                $params[] = $plaza;
            }
            if (!empty($tienda)) {
                $sql .= " AND v.ctienda = ?";
                $params[] = $tienda;
            }
            if (!empty($vendedor)) {
                $sql .= " AND v.clave_vend = ?";
                $params[] = $vendedor;
            }

            $sql .= "
                GROUP BY v.f_emision, v.clave_vend, v.cplaza, v.ctienda
            )
            SELECT
                c.ctienda || '-' || c.vend_clave AS tienda_vendedor,
                c.vend_clave || '-' || EXTRACT(DAY FROM TO_DATE(c.nota_fecha::text, 'YYYYMMDD')) AS vendedor_dia,
                CASE
                    WHEN c.ctienda IN ('T0014', 'T0017', 'T0031') THEN 'MANZA'
                    WHEN c.vend_clave = '14379' THEN 'MANZA'
                    ELSE c.cplaza
                END AS plaza_ajustada,
                c.ctienda,
                c.vend_clave,
                c.nota_fecha,
                SUM(c.nota_impor) AS venta_total,
                COALESCE(d.devolucion_total, 0) AS devolucion
            FROM canota c
            LEFT JOIN devoluciones_precalculadas d ON d.f_emision = c.nota_fecha
                AND d.clave_vend = c.vend_clave
                AND d.cplaza = c.cplaza
                AND d.ctienda = c.ctienda
            WHERE c.ban_status <> 'C'
              AND c.nota_fecha BETWEEN ? AND ?
              AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
              AND c.ctienda NOT LIKE '%DESC%'
              AND c.ctienda NOT LIKE '%CEDI%'
            ";

            // Agregar los mismos parámetros para el rango de fechas en la consulta principal
            $params = array_merge($params, [$fecha_inicio, $fecha_fin]);

            // Aplicar filtros adicionales a la consulta principal
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

            $sql .= " GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave, d.devolucion_total
                      ORDER BY c.ctienda || '-' || c.vend_clave,
                               c.vend_clave || '-' || TO_CHAR(TO_DATE(c.nota_fecha::text, 'YYYYMMDD'), 'DD')";

            $resultados_raw = DB::select($sql, $params);

            // Procesar resultados usando collections para mejor rendimiento
            return collect($resultados_raw)->map(function ($row) {
                $fecha_str = (string)$row->nota_fecha;
                $fecha = strlen($fecha_str) == 8 ?
                    substr($fecha_str, 0, 4) . '-' . substr($fecha_str, 4, 2) . '-' . substr($fecha_str, 6, 2) :
                    $fecha_str;

                $venta_total = floatval($row->venta_total);
                $devolucion = floatval($row->devolucion);
                $venta_neta = $venta_total - $devolucion;

                // Ajustar vendedor_dia
                $vendedor_dia = $row->vendedor_dia;
                if (strpos($vendedor_dia, '-') !== false && strlen($fecha_str) == 8) {
                    $partes = explode('-', $vendedor_dia);
                    if (count($partes) == 2 && (strlen($partes[1]) == 0 || $partes[1] == '0' || $partes[1] == '1')) {
                        $dia = substr($fecha_str, 6, 2);
                        $vendedor_dia = $partes[0] . '-' . $dia;
                    }
                }

                return [
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
            });
        }
    }

    /**
     * Obtener datos del reporte matricial de vendedores - VERSIÓN OPTIMIZADA
     */
    public static function getVendedoresMatricialReport(array $filtros): array
    {
        // Crear clave de cache para reportes matriciales
        $cacheKey = 'vendedores_matricial_report_' . md5(serialize($filtros));

        try {
            return Cache::remember($cacheKey, 3600, function () use ($filtros) {
            $fecha_inicio = $filtros['fecha_inicio'];
            $fecha_fin = $filtros['fecha_fin'];
            $plaza = $filtros['plaza'] ?? '';
            $tienda = $filtros['tienda'] ?? '';
            $vendedor = $filtros['vendedor'] ?? '';

            // Query ULTRA OPTIMIZADA: Subquery correlacionada para evitar problemas de GROUP BY
            $sql = "
            SELECT
                c.vend_clave,
                a.nombre,
                a.tipo,
                c.ctienda,
                c.cplaza,
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
            JOIN asesores_vvt a ON (a.plaza = c.cplaza AND a.asesor = c.vend_clave)
            WHERE c.ban_status <> 'C'
              AND c.nota_fecha BETWEEN ? AND ?
              AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
              AND c.ctienda NOT LIKE '%DESC%'
              AND c.ctienda NOT LIKE '%CEDI%'
            ";

            $params = [$fecha_inicio, $fecha_fin];

            // Aplicar filtros
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

            $sql .= " GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave, a.nombre, a.tipo
                      ORDER BY c.vend_clave, c.nota_fecha";

            $resultados_raw = DB::select($sql, $params);

            // Procesar datos matriciales
            return self::procesarDatosMatriciales($resultados_raw, $fecha_inicio, $fecha_fin);
        });
        } catch (\Exception $e) {
            // Si hay error de cache, ejecutar sin cache
            Log::warning('Error de cache en getVendedoresMatricialReport, ejecutando sin cache: ' . $e->getMessage());

            // Ejecutar la consulta sin cache
            $fecha_inicio = $filtros['fecha_inicio'];
            $fecha_fin = $filtros['fecha_fin'];
            $plaza = $filtros['plaza'] ?? '';
            $tienda = $filtros['tienda'] ?? '';
            $vendedor = $filtros['vendedor'] ?? '';

            // Query ULTRA OPTIMIZADA: Subquery correlacionada para evitar problemas de GROUP BY
            $sql = "
            SELECT
                c.vend_clave,
                a.nombre,
                a.tipo,
                c.ctienda,
                c.cplaza,
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
            JOIN asesores_vvt a ON (a.plaza = c.cplaza AND a.asesor = c.vend_clave)
            WHERE c.ban_status <> 'C'
              AND c.nota_fecha BETWEEN ? AND ?
              AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
              AND c.ctienda NOT LIKE '%DESC%'
              AND c.ctienda NOT LIKE '%CEDI%'
            ";

            $params = [$fecha_inicio, $fecha_fin];

            // Aplicar filtros
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

            $sql .= " GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave, a.nombre, a.tipo
                      ORDER BY c.vend_clave, c.nota_fecha";

            $resultados_raw = DB::select($sql, $params);

            // Procesar datos matriciales
            return self::procesarDatosMatriciales($resultados_raw, $fecha_inicio, $fecha_fin);
        }
    }

    /**
     * Procesar datos para vista matricial
     */
    private static function procesarDatosMatriciales($resultados_raw, $fecha_inicio, $fecha_fin): array
    {
        $vendedores_info = [];
        $dias = [];

        // Crear array de días
        $start = new \DateTime($fecha_inicio);
        $end = new \DateTime($fecha_fin);
        $end->modify('+1 day');
        $interval = new \DateInterval('P1D');
        $dateRange = new \DatePeriod($start, $interval, $end);

        foreach ($dateRange as $date) {
            $dia_key = $date->format('Ymd');
            $dias[$dia_key] = $date->format('Y-m-d');
        }

        foreach ($resultados_raw as $row) {
            $vendedor_id = $row->vend_clave;
            $nombre = $row->nombre;
            $tipo = $row->tipo;
            $tienda_val = $row->ctienda;
            $plaza_val = $row->cplaza;
            $fecha_key = $row->nota_fecha;

            if (strlen($fecha_key) == 8) {
                $fecha_key = $fecha_key;
            } else {
                $fecha_key = str_replace('-', '', $fecha_key);
            }

            if (!isset($vendedores_info[$vendedor_id])) {
                $vendedores_info[$vendedor_id] = [
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'tiendas' => [],
                    'plazas' => [],
                    'ventas' => []
                ];
            }

            if (!in_array($tienda_val, $vendedores_info[$vendedor_id]['tiendas'])) {
                $vendedores_info[$vendedor_id]['tiendas'][] = $tienda_val;
            }

            if (!in_array($plaza_val, $vendedores_info[$vendedor_id]['plazas'])) {
                $vendedores_info[$vendedor_id]['plazas'][] = $plaza_val;
            }

            $venta_total = floatval($row->venta_total);
            $devolucion = floatval($row->devolucion);
            $venta_neta = $venta_total - $devolucion;

            if (!isset($vendedores_info[$vendedor_id]['ventas'][$fecha_key])) {
                $vendedores_info[$vendedor_id]['ventas'][$fecha_key] = 0;
            }
            $vendedores_info[$vendedor_id]['ventas'][$fecha_key] += $venta_neta;
        }

        return [
            'vendedores_info' => $vendedores_info,
            'dias' => $dias
        ];
    }

    /**
     * Calcular estadísticas para reporte de vendedores - OPTIMIZADO
     */
    public static function calcularEstadisticasVendedores(Collection $resultados): array
    {
        // Usar chunking para manejar grandes datasets sin consumir toda la memoria
        $estadisticas = [
            'total_ventas' => 0,
            'total_devoluciones' => 0,
            'total_neto' => 0,
            'total_registros' => $resultados->count()
        ];

        // Procesar en chunks de 1000 registros para evitar memory exhaustion
        $resultados->chunk(1000)->each(function ($chunk) use (&$estadisticas) {
            $estadisticas['total_ventas'] += $chunk->sum('venta_total');
            $estadisticas['total_devoluciones'] += $chunk->sum('devolucion');
            $estadisticas['total_neto'] += $chunk->sum('venta_neta');
        });

        return $estadisticas;
    }

    /**
     * Procesar datos en chunks para evitar memory issues
     */
    public static function procesarEnChunks(Collection $datos, callable $callback, int $chunkSize = 1000): void
    {
        $datos->chunk($chunkSize)->each(function ($chunk) use ($callback) {
            $callback($chunk);
        });
    }

    /**
     * Obtener datos del reporte de metas de ventas
     */
    public static function getMetasVentasReport(array $filtros): array
    {
        $cacheKey = 'metas_ventas_report_' . md5(serialize($filtros));

        try {
            return Cache::remember($cacheKey, 3600, function () use ($filtros) {
                // Usar el modelo existente que ya está optimizado
                $resultados = \App\Models\ReporteMetasVentas::obtenerReporte($filtros);
                $estadisticas = \App\Models\ReporteMetasVentas::obtenerEstadisticas($resultados);

                return [
                    'resultados' => $resultados,
                    'estadisticas' => $estadisticas
                ];
            });
        } catch (\Exception $e) {
            Log::warning('Error de cache en getMetasVentasReport, ejecutando sin cache: ' . $e->getMessage());

            // Ejecutar sin cache
            $resultados = \App\Models\ReporteMetasVentas::obtenerReporte($filtros);
            $estadisticas = \App\Models\ReporteMetasVentas::obtenerEstadisticas($resultados);

            return [
                'resultados' => $resultados,
                'estadisticas' => $estadisticas
            ];
        }
    }

    /**
     * Obtener venta acumulada para API
     */
    public static function getVentaAcumulada(string $fecha, string $plaza = '', string $tienda = ''): array
    {
        $cacheKey = 'venta_acumulada_' . md5($fecha . $plaza . $tienda);

        try {
            return Cache::remember($cacheKey, 1800, function () use ($fecha, $plaza, $tienda) {
                // Obtener el primer día del mes
                $primer_dia_mes = date('Y-m-01', strtotime($fecha));

                $sql = "
                    SELECT
                        cplaza,
                        tienda,
                        SUM(
                            (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                            (COALESCE(vtacred, 0) - COALESCE(descred, 0))
                        ) AS venta_acumulada_mes
                    FROM xcorte
                    WHERE fecha BETWEEN ? AND ?
                ";

                $params = [$primer_dia_mes, $fecha];

                if (!empty($plaza)) {
                    $sql .= " AND cplaza = ?";
                    $params[] = $plaza;
                }

                if (!empty($tienda)) {
                    $sql .= " AND tienda = ?";
                    $params[] = $tienda;
                }

                $sql .= " GROUP BY cplaza, tienda";

                $resultados = DB::select($sql, $params);

                return [
                    'success' => true,
                    'fecha' => $fecha,
                    'primer_dia_mes' => $primer_dia_mes,
                    'data' => $resultados,
                    'total_acumulado' => array_sum(array_column($resultados, 'venta_acumulada_mes'))
                ];
            });
        } catch (\Exception $e) {
            Log::warning('Error de cache en getVentaAcumulada, ejecutando sin cache: ' . $e->getMessage());

            // Ejecutar sin cache
            $primer_dia_mes = date('Y-m-01', strtotime($fecha));

            $sql = "
                SELECT
                    cplaza,
                    tienda,
                    SUM(
                        (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                        (COALESCE(vtacred, 0) - COALESCE(descred, 0))
                    ) AS venta_acumulada_mes
                FROM xcorte
                WHERE fecha BETWEEN ? AND ?
            ";

            $params = [$primer_dia_mes, $fecha];

            if (!empty($plaza)) {
                $sql .= " AND cplaza = ?";
                $params[] = $plaza;
            }

            if (!empty($tienda)) {
                $sql .= " AND tienda = ?";
                $params[] = $tienda;
            }

            $sql .= " GROUP BY cplaza, tienda";

            $resultados = DB::select($sql, $params);

            return [
                'success' => true,
                'fecha' => $fecha,
                'primer_dia_mes' => $primer_dia_mes,
                'data' => $resultados,
                'total_acumulado' => array_sum(array_column($resultados, 'venta_acumulada_mes'))
            ];
        }
    }

    /**
     * Limpiar cache de reportes
     */
    public static function limpiarCacheReportes(): void
    {
        try {
            Cache::flush(); // Limpiar toda la cache (o usar tags específicos)
        } catch (\Exception $e) {
            Log::warning('Error al limpiar cache: ' . $e->getMessage());
            // Continuar sin cache si hay error
        }
    }

    /**
     * Obtener configuración de memoria y tiempo de ejecución
     */
    public static function optimizarConfiguracion(): void
    {
        // Aumentar límites para procesamiento de reportes grandes
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300); // 5 minutos máximo
    }
}