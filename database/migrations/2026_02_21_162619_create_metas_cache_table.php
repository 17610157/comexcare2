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
        Schema::create('metas_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cplaza', 20)->nullable();
            $table->string('ctienda', 20)->nullable();
            $table->string('vend_clave', 20)->nullable();
            $table->date('fecha')->nullable();
            $table->string('plaza_ajustada', 50)->nullable();
            $table->string('tienda_vendedor', 50)->nullable();
            $table->decimal('meta_dia', 15, 2)->default(0);
            $table->decimal('venta', 15, 2)->default(0);
            $table->decimal('diferencia', 15, 2)->default(0);
            $table->decimal('porcentaje', 5, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metas_cache');
    }
};
