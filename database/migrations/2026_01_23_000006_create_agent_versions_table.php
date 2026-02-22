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
        Schema::create('agent_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version');
            $table->enum('channel', ['stable', 'beta', 'alpha'])->default('stable');
            $table->string('file_path');
            $table->string('checksum', 64);
            $table->text('changelog')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_versions');
    }
};