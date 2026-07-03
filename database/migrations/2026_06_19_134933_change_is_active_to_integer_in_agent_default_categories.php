<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE agent_default_categories ALTER COLUMN is_active DROP DEFAULT');
            DB::statement('ALTER TABLE agent_default_categories ALTER COLUMN is_active TYPE smallint USING (CASE WHEN is_active THEN 1 ELSE 0 END)');
            DB::statement('ALTER TABLE agent_default_categories ALTER COLUMN is_active SET DEFAULT 1');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE agent_default_categories ALTER COLUMN is_active DROP DEFAULT');
            DB::statement('ALTER TABLE agent_default_categories ALTER COLUMN is_active TYPE boolean USING (is_active::boolean)');
            DB::statement('ALTER TABLE agent_default_categories ALTER COLUMN is_active SET DEFAULT true');
        }
    }
};
