<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conciliacion_hash_archivos', function (Blueprint $table) {
            $table->string('ip', 45)->nullable()->index()->after('sucursal');
        });
    }

    public function down(): void
    {
        Schema::table('conciliacion_hash_archivos', function (Blueprint $table) {
            $table->dropColumn('ip');
        });
    }
};
