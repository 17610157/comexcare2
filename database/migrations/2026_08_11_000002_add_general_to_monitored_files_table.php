<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitored_files', function (Blueprint $table) {
            // Nivel "general": el registro se aplica a todas las máquinas.
            // PostgreSQL no acepta bindings booleanos de Laravel (los convierte a int),
            // por lo que se usa smallint (igual que la columna recursive).
            if (DB::getDriverName() === 'pgsql') {
                $table->smallInteger('general')->default(0);
            } else {
                $table->boolean('general')->default(false);
            }

            $table->index('general');
        });
    }

    public function down(): void
    {
        Schema::table('monitored_files', function (Blueprint $table) {
            $table->dropIndex(['general']);
            $table->dropColumn('general');
        });
    }
};
