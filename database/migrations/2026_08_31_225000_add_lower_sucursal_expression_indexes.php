<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement('CREATE INDEX IF NOT EXISTS conciliacion_hash_archivos_sucursal_lower_idx ON conciliacion_hash_archivos (lower(sucursal))');
        DB::statement('CREATE INDEX IF NOT EXISTS conciliacion_hash_archivos_disparador_lower_idx ON conciliacion_hash_archivos (lower(disparador))');
        DB::statement('CREATE INDEX IF NOT EXISTS hash_archivos_lotes_sucursal_lower_idx ON hash_archivos_lotes (lower(sucursal))');
        DB::statement('CREATE INDEX IF NOT EXISTS hash_archivos_lotes_disparador_lower_idx ON hash_archivos_lotes (lower(disparador))');
        DB::statement('CREATE INDEX IF NOT EXISTS hash_archivos_historial_sucursal_lower_idx ON hash_archivos_historial (lower(sucursal))');
        DB::statement('CREATE INDEX IF NOT EXISTS hash_archivos_historial_archivo_lower_idx ON hash_archivos_historial (lower(archivo))');
        DB::statement('CREATE INDEX IF NOT EXISTS hash_archivos_historial_disparador_lower_idx ON hash_archivos_historial (lower(disparador))');
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver !== 'pgsql') {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS conciliacion_hash_archivos_sucursal_lower_idx');
        DB::statement('DROP INDEX IF EXISTS conciliacion_hash_archivos_disparador_lower_idx');
        DB::statement('DROP INDEX IF EXISTS hash_archivos_lotes_sucursal_lower_idx');
        DB::statement('DROP INDEX IF EXISTS hash_archivos_lotes_disparador_lower_idx');
        DB::statement('DROP INDEX IF EXISTS hash_archivos_historial_sucursal_lower_idx');
        DB::statement('DROP INDEX IF EXISTS hash_archivos_historial_archivo_lower_idx');
        DB::statement('DROP INDEX IF EXISTS hash_archivos_historial_disparador_lower_idx');
    }
};
