<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users ALTER COLUMN activo DROP DEFAULT');
        DB::statement('ALTER TABLE users ALTER COLUMN activo TYPE smallint USING (activo::int)');
        DB::statement('ALTER TABLE users ALTER COLUMN activo SET DEFAULT 1');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users ALTER COLUMN activo DROP DEFAULT');
        DB::statement('ALTER TABLE users ALTER COLUMN activo TYPE boolean USING (activo::bool)');
        DB::statement('ALTER TABLE users ALTER COLUMN activo SET DEFAULT true');
    }
};
