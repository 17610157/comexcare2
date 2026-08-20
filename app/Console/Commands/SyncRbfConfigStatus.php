<?php

namespace App\Console\Commands;

use App\Services\RbfConfigStatusService;
use Illuminate\Console\Command;

class SyncRbfConfigStatus extends Command
{
    protected $signature = 'rbf-config-status:sync';

    protected $description = 'Sincroniza el estado de configuración RBF desde el endpoint externo';

    public function handle(): int
    {
        $this->info('Sincronizando estado de configuración RBF...');

        $service = new RbfConfigStatusService;
        $result = $service->fetchAndSync();

        if ($result['success']) {
            $this->info("Sincronización completada: {$result['count']} registros");

            return 0;
        }

        $this->error($result['message']);

        return 1;
    }
}
