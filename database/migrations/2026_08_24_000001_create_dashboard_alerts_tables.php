<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->string('metric_key')->unique();
            $table->string('label');
            $table->string('comparator', 12)->default('gt');
            $table->unsignedTinyInteger('threshold_pct')->nullable();
            $table->string('severity', 12)->default('warning');
            $table->boolean('enabled')->default(true);
            $table->unsignedTinyInteger('cooldown_min')->default(15);
            $table->string('sound_path')->nullable();
            $table->timestamps();
        });

        Schema::create('dashboard_alert_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rule_id')->constrained('dashboard_alert_rules')->cascadeOnDelete();
            $table->decimal('value_pct', 6, 2)->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->timestamps();
        });

        DB::table('dashboard_alert_rules')->insert([
            [
                'metric_key' => 'fleet_online',
                'label' => 'Equipos con comunicación',
                'comparator' => 'lt',
                'threshold_pct' => 90,
                'severity' => 'critical',
                'enabled' => DB::raw('true'),
                'cooldown_min' => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'metric_key' => 'dbf_service_level',
                'label' => 'Nivel de servicio reporte de precios (DBF)',
                'comparator' => 'lt',
                'threshold_pct' => 95,
                'severity' => 'warning',
                'enabled' => DB::raw('true'),
                'cooldown_min' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'metric_key' => 'srv_cpu',
                'label' => 'CPU del servidor',
                'comparator' => 'gt',
                'threshold_pct' => 60,
                'severity' => 'warning',
                'enabled' => DB::raw('true'),
                'cooldown_min' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'metric_key' => 'admin_changes',
                'label' => 'Cambios en usuarios, roles y permisos',
                'comparator' => 'event',
                'threshold_pct' => null,
                'severity' => 'info',
                'enabled' => DB::raw('true'),
                'cooldown_min' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_alert_events');
        Schema::dropIfExists('dashboard_alert_rules');
    }
};
