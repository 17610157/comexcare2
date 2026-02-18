<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('plaza', 10)->nullable()->after('email');
            $table->string('tienda', 10)->nullable()->after('plaza');
            $table->enum('rol', ['admin', 'vendedor', 'gerente', 'encargado'])->default('vendedor')->after('tienda');
            $table->boolean('activo')->default(true)->after('rol');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['plaza', 'tienda', 'rol', 'activo']);
        });
    }
};
