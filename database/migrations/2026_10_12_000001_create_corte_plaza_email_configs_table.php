<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corte_plaza_email_configs', function (Blueprint $table) {
            $table->id();
            $table->string('plaza', 50)->unique();
            $table->json('emails')->nullable();
            $table->smallInteger('envio_activo')->default(0);
            $table->smallInteger('hora_envio')->default(7);
            $table->timestamp('ultimo_envio_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corte_plaza_email_configs');
    }
};
