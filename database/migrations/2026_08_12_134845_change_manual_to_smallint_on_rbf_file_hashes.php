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
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE rbf_file_hashes ALTER COLUMN manual DROP DEFAULT');
            DB::statement('ALTER TABLE rbf_file_hashes ALTER COLUMN manual TYPE smallint USING (manual::int)');
            DB::statement('ALTER TABLE rbf_file_hashes ALTER COLUMN manual SET DEFAULT 0');
        } else {
            // SQLite: boolean is already treated as integer (0/1)
            // No change needed
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE rbf_file_hashes ALTER COLUMN manual DROP DEFAULT');
            DB::statement('ALTER TABLE rbf_file_hashes ALTER COLUMN manual TYPE boolean USING (manual::boolean)');
            DB::statement('ALTER TABLE rbf_file_hashes ALTER COLUMN manual SET DEFAULT false');
        }
    }
};
