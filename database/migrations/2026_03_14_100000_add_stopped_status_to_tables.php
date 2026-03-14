<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE receptions DROP CONSTRAINT receptions_status_check');
        DB::statement("ALTER TABLE receptions ADD CONSTRAINT receptions_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'failed', 'cancelled', 'stopped'))");

        DB::statement('ALTER TABLE distributions DROP CONSTRAINT distributions_status_check');
        DB::statement("ALTER TABLE distributions ADD CONSTRAINT distributions_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'failed', 'cancelled', 'stopped'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE receptions DROP CONSTRAINT receptions_status_check');
        DB::statement("ALTER TABLE receptions ADD CONSTRAINT receptions_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'failed', 'cancelled'))");

        DB::statement('ALTER TABLE distributions DROP CONSTRAINT distributions_status_check');
        DB::statement("ALTER TABLE distributions ADD CONSTRAINT distributions_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'failed'))");
    }
};
