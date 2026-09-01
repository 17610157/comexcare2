<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hash_archivos_historial', function (Blueprint $table) {
            $table->id();
            $table->string('sucursal', 100);
            $table->string('ip', 45)->nullable();
            $table->string('archivo', 255);
            $table->string('md5', 20);
            $table->string('md5_completo', 32)->nullable();
            $table->string('disparador', 50);
            $table->string('fecha_modificacion', 50)->nullable();
            $table->string('fecha_consulta_api', 50)->nullable();
            $table->timestamps();

            $table->index(['sucursal', 'archivo', 'disparador']);
            $table->index(['sucursal', 'disparador']);
            $table->index('fecha_consulta_api');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hash_archivos_historial');
    }
};
