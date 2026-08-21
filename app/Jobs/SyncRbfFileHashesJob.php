<?php

namespace App\Jobs;

use App\Services\RbfFileHashService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncRbfFileHashesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;

    public $tries = 1;

    public function handle(): void
    {
        Log::info('Sincronización RBF FileServices forzada desde formulario');

        $result = (new RbfFileHashService)->fetchAndSync();

        if ($result['success']) {
            Log::info("Sincronización RBF forzada completada: {$result['count']} registros");

            return;
        }

        Log::error('Sincronización RBF forzada falló: '.$result['message']);
    }
}
