<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Obtener datos del reporte de vendedores con filtros - USANDO TABLA CACHE
     */
    public static function getVendedoresReport(array $filtros): Collection
    {
        // Crear clave de cache única basada en los filtros
        $cacheKey = 'vendedores_report_'.md5(serialize($filtros));

        try {
            return Cache::remember($cacheKey, 3600, function () use ($filtros) { // Cache por 1 hora
                $fecha_inicio = $filtros['fecha_inicio'];
                $fecha_fin = $filtros['fecha_fin'];
                $plaza = $filtros['plaza'] ?? '';
                $tienda = $filtros['tienda'] ?? '';
                $vendedor = $filtros['vendedor'] ?? '';

                $query = DB::table('vendedores_cache')
                    ->select([
                        'ctienda',
                        'vend_clave',
                        'nota_fecha',
                        'plaza_ajustada',
                        'tienda_vendedor',
                        'vendedor_dia',
                        'venta_total',
                        'devolucion',
                        'venta_neta'
                    ])
                    ->whereBetween('nota_fecha', [$fecha_inicio, $fecha_fin]);

                // Aplicar filtros
                if (! empty($plaza)) {
                    $plazasArray = explode(',', $plaza);
                    $query->whereIn('cplaza', $plazasArray);
                }
                if (! empty($tienda)) {
                    $tiendasArray = explode(',', $tienda);
                    $query->whereIn('ctienda', $tiendasArray);
                }
                if (! empty($vendedor)) {
                    $query->where('vend_clave', $vendedor);
                }

                $query->orderBy('ctienda')
                    ->orderBy('vend_clave')
                    ->orderBy('nota_fecha');

                $resultados_raw = $query->get();

                // Procesar resultados
                return collect($resultados_raw)->map(function ($row) {
                    $fecha = $row->nota_fecha;
                    $venta_total = floatval($row->venta_total);
                    $devolucion = floatval($row->devolucion);
                    $venta_neta = floatval($row->venta_neta);

                    return [
                        'tienda_vendedor' => $row->tienda_vendedor,
                        'vendedor_dia' => $row->vendedor_dia,
                        'plaza_ajustada' => $row->plaza_ajustada,
                        'ctienda' => $row->ctienda,
                        'vend_clave' => $row->vend_clave,
                        'fecha' => $fecha,
                        'venta_total' => $venta_total,
                        'devolucion' => $devolucion,
                        'venta_neta' => $venta_neta,
                    ];
                });
            });
        } catch (\Exception $e) {
            Log::error('Error en getVendedoresReport con cache: '.$e->getMessage());
            return collect([]);
        }
    }

    /**
     * Obtener datos del reporte matricial de vendedores - USANDO TABLA CACHE
     */
    public static function getVendedoresMatricialReport(array $filtros): array
    {
        $cacheKey = 'vendedores_matricial_report_'.md5(serialize($filtros));

        try {
            return Cache::remember($cacheKey, 3600, function () use ($filtros) {
                $fecha_inicio = $filtros['fecha_inicio'];
                $fecha_fin = $filtros['fecha_fin'];
                $plaza = $filtros['plaza'] ?? '';
                $tienda = $filtros['tienda'] ?? '';
                $vendedor = $filtros['vendedor'] ?? '';

                $query = DB::table('vendedores_cache')
                    ->select([
                        'cplaza',
                        'ctienda',
                        'vend_clave',
                        'nota_fecha',
                        'plaza_ajustada',
                        'tienda_vendedor',
                        'vendedor_dia',
                        'venta_total',
                        'devolucion',
                        'venta_neta'
                    ])
                    ->whereBetween('nota_fecha', [$fecha_inicio, $fecha_fin]);

                if (! empty($plaza)) {
                    $plazasArray = explode(',', $plaza);
                    $query->whereIn('cplaza', $plazasArray);
                }
                if (! empty($tienda)) {
                    $tiendasArray = explode(',', $tienda);
                    $query->whereIn('ctienda', $tiendasArray);
                }
                if (! empty($vendedor)) {
                    $query->where('vend_clave', $vendedor);
                }

                $resultados = $query->get();

                return self::procesarDatosMatricialesDesdeCache($resultados, $fecha_inicio, $fecha_fin);
            });
        } catch (\Exception $e) {
            Log::error('Error en getVendedoresMatricialReport: '.$e->getMessage());
            return [];
        }
    }

    /**
     * Procesar datos para vista matricial desde cache
     */
    private static function procesarDatosMatricialesDesdeCache($resultados_raw, $fecha_inicio, $fecha_fin): array
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
            $dia_key = $date->format('Y-m-d');
            $dias[$dia_key] = $dia_key;
        }

        foreach ($resultados_raw as $row) {
            $vendedor_id = $row->vend_clave;
            $tienda_val = $row->ctienda;
            $plaza_val = $row->cplaza;
            $fecha_key = $row->nota_fecha;

            if (! isset($vendedores_info[$vendedor_id])) {
                $vendedores_info[$vendedor_id] = [
                    'nombre' => '',
                    'tipo' => '',
                    'tiendas' => [],
                    'plazas' => [],
                    'ventas' => [],
                ];
            }

            if (! in_array($tienda_val, $vendedores_info[$vendedor_id]['tiendas'])) {
                $vendedores_info[$vendedor_id]['tiendas'][] = $tienda_val;
            }

            if (! in_array($plaza_val, $vendedores_info[$vendedor_id]['plazas'])) {
                $vendedores_info[$vendedor_id]['plazas'][] = $plaza_val;
            }

            $venta_total = floatval($row->venta_total);
            $devolucion = floatval($row->devolucion);
            $venta_neta = $venta_total - $devolucion;

            if (! isset($vendedores_info[$vendedor_id]['ventas'][$fecha_key])) {
                $vendedores_info[$vendedor_id]['ventas'][$fecha_key] = 0;
            }
            $vendedores_info[$vendedor_id]['ventas'][$fecha_key] += $venta_neta;
        }

        return [
            'vendedores_info' => $vendedores_info,
            'dias' => $dias,
        ];
    }

    /**
            if (! empty($plaza)) {
                $sql .= ' AND c.cplaza = ?';
                $params[] = $plaza;
            }
            if (! empty($tienda)) {
                $sql .= ' AND c.ctienda = ?';
                $params[] = $tienda;
            }
            if (! empty($vendedor)) {
                $sql .= ' AND c.vend_clave = ?';
                $params[] = $vendedor;
            }

            $sql .= ' GROUP BY c.nota_fecha, c.cplaza, c.ctienda, c.vend_clave, a.nombre, a.tipo
                      ORDER BY c.vend_clave, c.nota_fecha';

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

            if (! isset($vendedores_info[$vendedor_id])) {
                $vendedores_info[$vendedor_id] = [
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'tiendas' => [],
                    'plazas' => [],
                    'ventas' => [],
                ];
            }

            if (! in_array($tienda_val, $vendedores_info[$vendedor_id]['tiendas'])) {
                $vendedores_info[$vendedor_id]['tiendas'][] = $tienda_val;
            }

            if (! in_array($plaza_val, $vendedores_info[$vendedor_id]['plazas'])) {
                $vendedores_info[$vendedor_id]['plazas'][] = $plaza_val;
            }

            $venta_total = floatval($row->venta_total);
            $devolucion = floatval($row->devolucion);
            $venta_neta = $venta_total - $devolucion;

            if (! isset($vendedores_info[$vendedor_id]['ventas'][$fecha_key])) {
                $vendedores_info[$vendedor_id]['ventas'][$fecha_key] = 0;
            }
            $vendedores_info[$vendedor_id]['ventas'][$fecha_key] += $venta_neta;
        }

        return [
            'vendedores_info' => $vendedores_info,
            'dias' => $dias,
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
            'total_registros' => $resultados->count(),
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
        $cacheKey = 'metas_ventas_report_'.md5(serialize($filtros));

        try {
            return Cache::remember($cacheKey, 3600, function () use ($filtros) {
                // Usar el modelo existente que ya está optimizado
                $resultados = \App\Models\ReporteMetasVentas::obtenerReporte($filtros);
                $estadisticas = \App\Models\ReporteMetasVentas::obtenerEstadisticas($resultados);

                return [
                    'resultados' => $resultados,
                    'estadisticas' => $estadisticas,
                ];
            });
        } catch (\Exception $e) {
            Log::warning('Error de cache en getMetasVentasReport, ejecutando sin cache: '.$e->getMessage());

            // Ejecutar sin cache
            $resultados = \App\Models\ReporteMetasVentas::obtenerReporte($filtros);
            $estadisticas = \App\Models\ReporteMetasVentas::obtenerEstadisticas($resultados);

            return [
                'resultados' => $resultados,
                'estadisticas' => $estadisticas,
            ];
        }
    }

    /**
     * Obtener venta acumulada para API
     */
    public static function getVentaAcumulada(string $fecha, string $plaza = '', string $tienda = ''): array
    {
        $cacheKey = 'venta_acumulada_'.md5($fecha.$plaza.$tienda);

        try {
            return Cache::remember($cacheKey, 1800, function () use ($fecha, $plaza, $tienda) {
                // Obtener el primer día del mes
                $primer_dia_mes = date('Y-m-01', strtotime($fecha));

                $sql = '
                    SELECT
                        cplaza,
                        tienda,
                        SUM(
                            (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                            (COALESCE(vtacred, 0) - COALESCE(descred, 0))
                        ) AS venta_acumulada_mes
                    FROM xcorte
                    WHERE fecha BETWEEN ? AND ?
                ';

                $params = [$primer_dia_mes, $fecha];

                if (! empty($plaza)) {
                    $sql .= ' AND cplaza = ?';
                    $params[] = $plaza;
                }

                if (! empty($tienda)) {
                    $sql .= ' AND tienda = ?';
                    $params[] = $tienda;
                }

                $sql .= ' GROUP BY cplaza, tienda';

                $resultados = DB::select($sql, $params);

                return [
                    'success' => true,
                    'fecha' => $fecha,
                    'primer_dia_mes' => $primer_dia_mes,
                    'data' => $resultados,
                    'total_acumulado' => array_sum(array_column($resultados, 'venta_acumulada_mes')),
                ];
            });
        } catch (\Exception $e) {
            Log::warning('Error de cache en getVentaAcumulada, ejecutando sin cache: '.$e->getMessage());

            // Ejecutar sin cache
            $primer_dia_mes = date('Y-m-01', strtotime($fecha));

            $sql = '
                SELECT
                    cplaza,
                    tienda,
                    SUM(
                        (COALESCE(vtacont, 0) - COALESCE(descont, 0)) +
                        (COALESCE(vtacred, 0) - COALESCE(descred, 0))
                    ) AS venta_acumulada_mes
                FROM xcorte
                WHERE fecha BETWEEN ? AND ?
            ';

            $params = [$primer_dia_mes, $fecha];

            if (! empty($plaza)) {
                $sql .= ' AND cplaza = ?';
                $params[] = $plaza;
            }

            if (! empty($tienda)) {
                $sql .= ' AND tienda = ?';
                $params[] = $tienda;
            }

            $sql .= ' GROUP BY cplaza, tienda';

            $resultados = DB::select($sql, $params);

            return [
                'success' => true,
                'fecha' => $fecha,
                'primer_dia_mes' => $primer_dia_mes,
                'data' => $resultados,
                'total_acumulado' => array_sum(array_column($resultados, 'venta_acumulada_mes')),
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
            Log::warning('Error al limpiar cache: '.$e->getMessage());
            // Continuar sin cache si hay error
        }
    }

    /**
     * Reporte Metas Matricial - NUEVO REPORTE
     */
    public static function getMetasMatricialReport(array $filtros): array
    {
        $cacheKey = 'metas_matricial_report_'.md5(serialize($filtros));

        try {
            return Cache::remember($cacheKey, 3600, function () use ($filtros) {
                return self::procesarMetasMatricial($filtros);
            });
        } catch (\Exception $e) {
            Log::warning('Error de cache en getMetasMatricialReport, ejecutando sin cache: '.$e->getMessage());

            return self::procesarMetasMatricial($filtros);
        }
    }

    private static function procesarMetasMatricial(array $filtros): array
    {
        $fecha_inicio = $filtros['fecha_inicio'];
        $fecha_fin = $filtros['fecha_fin'];
        $plaza = $filtros['plaza'] ?? '';
        $tienda = $filtros['tienda'] ?? '';
        $zona = $filtros['zona'] ?? '';

        // Consulta SQL optimizada
        $sql = "
        SELECT
            xc.fecha,
            xc.cplaza,
            xc.ctienda,
            xc.ctienda as tienda,
            m.valor_dia,
            m.meta_dia,
            m.meta_total,
            m.dias_total,
            bst.zona,
            ((COALESCE(xc.vtacont, 0) - COALESCE(xc.descont, 0)) +
             (COALESCE(xc.vtacred, 0) - COALESCE(xc.descred, 0))) as total,
            (COALESCE(xc.vtacont, 0) - COALESCE(xc.descont, 0)) as venta_contado,
            (COALESCE(xc.vtacred, 0) - COALESCE(xc.descred, 0)) as venta_credito,
            CASE
                WHEN m.meta_dia > 0 THEN
                    (((COALESCE(xc.vtacont, 0) - COALESCE(xc.descont, 0)) +
                      (COALESCE(xc.vtacred, 0) - COALESCE(xc.descred, 0))) / m.meta_dia) * 100
                ELSE 0
            END AS porcentaje
        FROM xcorte xc
        LEFT JOIN bi_sys_tiendas bst ON xc.ctienda = bst.clave_tienda OR xc.ctienda LIKE bst.clave_tienda || '%' OR xc.ctienda = bst.clave_alterna
        LEFT JOIN metas m ON TRIM(COALESCE(bst.clave_tienda, xc.ctienda)) = TRIM(m.tienda) AND xc.fecha = m.fecha
        WHERE xc.ctienda NOT IN ('ALMAC','BODEG','CXVEA','GALMA','B0001','00027')
          AND xc.ctienda NOT LIKE '%DESC%'
          AND xc.ctienda NOT LIKE '%CEDI%'
          AND xc.fecha BETWEEN ? AND ?
        ";

        // Obtener suma_valor_dia por tienda para el rango completo
        $sumaValorDiaQuery = '
        SELECT tienda, SUM(valor_dia) as suma_valor_dia
        FROM metas
        WHERE fecha BETWEEN ? AND ?
        GROUP BY tienda
        ';
        $sumaValorDiaData = DB::select($sumaValorDiaQuery, [$fecha_inicio, $fecha_fin]);
        $sumasValorDia = [];
        foreach ($sumaValorDiaData as $row) {
            $sumasValorDia[$row->tienda] = $row->suma_valor_dia;
        }

        $params = [$fecha_inicio, $fecha_fin];

        // Aplicar filtros
        if (! empty($plaza)) {
            $sql .= ' AND xc.cplaza = ?';
            $params[] = $plaza;
        }
        if (! empty($tienda)) {
            $sql .= ' AND xc.tienda = ?';
            $params[] = $tienda;
        }
        if (! empty($zona)) {
            $sql .= ' AND bst.zona = ?';
            $params[] = $zona;
        }

        $sql .= ' ORDER BY xc.cplaza, bst.zona, xc.ctienda, xc.fecha';

        $rawData = DB::select($sql, $params);

        // Procesar datos jerárquicos
        return self::construirMatrizJerarquica($rawData, $fecha_inicio, $fecha_fin, $sumasValorDia);
    }

    private static function construirMatrizJerarquica($rawData, $fecha_inicio, $fecha_fin, $sumasValorDia): array
    {
        $fechas = self::generarRangoFechas($fecha_inicio, $fecha_fin);
        $matriz = [];
        $tiendas = [];

        // Procesar datos por tienda
        foreach ($rawData as $row) {
            $tienda = $row->tienda;

            if (! in_array($tienda, $tiendas)) {
                $tiendas[] = $tienda;

                // Información básica de la tienda
                $zona = $row->zona;
                if (empty($zona)) {
                    $zona = $row->cplaza; // Si no hay zona, usar plaza como zona
                }
                $matriz['info'][$tienda] = [
                    'plaza' => $row->cplaza,
                    'zona' => $zona,
                    'tienda' => $tienda,
                    'meta_total' => $row->meta_total,
                    'dias_totales' => $row->dias_total,
                    'suma_valor_dia' => $sumasValorDia[$row->clave_tienda ?? $tienda] ?? 0,
                ];

                // Debug: si suma_valor_dia es 0, intentar con clave alternativa
                if (($sumasValorDia[$tienda][$row->cplaza] ?? 0) == 0) {
                    // Verificar si hay con plaza como string o algo
                    // Por ahora, dejar como está
                }
            }

            // Datos por fecha
            $fechaKey = $row->fecha;
            if (in_array($fechaKey, $fechas)) {
                $matriz['datos'][$tienda][$fechaKey] = [
                    'total' => $row->total,
                    'venta_contado' => $row->venta_contado,
                    'venta_credito' => $row->venta_credito,
                    'meta_dia' => $row->meta_dia,
                    'porcentaje' => $row->porcentaje,
                ];
            }
        }

        // Asegurar que matriz['info'] y matriz['datos'] existan aunque no haya datos
        if (! isset($matriz['info'])) {
            $matriz['info'] = [];
        }
        if (! isset($matriz['datos'])) {
            $matriz['datos'] = [];
        }
        if (! isset($matriz['totales'])) {
            $matriz['totales'] = [];
        }

        // Calcular totales por tienda
        foreach ($tiendas as $tienda) {
            $totalVentas = 0;
            foreach ($fechas as $fecha) {
                $totalVentas += $matriz['datos'][$tienda][$fecha]['total'] ?? 0;
            }
            $meta_total = $matriz['info'][$tienda]['meta_total'];
            $dias_totales_meta = $matriz['info'][$tienda]['dias_totales'] ?? count($fechas);
            $suma_valor_dia = $matriz['info'][$tienda]['suma_valor_dia'];
            $objetivo = $dias_totales_meta > 0 ? ($meta_total / $dias_totales_meta) * $suma_valor_dia : 0;
            $matriz['totales'][$tienda] = [
                'total' => $totalVentas,
                'objetivo' => $objetivo,
                'porcentaje_total' => $objetivo > 0 ? ($totalVentas / $objetivo) * 100 : 0,
                'meta_total' => $meta_total,
            ];
        }

        // Calcular suma diaria total (suma de todas las tiendas por fecha)
        $suma_diaria = [];
        foreach ($fechas as $fecha) {
            $suma = 0;
            foreach ($tiendas as $tienda) {
                $suma += $matriz['datos'][$tienda][$fecha]['total'] ?? 0;
            }
            $suma_diaria[$fecha] = $suma;
        }

        // Calcular totales por zona
        $totales_zona = [];
        if (! empty($tiendas) && ! empty($matriz['info'])) {
            $zonas = array_unique(array_column($matriz['info'], 'zona'));
            foreach ($zonas as $zona) {
                $tiendas_zona = array_filter($tiendas, fn ($t) => $matriz['info'][$t]['zona'] === $zona);
                $total = 0;
                $objetivo = 0;
                $meta_total = 0;
                $suma_valor_dia = 0;
                foreach ($tiendas_zona as $tienda) {
                    $total += $matriz['totales'][$tienda]['total'] ?? 0;
                    $objetivo += $matriz['totales'][$tienda]['objetivo'] ?? 0;
                    $meta_total += $matriz['info'][$tienda]['meta_total'] ?? 0;
                    $suma_valor_dia += $matriz['info'][$tienda]['suma_valor_dia'] ?? 0;
                }
                $totales_zona[$zona] = [
                    'total' => $total,
                    'objetivo' => $objetivo,
                    'porcentaje_total' => $objetivo > 0 ? ($total / $objetivo) * 100 : 0,
                    'meta_total' => $meta_total,
                    'suma_valor_dia' => $suma_valor_dia,
                    'dias_totales' => $matriz['info'][reset($tiendas_zona)]['dias_totales'] ?? count($fechas),
                    'datos_diarios' => [],
                ];
                // Datos diarios por zona
                foreach ($fechas as $fecha) {
                    $suma_zona_fecha = 0;
                    foreach ($tiendas_zona as $tienda) {
                        $suma_zona_fecha += $matriz['datos'][$tienda][$fecha]['total'] ?? 0;
                    }
                    $totales_zona[$zona]['datos_diarios'][$fecha] = $suma_zona_fecha;
                }
            }
        }

        // Calcular totales por plaza
        $plazas = array_unique(array_column($matriz['info'], 'plaza'));
        $totales_plaza = [];
        foreach ($plazas as $plaza) {
            $zonas_plaza = array_unique(array_column(array_filter($matriz['info'], fn ($info) => $info['plaza'] === $plaza), 'zona'));
            $total = 0;
            $objetivo = 0;
            $meta_total = 0;
            $suma_valor_dia = 0;
            foreach ($zonas_plaza as $zona) {
                $total += $totales_zona[$zona]['total'] ?? 0;
                $objetivo += $totales_zona[$zona]['objetivo'] ?? 0;
                $meta_total += $totales_zona[$zona]['meta_total'] ?? 0;
                $suma_valor_dia += $totales_zona[$zona]['suma_valor_dia'] ?? 0;
            }
            $totales_plaza[$plaza] = [
                'total' => $total,
                'objetivo' => $objetivo,
                'porcentaje_total' => $objetivo > 0 ? ($total / $objetivo) * 100 : 0,
                'meta_total' => $meta_total,
                'suma_valor_dia' => $suma_valor_dia,
                'dias_totales' => $totales_zona[reset($zonas_plaza)]['dias_totales'] ?? count($fechas),
                'datos_diarios' => [],
            ];
            // Datos diarios por plaza
            foreach ($fechas as $fecha) {
                $suma_plaza_fecha = 0;
                foreach ($zonas_plaza as $zona) {
                    $suma_plaza_fecha += $totales_zona[$zona]['datos_diarios'][$fecha] ?? 0;
                }
                $totales_plaza[$plaza]['datos_diarios'][$fecha] = $suma_plaza_fecha;
            }
        }

        return [
            'fechas' => $fechas,
            'tiendas' => $tiendas,
            'matriz' => $matriz,
            'suma_diaria' => $suma_diaria,
            'dias_totales' => count($fechas),
            'totales_zona' => $totales_zona,
            'totales_plaza' => $totales_plaza,
        ];
    }

    private static function generarRangoFechas($inicio, $fin): array
    {
        $fechas = [];
        $current = strtotime($inicio);
        $end = strtotime($fin);

        while ($current <= $end) {
            $fechas[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        return $fechas;
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
