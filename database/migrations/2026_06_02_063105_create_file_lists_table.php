<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_lists', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['whitelist', 'blacklist']);
            $table->string('file_name', 255);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['type', 'file_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_lists');
    }
};
