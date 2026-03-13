<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->string('frequency_type')->nullable()->after('recurrence'); // minutes, hours, daily, weekly, monthly
            $table->integer('frequency_interval')->nullable()->after('frequency_type'); // 1, 2, 3, 5, 10, 15, 30
        });
    }

    public function down(): void
    {
        Schema::table('receptions', function (Blueprint $table) {
            $table->dropColumn(['frequency_type', 'frequency_interval']);
        });
    }
};
