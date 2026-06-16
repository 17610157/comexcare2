<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->unsignedBigInteger('computer_id')->nullable()->after('id');
            $table->foreign('computer_id')->references('id')->on('computers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->dropForeign(['computer_id']);
            $table->dropColumn('computer_id');
        });
    }
};
