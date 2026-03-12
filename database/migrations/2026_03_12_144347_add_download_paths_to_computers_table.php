<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            for ($i = 1; $i <= 10; $i++) {
                $table->string("download_path_{$i}", 500)->nullable()->after('download_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            for ($i = 1; $i <= 10; $i++) {
                $table->dropColumn("download_path_{$i}");
            }
        });
    }
};
