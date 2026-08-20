<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rbf_config_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('pl', 20)->index();
            $table->string('rs', 20)->index();
            $table->string('ti', 10)->index();
            $table->string('ca', 50)->index();
            $table->string('li', 50)->nullable()->index();
            $table->string('of', 50)->nullable()->index();
            $table->string('pr', 50)->nullable()->index();
            $table->string('co', 50)->nullable()->index();
            $table->string('ex', 50)->nullable()->index();
            $table->string('db', 50)->nullable()->index();
            $table->string('pv', 50)->nullable()->index();
            $table->string('us', 50)->nullable()->index();
            $table->timestamp('synced_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rbf_config_statuses');
    }
};
