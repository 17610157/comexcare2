<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hash_archivos_lotes', function (Blueprint $table) {
            $table->id();
            $table->string('cliente', 100)->nullable();
            $table->string('sucursal', 100)->nullable();
            $table->string('nombre_carpeta', 255)->nullable();
            $table->string('ruta_base', 500)->nullable();
            $table->timestamp('fecha_envio')->nullable();
            $table->string('disparador', 50)->nullable();
            $table->integer('num_archivos')->default(0);
            $table->bigInteger('peso_total')->default(0);
            $table->string('estado', 30);
            $table->longText('payload');
            $table->json('errores')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index('sucursal');
            $table->index('disparador');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hash_archivos_lotes');
    }
};
