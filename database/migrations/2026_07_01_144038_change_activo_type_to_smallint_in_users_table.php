<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN activo DROP DEFAULT');
        DB::statement('ALTER TABLE users ALTER COLUMN activo TYPE smallint USING (activo::int)');
        DB::statement('ALTER TABLE users ALTER COLUMN activo SET DEFAULT 1');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users ALTER COLUMN activo DROP DEFAULT');
        DB::statement('ALTER TABLE users ALTER COLUMN activo TYPE boolean USING (activo::bool)');
        DB::statement('ALTER TABLE users ALTER COLUMN activo SET DEFAULT true');
    }
};
