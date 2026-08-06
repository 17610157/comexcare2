<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_list_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_list_id')->constrained('file_lists')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email');
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('authorized_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_list_authorizations');
    }
};
