<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agent_default_category_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_default_category_id')
                ->constrained('agent_default_categories')
                ->onDelete('cascade');
            $table->string('route_pattern');
            $table->string('label')->nullable();
            $table->unsignedTinyInteger('download_path_index')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_default_category_routes');
    }
};
