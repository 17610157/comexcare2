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
        Schema::table('bi_sys_tiendas', function (Blueprint $table) {
            $table->enum('tipo', ['almacen', 'vendedor', 'tienda', 'administrativo'])->nullable()->default(null)->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bi_sys_tiendas', function (Blueprint $table) {
            //
        });
    }
};
