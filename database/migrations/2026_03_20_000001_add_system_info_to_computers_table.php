<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->string('windows_version', 100)->nullable()->after('pvsi_hora');
            $table->string('architecture', 10)->nullable()->after('windows_version');
            $table->bigInteger('total_ram')->nullable()->after('architecture');
            $table->bigInteger('total_disk_space')->nullable()->after('total_ram');
        });
    }

    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropColumn(['windows_version', 'architecture', 'total_ram', 'total_disk_space']);
        });
    }
};
