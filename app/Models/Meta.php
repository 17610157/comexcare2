<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meta extends Model
{
    use HasFactory;

    protected $table = 'xcorte'; // O la tabla correcta según tu estructura
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'cplaza',
        'ctienda',
        'fecha',
        'vtacont',
        'descont',
        'vtacred',
        'descred',
        'cobro',
        'vend_clave',
        'vendedor',
    ];
    
    /**
     * Obtener el reporte de metas vs ventas
     */
    public static function obtenerReporteMetasVentas($filtros = [])
    {
        $query = "
            SELECT 
                z.rp_plaza AS plaza,
                z.tienda,
                z.zona,
                b.nombre AS sucursal,
                m.fecha,
                m.meta_total,
                m.dias_total,
                m.valor_dia,
                m.meta_dia,
                
                /* Total vendido */
                (
                    (COALESCE(x.vtacont, 0) - COALESCE(x.descont, 0)) +
                    (COALESCE(x.vtacred, 0) - COALESCE(x.descred, 0))
                ) AS total_vendido,

                /* Porcentaje de cumplimiento */
                CASE 
                    WHEN m.meta_dia > 0 THEN
                        (
                            (
                                (COALESCE(x.vtacont, 0) - COALESCE(x.descont, 0)) +
                                (COALESCE(x.vtacred, 0) - COALESCE(x.descred, 0))
                            ) * 100.0 / m.meta_dia
                        )
                    ELSE 0
                END AS porcentaje_cumplimiento

            FROM metas m

            LEFT JOIN (
                SELECT 
                    xcorte.cplaza  AS plaza,
                    xcorte.ctienda AS tienda,
                    xcorte.fecha,
                    MAX(COALESCE(xcorte.vtacont, 0::numeric)) AS vtacont,
                    MAX(COALESCE(xcorte.descont, 0::numeric)) AS descont,
                    MAX(COALESCE(xcorte.vtacred, 0::numeric)) AS vtacred,
                    MAX(COALESCE(xcorte.descred, 0::numeric)) AS descred,
                    MAX(COALESCE(xcorte.cobro, 0::numeric))   AS cobro
                FROM xcorte
                GROUP BY 
                    xcorte.cplaza,
                    xcorte.ctienda,
                    xcorte.fecha
            ) x 
                ON m.plaza::bpchar  = x.plaza
               AND m.tienda::bpchar = x.tienda
               AND m.fecha          = x.fecha

            JOIN zona z 
                ON m.plaza::text  = z.plaza::text
               AND m.tienda::text = z.tienda::text
               AND z.zona::text NOT ILIKE '%dmin%'
               AND z.zona::text NOT ILIKE '%lmac%'

            LEFT JOIN bi_sys_tiendas b 
                ON m.plaza::bpchar  = b.id_plaza
               AND m.tienda::bpchar = b.clave_tienda
            
            WHERE 1=1
        ";
        
        // Aplicar filtros
        if (!empty($filtros['fecha_inicio'])) {
            $query .= " AND m.fecha >= '{$filtros['fecha_inicio']}'";
        }
        
        if (!empty($filtros['fecha_fin'])) {
            $query .= " AND m.fecha <= '{$filtros['fecha_fin']}'";
        }
        
        if (!empty($filtros['plaza'])) {
            $query .= " AND m.plaza = '{$filtros['plaza']}'";
        }
        
        if (!empty($filtros['tienda'])) {
            $query .= " AND m.tienda = '{$filtros['tienda']}'";
        }
        
        if (!empty($filtros['zona'])) {
            $query .= " AND z.zona LIKE '%{$filtros['zona']}%'";
        }
        
        $query .= " ORDER BY m.plaza, m.tienda, m.fecha ASC";
        
        return \DB::select($query);
    }
    
    /**
     * Obtener las plazas disponibles
     */
    public static function obtenerPlazas()
    {
        return \DB::table('metas')
            ->select('plaza')
            ->distinct()
            ->orderBy('plaza')
            ->get();
    }
    
    /**
     * Obtener las tiendas disponibles
     */
    public static function obtenerTiendas($plaza = null)
    {
        $query = \DB::table('metas')
            ->select('tienda')
            ->distinct();
            
        if ($plaza) {
            $query->where('plaza', $plaza);
        }
        
        return $query->orderBy('tienda')->get();
    }
}