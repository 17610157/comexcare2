<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $connection = DB::connection()->getDriverName();

        if (! in_array($connection, ['pgsql'])) {
            return;
        }

        try {
            DB::statement('ALTER TABLE receptions DROP CONSTRAINT IF EXISTS receptions_status_check');
            DB::statement("ALTER TABLE receptions ADD CONSTRAINT receptions_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'failed', 'cancelled', 'stopped'))");
        } catch (Exception $e) {
            // Tabla puede no existir
        }

        try {
            DB::statement('ALTER TABLE distributions DROP CONSTRAINT IF EXISTS distributions_status_check');
            DB::statement("ALTER TABLE distributions ADD CONSTRAINT distributions_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'failed', 'cancelled', 'stopped'))");
        } catch (Exception $e) {
            // Tabla puede no existir
        }
    }

    public function down(): void
    {
        $connection = DB::connection()->getDriverName();

        if (! in_array($connection, ['pgsql'])) {
            return;
        }

        try {
            DB::statement('ALTER TABLE receptions DROP CONSTRAINT IF EXISTS receptions_status_check');
            DB::statement("ALTER TABLE receptions ADD CONSTRAINT receptions_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'failed', 'cancelled'))");
        } catch (Exception $e) {
            // Ignorar
        }

        try {
            DB::statement('ALTER TABLE distributions DROP CONSTRAINT IF EXISTS distributions_status_check');
            DB::statement("ALTER TABLE distributions ADD CONSTRAINT distributions_status_check CHECK (status IN ('pending', 'in_progress', 'completed', 'failed'))");
        } catch (Exception $e) {
            // Ignorar
        }
    }
};
