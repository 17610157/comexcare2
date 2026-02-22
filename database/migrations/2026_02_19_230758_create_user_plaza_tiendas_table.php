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
        Schema::create('user_plaza_tiendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('plaza')->nullable();
            $table->string('tienda')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'plaza', 'tienda']);
            $table->index('user_id');
            $table->index('plaza');
            $table->index('tienda');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_plaza_tiendas');
    }
};
