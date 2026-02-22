<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendedores_cache', function (Blueprint $table) {
            $table->id();
            $table->string('tienda_vendedor', 50);
            $table->string('vendedor_dia', 50);
            $table->string('plaza_ajustada', 50);
            $table->string('ctienda', 20);
            $table->string('vend_clave', 20);
            $table->date('fecha');
            $table->decimal('venta_total', 15, 2)->default(0);
            $table->decimal('devolucion', 15, 2)->default(0);
            $table->decimal('venta_neta', 15, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ctienda', 'vend_clave']);
            $table->index(['plaza_ajustada']);
            $table->index(['fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendedores_cache');
    }
};
