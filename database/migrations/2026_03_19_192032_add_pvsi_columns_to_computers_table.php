<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->string('pvsi_version', 50)->nullable()->after('agent_version');
            $table->string('pvsi_fecha', 20)->nullable()->after('pvsi_version');
            $table->string('pvsi_hora', 10)->nullable()->after('pvsi_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropColumn(['pvsi_version', 'pvsi_fecha', 'pvsi_hora']);
        });
    }
};
