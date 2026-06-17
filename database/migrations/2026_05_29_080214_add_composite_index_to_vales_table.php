<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->index(['no_consec', 'cve_pro_cl', 'fecha', 'tienda'], 'idx_vales_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->dropIndex('idx_vales_lookup');
        });
    }
};
