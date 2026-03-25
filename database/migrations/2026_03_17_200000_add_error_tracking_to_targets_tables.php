<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('distribution_targets')) {
            return;
        }

        Schema::table('distribution_targets', function (Blueprint $table) {
            if (! Schema::hasColumn('distribution_targets', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
            if (! Schema::hasColumn('distribution_targets', 'attempts')) {
                $table->integer('attempts')->default(0)->after('error_message');
            }
            if (! Schema::hasColumn('distribution_targets', 'next_retry_at')) {
                $table->timestamp('next_retry_at')->nullable()->after('attempts');
            }
        });

        if (! Schema::hasTable('reception_targets')) {
            return;
        }

        Schema::table('reception_targets', function (Blueprint $table) {
            if (! Schema::hasColumn('reception_targets', 'error_message')) {
                $table->text('error_message')->nullable()->after('status');
            }
            if (! Schema::hasColumn('reception_targets', 'attempts')) {
                $table->integer('attempts')->default(0)->after('error_message');
            }
            if (! Schema::hasColumn('reception_targets', 'next_retry_at')) {
                $table->timestamp('next_retry_at')->nullable()->after('attempts');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('distribution_targets') && Schema::hasColumn('distribution_targets', 'error_message')) {
            Schema::table('distribution_targets', function (Blueprint $table) {
                $table->dropColumn(['error_message', 'attempts', 'next_retry_at']);
            });
        }

        if (Schema::hasTable('reception_targets') && Schema::hasColumn('reception_targets', 'error_message')) {
            Schema::table('reception_targets', function (Blueprint $table) {
                $table->dropColumn(['error_message', 'attempts', 'next_retry_at']);
            });
        }
    }
};
