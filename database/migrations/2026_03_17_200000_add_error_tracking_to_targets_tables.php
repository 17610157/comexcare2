<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distribution_targets', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('status');
            $table->integer('attempts')->default(0)->after('error_message');
            $table->timestamp('next_retry_at')->nullable()->after('attempts');
        });

        Schema::table('reception_targets', function (Blueprint $table) {
            $table->text('error_message')->nullable()->after('status');
            $table->integer('attempts')->default(0)->after('error_message');
            $table->timestamp('next_retry_at')->nullable()->after('attempts');
        });
    }

    public function down(): void
    {
        Schema::table('distribution_targets', function (Blueprint $table) {
            $table->dropColumn(['error_message', 'attempts', 'next_retry_at']);
        });

        Schema::table('reception_targets', function (Blueprint $table) {
            $table->dropColumn(['error_message', 'attempts', 'next_retry_at']);
        });
    }
};
