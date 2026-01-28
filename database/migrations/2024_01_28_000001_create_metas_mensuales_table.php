<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('metas_mensuales', function (Blueprint $table) {
            $table->id();
            $table->string('plaza', 10);
            $table->string('tienda', 10);
            $table->string('periodo', 7); // Formato: 2026-01
            $table->decimal('meta', 12, 4);
            $table->timestamps();
            
            // Índices únicos para evitar duplicados
            $table->unique(['plaza', 'tienda', 'periodo']);
            $table->index(['plaza', 'tienda']);
            $table->index('periodo');
        });
    }

    public function down()
    {
        Schema::dropIfExists('metas_mensuales');
    }
};