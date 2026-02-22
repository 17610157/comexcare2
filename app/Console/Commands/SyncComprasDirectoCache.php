<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncComprasDirectoCache extends Command
{
    protected $signature = 'compras-directo:sync-cache 
                            {--full : Sincronizar desde 2000 hasta hoy}
                            {--period= : Período a sincronizar (YYYY-MM-DD,YYYY-MM-DD)}
                            {--day= : Un día específico (YYYY-MM-DD)}
                            {--last-days= : Últimos N días}
                            {--append : Agregar datos sin limpiar la tabla}';

    protected $description = 'Sincroniza los datos de compras directo a la tabla caché';

    public function handle(): int
    {
        $this->info('Iniciando sincronización de compras directo...');
        
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
                DB::statement('TRUNCATE TABLE compras_directo_cache RESTART IDENTITY CASCADE');
                $this->info('Tabla caché limpiada');
            } else {
                $this->info('Modo append - agregando datos...');
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
            $this->info("Sincronización completada. Registros totales: {$count}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Error sincronizando compras_directo_cache: ' . $e->getMessage());
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
