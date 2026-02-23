<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAllCacheTables extends Command
{
    protected $signature = 'sync:all-cache-tables 
                            {--full : Sincronizar desde 2000 hasta hoy}
                            {--period= : Período a sincronizar (YYYY-MM-DD,YYYY-MM-DD)}
                            {--last-days= : Últimos N días}
                            {--append : Agregar datos sin limpiar las tablas}';

    protected $description = 'Sincroniza todas las tablas de caché de reportes';

    public function handle(): int
    {
        $this->info('========================================');
        $this->info('Iniciando sincronización de todas las tablas de caché...');
        $this->info('========================================');

        $lastDays = $this->option('last-days') ?? 60;
        $isFull = $this->option('full');
        $periodOption = $this->option('period');
        $append = $this->option('append');

        $start = '';
        $end = date('Y-m-d');

        if ($periodOption) {
            $parts = explode(',', $periodOption);
            $start = trim($parts[0]);
            $end = trim($parts[1]);
        } elseif ($isFull) {
            $start = '2000-01-01';
        } else {
            $start = date('Y-m-d', strtotime("-{$lastDays} days"));
        }

        $this->info("Período: {$start} hasta {$end}");
        $this->newLine();

        $results = [];

        $results[] = $this->syncCarteraAbonos($start, $end, $append);
        $results[] = $this->syncNotasCompletas($start, $end, $append);
        $results[] = $this->syncComprasDirecto($start, $end, $append);
        $results[] = $this->syncRedencionesClub($start, $end, $append);
        $results[] = $this->syncVendedores($start, $end, $append);
        $results[] = $this->syncMetas($start, $end, $append);

        $this->newLine();
        $this->info('========================================');
        $this->info('Resumen de sincronización:');
        foreach ($results as $result) {
            $status = $result['success'] ? '✓' : '✗';
            $this->info("{$status} {$result['name']}: {$result['count']} registros");
        }
        $this->info('========================================');

        return in_array(false, array_column($results, 'success')) 
            ? Command::FAILURE 
            : Command::SUCCESS;
    }

    private function syncCarteraAbonos(string $start, string $end, bool $append): array
    {
        $this->info('Sincronizando cartera_abonos_cache...');

        try {
            if (!$append) {
                DB::statement('TRUNCATE TABLE cartera_abonos_cache RESTART IDENTITY CASCADE');
            }

            $sql = "INSERT INTO cartera_abonos_cache (
                        plaza, tienda, fecha, fecha_vta, concepto, tipo, factura,
                        clave, rfc, nombre, monto_fa, monto_dv, monto_cd,
                        dias_cred, dias_vencidos, vend_clave, updated_at
                    )
                    SELECT
                        c.cplaza AS plaza,
                        c.ctienda AS tienda,
                        c.fecha AS fecha,
                        c2.dfechafac AS fecha_vta,
                        c.concepto AS concepto,
                        c.tipo_ref AS tipo,
                        c.no_ref AS factura,
                        cl.clie_clave AS clave,
                        cl.clie_rfc AS rfc,
                        cl.clie_nombr AS nombre,
                        CASE WHEN c.tipo_ref = 'FA' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_fa,
                        CASE WHEN c.tipo_ref = 'FA' AND c.concepto = 'DV' THEN c.IMPORTE ELSE 0 END AS monto_dv,
                        CASE WHEN c.tipo_ref = 'CD' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_cd,
                        COALESCE(cl.clie_credi, 0) AS dias_cred,
                        (c.fecha - COALESCE(c2.fecha_venc, c.fecha)) AS dias_vencidos,
                        cn.vend_clave AS vend_clave,
                        NOW() AS updated_at
                    FROM cobranza c
                    LEFT JOIN (
                        SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl
                        FROM cobranza co WHERE co.cargo_ab = 'C'
                    ) AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl)
                    LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave)
                    LEFT JOIN canota cn ON (cn.cplaza = c.cplaza AND cn.ctienda = c.ctienda AND cn.cfolio_r = c.no_ref AND cn.ban_status <> 'C')
                    WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' 
                    AND c.fecha >= :start AND c.fecha <= :end";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('cartera_abonos_cache')->count();

            return ['name' => 'Cartera Abonos', 'success' => true, 'count' => $count];
        } catch (\Exception $e) {
            Log::error('Error sincronizando cartera_abonos_cache: ' . $e->getMessage());
            $this->error("Error: {$e->getMessage()}");
            return ['name' => 'Cartera Abonos', 'success' => false, 'count' => 0];
        }
    }

    private function syncNotasCompletas(string $start, string $end, bool $append): array
    {
        $this->info('Sincronizando notas_completas_cache...');

        try {
            if (!$append) {
                DB::statement('TRUNCATE TABLE notas_completas_cache RESTART IDENTITY CASCADE');
            }

            $sql = "INSERT INTO notas_completas_cache (
                        plaza_ajustada, ctienda, num_referencia, vend_clave, factura,
                        nota_club, club_tr, club_id, fecha_vta, producto, descripcion,
                        piezas, descuento, precio_venta, costo, total_con_iva, total_sin_iva, updated_at
                    )
                    SELECT
                        CASE 
                            WHEN c.ctienda = 'T0014' THEN 'MANZA' 
                            WHEN c.ctienda = 'T0017' THEN 'MANZA' 
                            WHEN c.ctienda = 'T0031' THEN 'MANZA' 
                            WHEN c.vend_clave = '14379' THEN 'MANZA' 
                            ELSE c.cplaza 
                        END AS plaza_ajustada,
                        c.ctienda,
                        c.nota_folio AS num_referencia,
                        c.vend_clave,
                        c.cfolio_r AS factura,
                        TRIM(c.cnodoc) AS nota_club,
                        TRIM(cx.ccampo2) AS club_tr,
                        cx.ccampo3 AS club_id,
                        c.nota_fecha AS fecha_vta,
                        '''' || TRIM(cu.prod_clave) AS producto,
                        cu.cdesc_adi AS descripcion,
                        cu.nota_canti AS piezas,
                        cu.nota_pdesc AS descuento,
                        cu.nota_preci AS precio_venta,
                        cu.ncampo1 AS costo,
                        (cu.nota_canti * cu.nota_preci) AS total_con_iva,
                        ((cu.nota_canti * cu.nota_preci) / ('1' + (cu.nota_pimpu / '100'))) AS total_sin_iva,
                        NOW() AS updated_at
                    FROM canota c
                    INNER JOIN cunota cu ON c.nota_folio = cu.nota_folio AND c.cplaza = cu.cplaza AND c.ctienda = cu.ctienda
                    INNER JOIN canotaex cx ON c.cplaza = cx.cplaza AND c.ctienda = cx.ctienda AND c.nota_folio = cx.nota_folio
                    WHERE c.nota_fecha >= :start AND c.nota_fecha <= :end
                    AND c.ban_status <> 'C'
                    AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027','00095','GALMA','BOVER')
                    AND c.ctienda NOT LIKE '%DESC%'
                    AND c.ctienda NOT LIKE '%CEDI%'";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('notas_completas_cache')->count();

            return ['name' => 'Notas Completas', 'success' => true, 'count' => $count];
        } catch (\Exception $e) {
            Log::error('Error sincronizando notas_completas_cache: ' . $e->getMessage());
            $this->error("Error: {$e->getMessage()}");
            return ['name' => 'Notas Completas', 'success' => false, 'count' => 0];
        }
    }

    private function syncComprasDirecto(string $start, string $end, bool $append): array
    {
        $this->info('Sincronizando compras_directo_cache...');

        try {
            if (!$append) {
                DB::statement('TRUNCATE TABLE compras_directo_cache RESTART IDENTITY CASCADE');
            }

            $sql = "INSERT INTO compras_directo_cache (
                        cplaza, ctienda, tipo_doc, no_referen, tipo_doc_a, no_fact_pr,
                        clave_pro, nombre_proveedor, cuenta, f_emision, clave_art, descripcion,
                        cantidad, precio_uni, k_agrupa, k_familia, k_subfam, total, updated_at
                    )
                    SELECT
                        c.cplaza,
                        c.ctienda,
                        c.tipo_doc,
                        c.no_referen,
                        c.tipo_doc_a,
                        c.no_fact_pr,
                        c.clave_pro,
                        por.nombre AS nombre_proveedor,
                        c.cuenta,
                        c.f_emision,
                        '''' || p.clave_art AS clave_art,
                        pr.descripcio AS descripcion,
                        p.cantidad,
                        p.precio_uni,
                        pr.k_agrupa,
                        pr.k_familia,
                        pr.k_subfam,
                        p.cantidad * p.precio_uni AS total,
                        NOW() AS updated_at
                    FROM compras c
                    JOIN partcomp p ON c.ctienda = p.ctienda AND c.cplaza = p.cplaza AND c.tipo_doc = p.tipo_doc AND c.no_referen = p.no_referen
                    JOIN proveed por ON por.clave_pro = c.clave_pro AND c.ctienda = por.ctienda AND c.cplaza = por.cplaza
                    JOIN grupos pr ON p.clave_art = pr.clave
                    WHERE c.f_emision >= :start AND c.f_emision <= :end";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('compras_directo_cache')->count();

            return ['name' => 'Compras Directo', 'success' => true, 'count' => $count];
        } catch (\Exception $e) {
            Log::error('Error sincronizando compras_directo_cache: ' . $e->getMessage());
            $this->error("Error: {$e->getMessage()}");
            return ['name' => 'Compras Directo', 'success' => false, 'count' => 0];
        }
    }

    private function syncRedencionesClub(string $start, string $end, bool $append): array
    {
        $this->info('Sincronizando redenciones_club_cache...');

        try {
            if (!$append) {
                DB::statement('TRUNCATE TABLE redenciones_club_cache RESTART IDENTITY CASCADE');
            }

            $sql = "INSERT INTO redenciones_club_cache (
                        cplaza, ctienda, cve_con, fecha, ref_tipo, ref_num, importe,
                        ing_egr, club_id, vend_clave, nota_folio, cfolio_r, tipo_venta,
                        clie_clave, ban_status, nota_fecha, prod_clave, cdesc_adi,
                        nota_canti, nota_preci, subtotal, nota_impor, ncampo1, sr_recno, created_at
                    )
                    SELECT
                        r.plaza AS cplaza,
                        r.tienda AS ctienda,
                        r.clave AS cve_con,
                        r.fecha,
                        r.referencia AS ref_tipo,
                        r.num_ref AS ref_num,
                        r.importe,
                        r.ing_egr,
                        r.campo3 AS club_id,
                        r.clave_vend AS vend_clave,
                        r.nota_folio,
                        r.factura AS cfolio_r,
                        r.tipo_venta,
                        r.clie_clave,
                        r.status AS ban_status,
                        r.fecha2 AS nota_fecha,
                        '''' || r.clav AS prod_clave,
                        r.descripcion AS cdesc_adi,
                        r.cantidad::numeric AS nota_canti,
                        r.precio AS nota_preci,
                        r.subtotal,
                        r.importefin AS nota_impor,
                        r.costo AS ncampo1,
                        r.sr_recno,
                        NOW() AS created_at
                    FROM redenciones_clubcomex r
                    WHERE r.fecha >= :start AND r.fecha <= :end
                    AND r.tienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
                    AND r.tienda NOT LIKE '%DESC%'
                    AND r.tienda NOT LIKE '%CEDI%'";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('redenciones_club_cache')->count();

            return ['name' => 'Redenciones Club', 'success' => true, 'count' => $count];
        } catch (\Exception $e) {
            Log::error('Error sincronizando redenciones_club_cache: ' . $e->getMessage());
            $this->error("Error: {$e->getMessage()}");
            return ['name' => 'Redenciones Club', 'success' => false, 'count' => 0];
        }
    }

    private function syncVendedores(string $start, string $end, bool $append): array
    {
        $this->info('Sincronizando vendedores_cache...');

        try {
            if (!$append) {
                DB::statement('TRUNCATE TABLE vendedores_cache RESTART IDENTITY CASCADE');
            }

            $sql = "INSERT INTO vendedores_cache (
                        cplaza, ctienda, vend_clave, nota_fecha, plaza_ajustada,
                        tienda_vendedor, vendedor_dia, venta_total, devolucion, venta_neta, created_at, updated_at
                    )
                    SELECT
                        c.cplaza,
                        c.ctienda,
                        c.vend_clave,
                        c.nota_fecha::date AS nota_fecha,
                        CASE 
                            WHEN c.ctienda IN ('T0014', 'T0017', 'T0031') THEN 'MANZA' 
                            WHEN c.vend_clave = '14379' THEN 'MANZA' 
                            ELSE c.cplaza 
                        END AS plaza_ajustada,
                        c.ctienda || '-' || c.vend_clave AS tienda_vendedor,
                        c.vend_clave || '-' || EXTRACT(DAY FROM c.nota_fecha::date)::text AS vendedor_dia,
                        SUM(c.nota_impor) AS venta_total,
                        0 AS devolucion,
                        SUM(c.nota_impor) AS venta_neta,
                        NOW() AS created_at,
                        NOW() AS updated_at
                    FROM canota c
                    WHERE c.nota_fecha >= :start AND c.nota_fecha <= :end
                    AND c.ban_status <> 'C'
                    AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
                    AND c.ctienda NOT LIKE '%DESC%'
                    AND c.ctienda NOT LIKE '%CEDI%'
                    GROUP BY c.cplaza, c.ctienda, c.vend_clave, c.nota_fecha";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('vendedores_cache')->count();

            return ['name' => 'Vendedores', 'success' => true, 'count' => $count];
        } catch (\Exception $e) {
            Log::error('Error sincronizando vendedores_cache: ' . $e->getMessage());
            $this->error("Error: {$e->getMessage()}");
            return ['name' => 'Vendedores', 'success' => false, 'count' => 0];
        }
    }

    private function syncMetas(string $start, string $end, bool $append): array
    {
        $this->info('Sincronizando metas_cache...');

        try {
            if (!$append) {
                DB::statement('TRUNCATE TABLE metas_cache RESTART IDENTITY CASCADE');
            }

            $sql = "INSERT INTO metas_cache (
                        cplaza, ctienda, vend_clave, fecha, plaza_ajustada,
                        tienda_vendedor, meta_dia, venta, diferencia, porcentaje, created_at, updated_at
                    )
                    SELECT
                        c.cplaza,
                        c.ctienda,
                        c.vend_clave,
                        c.nota_fecha::date AS fecha,
                        CASE 
                            WHEN c.ctienda IN ('T0014', 'T0017', 'T0031') THEN 'MANZA' 
                            WHEN c.vend_clave = '14379' THEN 'MANZA' 
                            ELSE c.cplaza 
                        END AS plaza_ajustada,
                        c.ctienda || '-' || c.vend_clave AS tienda_vendedor,
                        0 AS meta_dia,
                        SUM(c.nota_impor) AS venta,
                        0 AS diferencia,
                        0 AS porcentaje,
                        NOW() AS created_at,
                        NOW() AS updated_at
                    FROM canota c
                    WHERE c.nota_fecha >= :start AND c.nota_fecha <= :end
                    AND c.ban_status <> 'C'
                    AND c.ctienda NOT IN ('ALMAC','BODEG','ALTAP','CXVEA','00095','GALMA','B0001','00027')
                    AND c.ctienda NOT LIKE '%DESC%'
                    AND c.ctienda NOT LIKE '%CEDI%'
                    GROUP BY c.cplaza, c.ctienda, c.vend_clave, c.nota_fecha";

            DB::insert($sql, ['start' => $start, 'end' => $end]);

            $count = DB::table('metas_cache')->count();

            return ['name' => 'Metas', 'success' => true, 'count' => $count];
        } catch (\Exception $e) {
            Log::error('Error sincronizando metas_cache: ' . $e->getMessage());
            $this->error("Error: {$e->getMessage()}");
            return ['name' => 'Metas', 'success' => false, 'count' => 0];
        }
    }
}
