<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReporteMetasVentas extends Model
{
    protected $table = 'metas';

    private $tiendasExcluidas = [
        'ALMAC', 'BODEG', 'ALTAP', 'CXVEA', '00095', 'GALMA', 'B0001', '00027', 'BOVER',
        '2ORIE', 'ALMAP', 'ALMAR', 'AMAGT', 'APSIV', 'AUTO4', 'BCEDV', 'BMADE', 'BOLGT', 'BPROS',
        'BRECL', 'BVENA', 'CHIGT', 'CMOBI', 'COAGT', 'CPUBL', 'CRECL', 'CRYST', 'FERRE', 'FUTGT',
        'GABED', 'GALAM', 'GARBO', 'GATLA', 'GCALL', 'GCATO', 'GCHCV', 'GCIUD', 'GCOLO', 'GCTLA',
        'GECHE', 'GESTA', 'GGOLF', 'GIGNA', 'GJUNT', 'GLADG', 'GLAZA', 'GLAZB', 'GLAZC', 'GLOMA',
        'GMAGN', 'GMIRA', 'GMOLI', 'GNIOB', 'GNOGA', 'GPANG', 'GPARQ', 'GPATB', 'GPROS', 'GPUBL',
        'GSJUA', 'GSPAL', 'GTORR', 'GVALL', 'GVENA', 'GVENB', 'GVENC', 'GVEND', 'GVENE', 'GZAPC',
        'GZAPO', 'HCENB', 'HCXVE', 'HMORE', 'HNAVA', 'HPUBL', 'HRECL', 'ICORO', 'IGUAL', 'ISLUC',
        'JUARE', 'JUMAG', 'LUPA', 'LUPAN', 'MASTE', 'MCEDV', 'MINGT', 'MRTGT', 'MTAPE', 'N8SUR',
        'NCANO', 'NCAOR', 'NCIJA', 'NJINO', 'NMASA', 'NMASB', 'NMATA', 'NMEOR', 'NPESP', 'NVENA',
        'NVENB', 'NVENC', 'PCEDJ', 'PCEDV', 'PLAKA', 'PMOBI', 'POETA', 'PPUBL', 'PRECL', 'PROGT',
        'PSJGT', 'PUB08', 'RECLA', 'RETGT', 'REYES', 'ROOGT', 'RVENB', 'RVENC', 'SC2GT', 'SJUGT',
        'SLCGT', 'SLUGT', 'SUBAL', 'SUMID', 'SUR06', 'T0022', 'T0044', 'T0048', 'TB2B1', 'TCEDV',
        'TCSUR', 'TPAUL', 'TPKTO', 'TPSER', 'TVEN3', 'TVESP', 'VBCSA', 'VCEDV', 'VEND2', 'VPLAK',
        'XAUTD', 'XCEDV', 'XCHED', 'XMERA', 'XPIPI',
    ];

    /**
     * Obtener datos del reporte agrupados por TIENDA
     */
    public static function obtenerReporte($filtros)
    {
        $self = new self;

        $fecha_inicio = $filtros['fecha_inicio'] ?? date('Y-m-01');
        $fecha_fin = $filtros['fecha_fin'] ?? date('Y-m-d');
        $plaza = $filtros['plaza'] ?? '';
        $tienda = $filtros['tienda'] ?? '';
        $zona = $filtros['zona'] ?? '';

        $periodo = date('Y-m', strtotime($fecha_inicio));
        $primer_dia_mes = date('Y-m-01', strtotime($fecha_inicio));

        $diasInfoQuery = '
            SELECT DISTINCT dias_mes, 
                   (SELECT SUM(valor_dia) FROM metas_dias md2 WHERE md2.periodo = metas_dias.periodo AND md2.fecha <= ?) as dias_agotados
            FROM metas_dias 
            WHERE periodo = ? 
            LIMIT 1
        ';
        $diasInfoResult = DB::select($diasInfoQuery, [$fecha_fin, $periodo]);

        $dias_mes = ! empty($diasInfoResult) ? floatval($diasInfoResult[0]->dias_mes) : 24;
        $dias_agotados = ! empty($diasInfoResult) ? floatval($diasInfoResult[0]->dias_agotados ?? 0) : 0;

        $metasMensualQuery = '
            SELECT plaza, tienda, meta 
            FROM metas_mensual 
            WHERE periodo = ?
        ';
        $metasMensualData = DB::select($metasMensualQuery, [$periodo]);

        $metasIndex = [];
        foreach ($metasMensualData as $m) {
            $metasIndex[$m->plaza.'|'.$m->tienda] = floatval($m->meta);
        }

        $whereTienda = "bst.clave_tienda IS NOT NULL AND bst.estado <> 'c' AND CAST(bst.id_tipo AS INTEGER) = 1";
        foreach ($self->tiendasExcluidas as $t) {
            $whereTienda .= " AND bst.clave_tienda <> '$t'";
        }
        $whereTienda .= " AND bst.clave_tienda NOT LIKE '%DESC%' AND bst.clave_tienda NOT LIKE '%CEDI%'";

        $sql = "
            SELECT 
                bst.id_plaza,
                bst.clave_tienda,
                bst.nombre AS sucursal,
                bst.zona,
                bst.id_tipo,
                MAX(m.dias_total) as dias_total
            FROM bi_sys_tiendas bst
            LEFT JOIN metas m ON bst.id_plaza = m.plaza AND bst.clave_tienda = m.tienda AND m.fecha BETWEEN '$fecha_inicio' AND '$fecha_fin'
            WHERE $whereTienda
        ";

        $params = [];

        if (! empty($plaza)) {
            $plazas = array_map('trim', explode(',', $plaza));
            if (count($plazas) > 1) {
                $placeholders = implode(',', array_fill(0, count($plazas), '?'));
                $sql .= " AND bst.id_plaza IN ($placeholders)";
                $params = array_merge($params, $plazas);
            } else {
                $sql .= ' AND bst.id_plaza = ?';
                $params[] = $plaza;
            }
        }

        if (! empty($tienda)) {
            $tiendas = array_map('trim', explode(',', $tienda));
            if (count($tiendas) > 1) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $sql .= " AND bst.clave_tienda IN ($placeholders)";
                $params = array_merge($params, $tiendas);
            } else {
                $sql .= ' AND bst.clave_tienda = ?';
                $params[] = $tienda;
            }
        }

        if (! empty($zona)) {
            $zonas = array_map('trim', explode(',', $zona));
            if (count($zonas) > 1) {
                $placeholders = implode(',', array_fill(0, count($zonas), '?'));
                $sql .= " AND bst.zona IN ($placeholders)";
                $params = array_merge($params, $zonas);
            } else {
                $sql .= ' AND bst.zona = ?';
                $params[] = $zona;
            }
        }

        $sql .= ' GROUP BY bst.id_plaza, bst.clave_tienda, bst.nombre, bst.zona, bst.id_tipo ORDER BY bst.clave_tienda';

        $tiendasData = DB::select($sql, $params);

        $whereVenta = 'c.ctienda IS NOT NULL';
        foreach ($self->tiendasExcluidas as $t) {
            $whereVenta .= " AND c.ctienda <> '$t'";
        }
        $whereVenta .= " AND c.ctienda NOT LIKE '%DESC%' AND c.ctienda NOT LIKE '%CEDI%'";

        $ventasSql = "
            SELECT 
                cplaza,
                ctienda,
                SUM((COALESCE(vtacont, 0) - COALESCE(descont, 0)) + (COALESCE(vtacred, 0) - COALESCE(descred, 0))) as venta_real
            FROM xcorte c
            WHERE fecha BETWEEN '$primer_dia_mes' AND '$fecha_fin'
            AND $whereVenta
        ";

        $ventasParams = [];

        if (! empty($plaza)) {
            $plazas = array_map('trim', explode(',', $plaza));
            if (count($plazas) > 1) {
                $placeholders = implode(',', array_fill(0, count($plazas), '?'));
                $ventasSql .= " AND cplaza IN ($placeholders)";
                $ventasParams = array_merge($ventasParams, $plazas);
            } else {
                $ventasSql .= ' AND cplaza = ?';
                $ventasParams[] = $plaza;
            }
        }

        if (! empty($tienda)) {
            $tiendas = array_map('trim', explode(',', $tienda));
            if (count($tiendas) > 1) {
                $placeholders = implode(',', array_fill(0, count($tiendas), '?'));
                $ventasSql .= " AND ctienda IN ($placeholders)";
                $ventasParams = array_merge($ventasParams, $tiendas);
            } else {
                $ventasSql .= ' AND ctienda = ?';
                $ventasParams[] = $tienda;
            }
        }

        $ventasSql .= ' GROUP BY cplaza, ctienda';

        $ventasData = DB::select($ventasSql, $ventasParams);

        $ventasIndex = [];
        foreach ($ventasData as $v) {
            $ventasIndex[$v->cplaza.'|'.$v->ctienda] = floatval($v->venta_real);
        }

        $resultados = [];
        foreach ($tiendasData as $row) {
            $key = $row->id_plaza.'|'.$row->clave_tienda;
            $venta_real = $ventasIndex[$key] ?? 0;
            $meta_total = $metasIndex[$key] ?? 0;
            $meta_parcial = $dias_mes > 0 ? ($meta_total / $dias_mes) * $dias_agotados : 0;
            $porcentaje = $meta_parcial > 0 ? ($venta_real / $meta_parcial) * 100 : 0;

            $resultados[] = (object) [
                'id_plaza' => $row->id_plaza,
                'clave_tienda' => $row->clave_tienda,
                'sucursal' => $row->sucursal,
                'zona' => $row->zona,
                'meta_total' => $meta_total,
                'dias_mes' => $dias_mes,
                'dias_agotados' => $dias_agotados,
                'meta_parcial' => $meta_parcial,
                'venta_real' => $venta_real,
                'porcentaje' => $porcentaje,
            ];
        }

        return $resultados;
    }

    public static function obtenerEstadisticas($resultados)
    {
        $estadisticas = [
            'total_meta_total' => 0,
            'total_venta_real' => 0,
            'total_meta_parcial' => 0,
            'porcentaje_promedio' => 0,
            'total_registros' => 0,
        ];

        if (count($resultados) > 0) {
            $total_meta_total = 0;
            $total_venta_real = 0;
            $total_meta_parcial = 0;
            $porcentaje_total = 0;

            foreach ($resultados as $item) {
                $total_meta_total += $item->meta_total;
                $total_venta_real += $item->venta_real;
                $total_meta_parcial += $item->meta_parcial;
                $porcentaje_total += $item->porcentaje;
            }

            $contador = count($resultados);

            $estadisticas['total_meta_total'] = $total_meta_total;
            $estadisticas['total_venta_real'] = $total_venta_real;
            $estadisticas['total_meta_parcial'] = $total_meta_parcial;
            $estadisticas['porcentaje_promedio'] = $contador > 0 ? $porcentaje_total / $contador : 0;
            $estadisticas['total_registros'] = $contador;
        }

        return $estadisticas;
    }
}
