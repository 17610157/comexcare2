<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rbf_file_hashes', function (Blueprint $table) {
            $table->boolean('manual')->default(false)->after('last_sync');
            $table->index('manual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rbf_file_hashes', function (Blueprint $table) {
            $table->dropIndex(['manual']);
            $table->dropColumn('manual');
        });
    }
};
