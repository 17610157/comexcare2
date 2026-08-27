<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            $table->string('empleado_id', 50);
            $table->date('fecha');
            $table->text('descripcion');
            $table->string('categoria', 255);
            $table->string('hora_inicio', 5)->nullable();
            $table->string('hora_fin', 5)->nullable();
            $table->string('archivo', 500)->nullable();
            $table->string('archivo_nombre', 255)->nullable();
            $table->timestamps();

            $table->index(['empleado_id', 'fecha']);
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
