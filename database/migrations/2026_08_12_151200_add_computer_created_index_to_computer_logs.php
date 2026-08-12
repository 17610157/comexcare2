<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_cl_computer_created ON computer_logs (computer_id, created_at)');
        } else {
            DB::statement('CREATE INDEX IF NOT EXISTS idx_cl_computer_created ON computer_logs (computer_id, created_at)');
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_cl_computer_created');
    }
};
