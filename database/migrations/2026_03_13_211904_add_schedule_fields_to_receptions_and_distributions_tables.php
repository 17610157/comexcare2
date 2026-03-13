<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->time('scheduled_time')->nullable()->after('scheduled_at');
            $table->json('week_days')->nullable()->after('scheduled_time'); // ['monday', 'tuesday', etc.]
            $table->json('file_types')->nullable()->after('week_days'); // ['.dbf', '.cdx', '.fpt']
            $table->boolean('all_files')->default(true)->after('file_types');
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->time('scheduled_time')->nullable()->after('scheduled_at');
            $table->json('week_days')->nullable()->after('scheduled_time');
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropColumn(['scheduled_time', 'week_days', 'file_types', 'all_files']);
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropColumn(['scheduled_time', 'week_days']);
        });
    }
};
