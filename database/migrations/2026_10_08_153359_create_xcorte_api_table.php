<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('xcorte_api', function (Blueprint $table) {
            $table->id();
            $table->date('fecha_corte');
            $table->string('clave_tienda', 50);
            $table->decimal('monto_contado', 20, 5)->default(0);
            $table->decimal('monto_credito', 20, 5)->default(0);
            $table->timestamp('fecha_registro')->useCurrent();
            $table->string('plaza', 50)->nullable();
            $table->unsignedBigInteger('computer_id')->nullable();
            $table->timestamps();

            $table->unique(['fecha_corte', 'clave_tienda'], 'xcorte_api_fecha_tienda_unique');
            $table->index('clave_tienda', 'xcorte_api_clave_tienda_index');
            $table->index('fecha_corte', 'xcorte_api_fecha_corte_index');
            $table->index('plaza', 'xcorte_api_plaza_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xcorte_api');
    }
};
