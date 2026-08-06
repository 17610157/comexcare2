<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->string('pvsi_bepartners_version', 50)->nullable()->after('pvsi_hora');
            $table->string('pvsi_bepartners_fecha', 20)->nullable()->after('pvsi_bepartners_version');
            $table->string('pvsi_bepartners_hora', 10)->nullable()->after('pvsi_bepartners_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropColumn(['pvsi_bepartners_version', 'pvsi_bepartners_fecha', 'pvsi_bepartners_hora']);
        });
    }
};
