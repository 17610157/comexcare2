<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rbf_plaza_time_configs', function (Blueprint $table) {
            $table->id();
            $table->string('plaza', 50)->unique();
            $table->smallInteger('meridiano');
            $table->smallInteger('zona_horaria')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rbf_plaza_time_configs');
    }
};
