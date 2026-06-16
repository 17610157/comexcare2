<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE commands DROP CONSTRAINT IF EXISTS commands_status_check');
            DB::statement("ALTER TABLE commands ADD CONSTRAINT commands_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'sent'::character varying, 'running'::character varying, 'downloading'::character varying, 'completed'::character varying, 'failed'::character varying]::text[]))");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE commands DROP CONSTRAINT IF EXISTS commands_status_check');
            DB::statement("ALTER TABLE commands ADD CONSTRAINT commands_status_check CHECK (status::text = ANY (ARRAY['pending'::character varying, 'sent'::character varying, 'completed'::character varying, 'failed'::character varying]::text[]))");
        }
    }
};
