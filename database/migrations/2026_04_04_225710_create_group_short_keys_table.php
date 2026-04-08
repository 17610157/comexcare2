<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_short_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained()->onDelete('cascade');
            $table->string('short_key', 50);
            $table->timestamps();
            
            $table->unique(['short_key']);
            $table->index(['group_id', 'short_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_short_keys');
    }
};
