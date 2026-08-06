<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_lists', function (Blueprint $table) {
            $table->enum('status', ['pending', 'active'])->default('pending')->after('description');
            $table->foreignId('module_id')->nullable()->after('status')->constrained('modules')->nullOnDelete();
            $table->text('authorization_token')->nullable()->after('module_id');
            $table->timestamp('token_expires_at')->nullable()->after('authorization_token');
        });
    }

    public function down(): void
    {
        Schema::table('file_lists', function (Blueprint $table) {
            $table->dropForeign(['module_id']);
            $table->dropColumn(['status', 'module_id', 'authorization_token', 'token_expires_at']);
        });
    }
};
