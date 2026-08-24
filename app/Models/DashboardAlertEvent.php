<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardAlertEvent extends Model
{
    protected $table = 'dashboard_alert_events';

    protected $fillable = [
        'rule_id',
        'value_pct',
        'message',
        'meta',
        'triggered_at',
        'acknowledged_at',
        'acknowledged_by',
    ];

    protected $casts = [
        'value_pct' => 'float',
        'meta' => 'array',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
    ];

    public function rule()
    {
        return $this->belongsTo(DashboardAlertRule::class, 'rule_id');
    }
}
