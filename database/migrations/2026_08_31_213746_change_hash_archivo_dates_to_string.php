<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE hash_archivos_lotes ALTER COLUMN fecha_envio TYPE varchar(50) USING fecha_envio::text');
            DB::statement('ALTER TABLE conciliacion_hash_archivos ALTER COLUMN fecha_modificacion TYPE varchar(50) USING fecha_modificacion::text');
            DB::statement('ALTER TABLE conciliacion_hash_archivos ALTER COLUMN fecha_consulta_api TYPE varchar(50) USING fecha_consulta_api::text');
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE conciliacion_hash_archivos ALTER COLUMN fecha_consulta_api TYPE timestamp USING NULLIF(fecha_consulta_api, \'\')::timestamp');
            DB::statement('ALTER TABLE conciliacion_hash_archivos ALTER COLUMN fecha_modificacion TYPE timestamp USING NULLIF(fecha_modificacion, \'\')::timestamp');
            DB::statement('ALTER TABLE hash_archivos_lotes ALTER COLUMN fecha_envio TYPE timestamp USING NULLIF(fecha_envio, \'\')::timestamp');
        }
    }
};
