<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_default_downloads', function (Blueprint $table) {
            $table->string('local_path')->nullable()->after('downloaded_at');
            $table->string('local_checksum')->nullable()->after('local_path');
            $table->string('ruta_local')->nullable()->after('local_checksum');
            $table->string('ruta_servidor')->nullable()->after('ruta_local');
            $table->string('sync_status')->default('pending')->after('ruta_servidor');
            $table->timestamp('synced_at')->nullable()->after('sync_status');

            $table->unique(['computer_id', 'agent_default_category_file_id'], 'downloads_computer_file_unique');
        });
    }

    public function down(): void
    {
        Schema::table('agent_default_downloads', function (Blueprint $table) {
            $table->dropUnique('downloads_computer_file_unique');
            $table->dropColumn(['local_path', 'local_checksum', 'ruta_local', 'ruta_servidor', 'sync_status', 'synced_at']);
        });
    }
};
