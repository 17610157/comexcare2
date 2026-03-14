<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            if (! Schema::hasColumn('distributions', 'recurrence')) {
                $table->string('recurrence')->nullable()->after('scheduled_time');
            }
            if (! Schema::hasColumn('distributions', 'frequency_type')) {
                $table->string('frequency_type')->nullable()->after('recurrence');
            }
            if (! Schema::hasColumn('distributions', 'frequency_interval')) {
                $table->integer('frequency_interval')->nullable()->after('frequency_type');
            }
            if (! Schema::hasColumn('distributions', 'week_days')) {
                $table->json('week_days')->nullable()->after('frequency_interval');
            }
            if (! Schema::hasColumn('distributions', 'last_run_at')) {
                $table->timestamp('last_run_at')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropColumn([
                'scheduled_time',
                'recurrence',
                'frequency_type',
                'frequency_interval',
                'week_days',
                'last_run_at',
            ]);
        });
    }
};
