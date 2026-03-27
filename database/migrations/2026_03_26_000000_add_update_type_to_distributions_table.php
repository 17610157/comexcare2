<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            if (! Schema::hasColumn('distributions', 'distribution_type')) {
                $table->enum('distribution_type', ['file', 'update'])->default('file')->after('type');
            }
            if (! Schema::hasColumn('distributions', 'subfolder')) {
                $table->string('subfolder')->nullable()->after('distribution_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropColumn(['distribution_type', 'subfolder']);
        });
    }
};
