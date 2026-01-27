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
        Schema::create('computers', function (Blueprint $table) {
            $table->id();
            $table->string('computer_name');
            $table->string('mac_address')->unique();
            $table->string('ip_address')->nullable();
            $table->foreignId('group_id')->nullable()->constrained('groups')->onDelete('set null');
            $table->string('agent_version');
            $table->timestamp('last_seen')->nullable();
            $table->enum('status', ['online', 'offline', 'error', 'updating'])->default('offline');
            $table->jsonb('system_info')->nullable();
            $table->jsonb('agent_config')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('computers');
    }
};