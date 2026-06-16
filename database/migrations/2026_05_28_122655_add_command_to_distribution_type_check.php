<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE distributions DROP CONSTRAINT IF EXISTS distributions_distribution_type_check');
            DB::statement("ALTER TABLE distributions ADD CONSTRAINT distributions_distribution_type_check CHECK (distribution_type::text = ANY (ARRAY['file'::character varying, 'update'::character varying, 'command'::character varying]::text[]))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE distributions DROP CONSTRAINT IF EXISTS distributions_distribution_type_check');
            DB::statement("ALTER TABLE distributions ADD CONSTRAINT distributions_distribution_type_check CHECK (distribution_type::text = ANY (ARRAY['file'::character varying, 'update'::character varying]::text[]))");
        }
    }
};
