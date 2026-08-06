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
        Schema::create('rbf_file_hashes', function (Blueprint $table) {
            $table->id();
            $table->string('servicio', 50);
            $table->string('plaza', 50)->nullable();
            $table->string('zona', 50)->nullable();
            $table->text('path');
            $table->string('name', 255);
            $table->string('hash', 20);
            $table->timestamp('last_modified')->nullable();
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();

            $table->index('servicio');
            $table->index('plaza');
            $table->index('zona');
            $table->unique('path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rbf_file_hashes');
    }
};
