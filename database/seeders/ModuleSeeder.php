<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('modules')->updateOrInsert(
            ['slug' => 'file-lists'],
            [
                'name' => 'Listas de Archivos',
                'description' => 'Gestión de listas blancas y negras de archivos para distribución y recepción.',
                'is_active' => DB::raw('true'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('✓ Módulo por defecto creado: Listas de Archivos');
    }
}
