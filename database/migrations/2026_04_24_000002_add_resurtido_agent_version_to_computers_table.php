<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->string('resurtido_agent_version', 50)->nullable()->after('agent_version');
        });
    }

    public function down(): void
    {
        Schema::table('computers', function (Blueprint $table) {
            $table->dropColumn('resurtido_agent_version');
        });
    }
};
