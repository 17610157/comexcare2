<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->string('command')->nullable()->after('subfolder');
            $table->string('command_args')->nullable()->after('command');
        });
    }

    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropColumn(['command', 'command_args']);
        });
    }
};
