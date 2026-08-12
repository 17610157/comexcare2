<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitored_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('computer_id')->nullable()->constrained('computers')->nullOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->string('path');
            $table->string('file_name', 255)->nullable();
            // PostgreSQL no acepta bindings booleanos de Laravel (los convierte a int),
            // por lo que se usa smallint (igual que agent_default_categories.is_active).
            if (DB::getDriverName() === 'pgsql') {
                $table->smallInteger('recursive')->default(0);
            } else {
                $table->boolean('recursive')->default(false);
            }
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['computer_id', 'sort_order']);
            $table->index(['group_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitored_files');
    }
};
