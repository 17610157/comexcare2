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
        Schema::create('agent_default_category_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_default_category_id')
                ->constrained('agent_default_categories')
                ->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('checksum', 64);
            $table->bigInteger('file_size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_default_category_files');
    }
};
