<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['immediate', 'scheduled', 'recurring'])->default('immediate');
            $table->timestamp('scheduled_at')->nullable();
            $table->string('recurrence')->nullable(); // daily, weekly, monthly
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->foreignId('group_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('reception_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained()->onDelete('cascade');
            $table->foreignId('computer_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->integer('progress')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reception_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->bigInteger('file_size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_files');
        Schema::dropIfExists('reception_targets');
        Schema::dropIfExists('receptions');
    }
};
