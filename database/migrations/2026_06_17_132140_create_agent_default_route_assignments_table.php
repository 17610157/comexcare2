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
        Schema::create('agent_default_route_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_default_category_route_id')
                ->constrained('agent_default_category_routes')
                ->onDelete('cascade');
            $table->morphs('assignable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_default_route_assignments');
    }
};
