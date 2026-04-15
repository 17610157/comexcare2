<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bi_sys_tiendas', function (Blueprint $table) {
            $table->id();
            $table->string('clave_tienda', 50)->unique();
            $table->string('nombre', 255)->nullable();
            $table->string('id_plaza', 50)->nullable()->index();
            $table->string('zona', 100)->nullable();
            $table->string('clave_alterna', 50)->nullable();
            $table->string('estado', 1)->default('A');
            $table->integer('id_tipo')->nullable();
            $table->timestamps();

            $table->index(['id_plaza', 'clave_tienda']);
            $table->index('clave_alterna');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bi_sys_tiendas');
    }
};
