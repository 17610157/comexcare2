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
                'grupo' => $this->asignarGrupo($tienda->id_plaza, $tienda->clave_tienda),
            ]);
        }

        $this->command->info('✓ Grupo asignado a '.$tiendas->count().' tiendas');
    }

    private function asignarGrupo(?string $plaza, string $claveTienda): string
    {
        if (! $plaza) {
            return 'SIN GRUPO';
        }

        return match ($plaza) {
            'GUATE' => 'GUATEMALA',
            'MANZA' => 'MANZANILLO',
            'TAPAC' => 'TAPACHULA',
            'HERMO' => 'HERMOSILLO',
            'XALAP' => 'XALAPA',
            'NICAR' => 'NICARAGUA',
            'PENLA' => 'PENINSULA',
            'MERID' => 'MERIDA',
            default => $plaza,
        };
    }
}
