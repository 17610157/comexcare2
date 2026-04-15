<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BiSysTiendasSeeder extends Seeder
{
    public function run(): void
    {
        $tiendas = DB::table('bi_sys_tiendas')->get();

        foreach ($tiendas as $tienda) {
            DB::table('bi_sys_tiendas')->where('id', $tienda->id)->update([
                'grupo' => $this->asignarGrupo($tienda->id_tipo),
            ]);
        }

        $this->command->info('✓ Grupo asignado a '.$tiendas->count().' tiendas');
    }

    private function asignarGrupo(?string $idTipo): string
    {
        if (! $idTipo || $idTipo === ' ' || $idTipo === '9') {
            return 'TIENDA';
        }

        return match ($idTipo) {
            '1' => 'TIENDA',
            '3' => 'VENDEDOR',
            '6' => 'ALMACEN',
            default => 'TIENDA',
        };
    }
}
