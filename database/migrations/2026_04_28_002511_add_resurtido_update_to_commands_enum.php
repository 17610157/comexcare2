<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE commands DROP CONSTRAINT commands_type_check');
            DB::statement("ALTER TABLE commands ADD CONSTRAINT commands_type_check CHECK (type::text = ANY (ARRAY['download'::character varying, 'execute'::character varying, 'update'::character varying, 'inventory'::character varying, 'receive'::character varying, 'resurtido_update'::character varying]::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE commands DROP CONSTRAINT commands_type_check');
            DB::statement("ALTER TABLE commands ADD CONSTRAINT commands_type_check CHECK (type::text = ANY (ARRAY['download'::character varying, 'execute'::character varying, 'update'::character varying, 'inventory'::character varying, 'receive'::character varying]::text[]))");
        }
    }
};
