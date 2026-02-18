<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncNotasCompletasCache extends Command
{
    protected $signature = 'notas-completas:sync-cache 
                            {--full : Sincronizar desde 2000 hasta hoy}
                            {--period= : Período a sincronizar (YYYY-MM-DD,YYYY-MM-DD)}
                            {--day= : Un día específico (YYYY-MM-DD)}
                            {--last-days= : Últimos N días}
                            {--append : Agregar datos sin limpiar la tabla}';

    protected $description = 'Sincroniza los datos de notas completas a la tabla caché';

    public function handle(): int
    {
        $this->info('Iniciando sincronización de notas completas...');
        
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
                DB::statement('TRUNCATE TABLE notas_completas_cache RESTART IDENTITY CASCADE');
                $this->info('Tabla caché limpiada');
            } else {
                $this->info('Modo append - agregando datos...');
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
            $this->info("Sincronización completada. Registros totales: {$count}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error sincronizando notas_completas_cache: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
