<?php

namespace App\Console\Commands;

use App\Http\Controllers\Reportes\ClubComexController;
use Illuminate\Console\Command;

class SyncClubComex extends Command
{
    protected $signature = 'clubcomex:sync {year? : Año a sincronizar}';

    protected $description = 'Sincroniza datos de Club Comex';

    public function handle()
    {
        $years = $this->argument('year') ? [(int) $this->argument('year')] : [2021, 2022, 2023, 2024, 2025, 2026];

        foreach ($years as $year) {
            $this->info("Sincronizando año {$year}...");
            $start = "{$year}-01-01";
            $end = $year === (int) date('Y') ? date('Y-m-d') : "{$year}-12-31";

            $this->info("  Período: {$start} - {$end}");

            $ctrl = new ClubComexController;

            $r = $ctrl->syncRedenciones($start, $end);
            $this->info("  Redenciones: {$r['count']}");

            $a = $ctrl->syncAcumulaciones($start, $end);
            $this->info("  Acumulaciones: {$a['count']}");

            $ia = $ctrl->syncAcumulacionesIa($start, $end);
            $this->info("  Acumulaciones IA: {$ia['count']}");
        }

        $this->info('¡Sincronización completada!');

        return 0;
    }
}
