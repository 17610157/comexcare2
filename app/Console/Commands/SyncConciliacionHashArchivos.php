<?php

namespace App\Console\Commands;

use App\Services\ConciliacionHashArchivoService;
use Illuminate\Console\Command;

class SyncConciliacionHashArchivos extends Command
{
    protected $signature = 'conciliacion-hash-archivos:sync';

    protected $description = 'Sincroniza los hashes de archivos desde el endpoint de Conciliación';

    public function handle(): int
    {
        $this->info('Sincronizando hashes de archivos de Conciliación...');

        $service = new ConciliacionHashArchivoService;
        $result = $service->fetchAndSync();

        if ($result['success']) {
            $this->info("Sincronización completada: {$result['count']} registros");

            return 0;
        }

        $this->error($result['message']);

        return 1;
    }
}
