<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->string('tipo_movim', 2)->nullable()->change();
            $table->string('no_consec', 6)->nullable()->change();
            $table->string('cve_pro_cl', 5)->nullable()->change();
            $table->string('ent_sal', 1)->nullable()->change();
            $table->string('observinv', 6)->nullable()->change();
            $table->string('almacen', 2)->nullable()->change();
            $table->string('afectado', 1)->nullable()->change();
            $table->string('estado', 1)->nullable()->change();
            $table->string('mov_origen', 13)->nullable()->change();
            $table->string('ya_exporta', 1)->nullable()->change();
            $table->string('clave_usu', 5)->nullable()->change();
            $table->string('campo1c', 5)->nullable()->change();
            $table->string('folio_ref', 6)->nullable()->change();
            $table->string('lista_prec', 1)->nullable()->change();
            $table->string('ccampo1', 16)->nullable()->change();
            $table->string('tienda', 5)->nullable()->change();
            $table->string('modhora', 8)->nullable()->change();
            $table->string('moduser', 5)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->char('tipo_movim', 2)->nullable()->change();
            $table->char('no_consec', 6)->nullable()->change();
            $table->char('cve_pro_cl', 5)->nullable()->change();
            $table->char('ent_sal', 1)->nullable()->change();
            $table->char('observinv', 6)->nullable()->change();
            $table->char('almacen', 2)->nullable()->change();
            $table->char('afectado', 1)->nullable()->change();
            $table->char('estado', 1)->nullable()->change();
            $table->char('mov_origen', 13)->nullable()->change();
            $table->char('ya_exporta', 1)->nullable()->change();
            $table->char('clave_usu', 5)->nullable()->change();
            $table->char('campo1c', 5)->nullable()->change();
            $table->char('folio_ref', 6)->nullable()->change();
            $table->char('lista_prec', 1)->nullable()->change();
            $table->char('ccampo1', 16)->nullable()->change();
            $table->char('tienda', 5)->nullable()->change();
            $table->char('modhora', 8)->nullable()->change();
            $table->char('moduser', 5)->nullable()->change();
        });
    }
};
