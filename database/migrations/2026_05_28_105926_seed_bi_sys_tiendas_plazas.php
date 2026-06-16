<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $plazas = [
            ['clave_tienda' => 'XALAP', 'nombre' => 'Xalapa', 'id_plaza' => 'XALAP'],
            ['clave_tienda' => 'BAJAC', 'nombre' => 'Baja California', 'id_plaza' => 'BAJAC'],
            ['clave_tienda' => 'TAPAC', 'nombre' => 'Tapachula', 'id_plaza' => 'TAPAC'],
            ['clave_tienda' => 'VALLA', 'nombre' => 'Vallarta', 'id_plaza' => 'VALLA'],
            ['clave_tienda' => 'CHETU', 'nombre' => 'Chetumal', 'id_plaza' => 'CHETU'],
            ['clave_tienda' => 'GUADA', 'nombre' => 'Guadalajara', 'id_plaza' => 'GUADA'],
            ['clave_tienda' => 'GUATE', 'nombre' => 'Guatemala', 'id_plaza' => 'GUATE'],
            ['clave_tienda' => 'NICAR', 'nombre' => 'Nicaragua', 'id_plaza' => 'NICAR'],
            ['clave_tienda' => 'REYES', 'nombre' => 'Los Reyes', 'id_plaza' => 'REYES'],
            ['clave_tienda' => 'GCHAP', 'nombre' => 'Golfo Chapas', 'id_plaza' => 'GCHAP'],
            ['clave_tienda' => 'HERMO', 'nombre' => 'Hermosillo', 'id_plaza' => 'HERMO'],
            ['clave_tienda' => 'PENLA', 'nombre' => 'Peninsula LA', 'id_plaza' => 'PENLA'],
        ];

        foreach ($plazas as $plaza) {
            DB::table('bi_sys_tiendas')->updateOrInsert(
                ['clave_tienda' => $plaza['clave_tienda']],
                [
                    'nombre' => $plaza['nombre'],
                    'id_plaza' => $plaza['id_plaza'],
                    'zona' => '1',
                    'clave_alterna' => $plaza['clave_tienda'],
                    'id_tipo' => 1,
                    'estado' => 'A',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('bi_sys_tiendas')
            ->whereIn('clave_tienda', ['XALAP', 'BAJAC', 'TAPAC', 'VALLA', 'CHETU', 'GUADA', 'GUATE', 'NICAR', 'REYES', 'GCHAP', 'HERMO', 'PENLA'])
            ->delete();
    }
};
