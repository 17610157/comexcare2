<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conciliacion_hash_archivos', function (Blueprint $table) {
            $table->id();
            $table->string('sucursal', 100);
            $table->string('archivo', 255);
            $table->string('md5', 20);
            $table->timestamp('fecha_modificacion')->nullable();
            $table->string('disparador', 10);
            $table->timestamp('fecha_consulta_api')->nullable();
            $table->timestamps();

            $table->index('sucursal');
            $table->index('archivo');
            $table->index('disparador');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conciliacion_hash_archivos');
    }
};
