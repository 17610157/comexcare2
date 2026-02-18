<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncCarteraAbonosCache extends Command
{
    protected $signature = 'cartera-abonos:sync-cache 
                            {--full : Sincronizar desde 2000 hasta hoy}
                            {--period= : Período a sincronizar (YYYY-MM-DD,YYYY-MM-DD)}
                            {--day= : Un día específico (YYYY-MM-DD)}
                            {--last-days= : Últimos N días}
                            {--append : Agregar datos sin limpiar la tabla}';

    protected $description = 'Sincroniza los datos de cartera de abonos a la tabla caché';

    public function handle(): int
    {
        $this->info('Iniciando sincronización de cartera abonos...');
        
        $isFull = $this->option('full');
        $periodOption = $this->option('period');
        $dayOption = $this->option('day');
        $lastDaysOption = $this->option('last-days');
        $append = $this->option('append');
        
        if ($periodOption) {
            $parts = explode(',', $periodOption);
            if (count($parts) !== 2) {
                $this->error('Formato de período inválido. Usar: YYYY-MM-DD,YYYY-MM-DD');
                return Command::FAILURE;
            }
            $start = trim($parts[0]);
            $end = trim($parts[1]);
        } elseif ($dayOption) {
            $start = $dayOption;
            $end = $dayOption;
        } elseif ($lastDaysOption) {
            $days = (int) $lastDaysOption;
            $end = date('Y-m-d');
            $start = date('Y-m-d', strtotime("-{$days} days"));
        } elseif ($isFull) {
            $start = '2000-01-01';
            $end = date('Y-m-d');
        } else {
            $start = date('Y-m-01', strtotime('first day of previous month'));
            $end = date('Y-m-t', strtotime('last day of previous month'));
        }

        $this->info("Período: {$start} hasta {$end}");

        try {
            if (!$append) {
                DB::statement('TRUNCATE TABLE cartera_abonos_cache RESTART IDENTITY CASCADE');
                $this->info('Tabla caché limpiada');
            } else {
                $this->info('Modo append - agregando datos...');
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
            $this->info("Sincronización completada. Registros totales: {$count}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error sincronizando cartera_abonos_cache: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
