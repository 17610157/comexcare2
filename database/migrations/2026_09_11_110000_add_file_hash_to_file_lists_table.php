<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_lists', function (Blueprint $table) {
            $table->string('file_path', 500)->nullable()->after('file_name');
            $table->string('file_md5', 32)->nullable()->after('file_path');
            $table->bigInteger('file_size')->nullable()->after('file_md5');
        });
    }

    public function down(): void
    {
        Schema::table('file_lists', function (Blueprint $table) {
            $table->dropColumn(['file_path', 'file_md5', 'file_size']);
        });
    }
};