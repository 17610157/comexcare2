<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vales', function (Blueprint $table) {
            $table->id();
            $table->char('tipo_movim', 2)->nullable();
            $table->char('no_consec', 6)->nullable();
            $table->date('fecha')->nullable();
            $table->char('cve_pro_cl', 5)->nullable();
            $table->string('desc_mov', 40)->nullable();
            $table->char('ent_sal', 1)->nullable();
            $table->char('observinv', 6)->nullable();
            $table->char('almacen', 2)->nullable();
            $table->char('afectado', 1)->nullable();
            $table->char('estado', 1)->nullable();
            $table->date('fechacort')->nullable();
            $table->decimal('precio_tot', 20, 5)->nullable();
            $table->decimal('impues_tot', 20, 5)->nullable();
            $table->decimal('cost_tot', 20, 5)->nullable();
            $table->decimal('no_partida', 20, 5)->nullable();
            $table->char('mov_origen', 13)->nullable();
            $table->char('ya_exporta', 1)->nullable();
            $table->char('clave_usu', 5)->nullable();
            $table->char('campo1c', 5)->nullable();
            $table->decimal('campo2n', 20, 5)->nullable();
            $table->char('folio_ref', 6)->nullable();
            $table->char('lista_prec', 1)->nullable();
            $table->char('ccampo1', 16)->nullable();
            $table->decimal('ncampo2', 20, 5)->nullable();
            $table->date('dcampo3')->nullable();
            $table->boolean('lcampo4')->nullable();
            $table->char('tienda', 5)->nullable();
            $table->char('modhora', 8)->nullable();
            $table->date('modfecha')->nullable();
            $table->char('moduser', 5)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vales');
    }
};
