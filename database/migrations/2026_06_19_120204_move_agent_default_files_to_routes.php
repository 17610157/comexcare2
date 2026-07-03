<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_default_category_files', function (Blueprint $table) {
            $table->foreignId('agent_default_category_route_id')
                ->nullable()
                ->after('id')
                ->constrained('agent_default_category_routes')
                ->onDelete('cascade');

            $table->dropForeign(['agent_default_category_id']);
            $table->dropColumn('agent_default_category_id');
        });
    }

    public function down(): void
    {
        Schema::table('agent_default_category_files', function (Blueprint $table) {
            $table->foreignId('agent_default_category_id')
                ->nullable()
                ->after('id')
                ->constrained('agent_default_categories')
                ->onDelete('cascade');

            $table->dropForeign(['agent_default_category_route_id']);
            $table->dropColumn('agent_default_category_route_id');
        });
    }
};
