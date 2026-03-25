<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 50);
            $table->string('endpoint', 500);
            $table->string('method', 10);
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->json('request_data')->nullable();
            $table->integer('response_code')->nullable();
            $table->decimal('duration_ms', 10, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index('ip_address');
            $table->index('endpoint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
