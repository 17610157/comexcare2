<?php

namespace App\Console\Commands;

use App\Services\RbfFileHashService;
use Illuminate\Console\Command;

class SyncRbfFileHashes extends Command
{
    protected $signature = 'rbf-file-hashes:sync';

    protected $description = 'Sincroniza los hashes de archivos desde el endpoint RBF FileServices';

    public function handle()
    {
        $this->info('Sincronizando hashes de archivos RBF...');

        $service = new RbfFileHashService;
        $result = $service->fetchAndSync();

        if ($result['success']) {
            $this->info("Sincronización completada: {$result['count']} registros");

            return 0;
        }

        $this->error($result['message']);

        return 1;
    }
}
