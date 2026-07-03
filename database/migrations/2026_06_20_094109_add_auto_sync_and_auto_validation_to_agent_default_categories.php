<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE agent_default_categories ADD COLUMN auto_sync smallint NOT NULL DEFAULT 1');
            DB::statement('ALTER TABLE agent_default_categories ADD COLUMN auto_validation smallint NOT NULL DEFAULT 1');
        } else {
            Schema::table('agent_default_categories', function (Blueprint $table) {
                $table->boolean('auto_sync')->default(true);
                $table->boolean('auto_validation')->default(true);
            });
        }
    }

    public function down(): void
    {
        Schema::table('agent_default_categories', function (Blueprint $table) {
            $table->dropColumn(['auto_sync', 'auto_validation']);
        });
    }
};
