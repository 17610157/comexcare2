<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conciliacion_hash_archivos', function (Blueprint $table) {
            $table->string('md5_completo', 32)->nullable()->after('md5');
            $table->string('disparador', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('conciliacion_hash_archivos', function (Blueprint $table) {
            $table->dropColumn('md5_completo');
            $table->string('disparador', 10)->change();
        });
    }
};
