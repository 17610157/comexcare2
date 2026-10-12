<?php

namespace Database\Seeders;

use App\Models\CortePlazaEmailConfig;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CortePlazaEmailConfigSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('corte_plaza_email_configs')) {
            return;
        }

        $plazas = DB::table('xcorte_api')
            ->whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->distinct()
            ->orderBy('plaza')
            ->pluck('plaza');

        foreach ($plazas as $plaza) {
            CortePlazaEmailConfig::firstOrCreate(
                ['plaza' => trim((string) $plaza)],
                ['emails' => [], 'envio_activo' => 0, 'hora_envio' => 7]
            );
        }

        $this->command?->info('✓ Configuraciones de notificación de cortes: '.$plazas->count().' plaza(s).');
    }
}
