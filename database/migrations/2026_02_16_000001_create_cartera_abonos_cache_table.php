<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartera_abonos_cache', function (Blueprint $table) {
            $table->id();
            $table->string('plaza', 10)->index();
            $table->string('tienda', 20)->index();
            $table->date('fecha');
            $table->date('fecha_vta')->nullable();
            $table->string('concepto', 10);
            $table->string('tipo', 10);
            $table->string('factura', 50);
            $table->string('clave', 50)->index();
            $table->string('rfc', 20)->index();
            $table->string('nombre', 255)->index();
            $table->decimal('monto_fa', 15, 2)->default(0);
            $table->decimal('monto_dv', 15, 2)->default(0);
            $table->decimal('monto_cd', 15, 2)->default(0);
            $table->integer('dias_cred')->default(0);
            $table->integer('dias_vencidos')->default(0);
            $table->timestamp('updated_at')->useCurrent();
            
            $table->index(['plaza', 'tienda', 'fecha']);
            $table->index(['fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartera_abonos_cache');
    }
};
