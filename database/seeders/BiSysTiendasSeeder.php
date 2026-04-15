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
            $tipo = $tienda->id_tipo == '3' ? 'VENDEDOR' : ($tienda->id_tipo == '6' ? 'ALMACEN' : 'TIENDA');

            DB::table('bi_sys_tiendas')->where('id', $tienda->id)->update([
                'grupo' => $tipo,
            ]);
        }

        $this->command->info('✓ Grupo actualizado en '.$tiendas->count().' tiendas');
    }
}
