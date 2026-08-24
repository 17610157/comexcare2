<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardAlertRule extends Model
{
    protected $table = 'dashboard_alert_rules';

    protected $fillable = [
        'metric_key',
        'label',
        'comparator',
        'threshold_pct',
        'severity',
        'enabled',
        'cooldown_min',
        'sound_path',
    ];

    protected $casts = [
        'threshold_pct' => 'integer',
        'enabled' => 'boolean',
        'cooldown_min' => 'integer',
    ];
}
