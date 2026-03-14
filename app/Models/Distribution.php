<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'schedule',
        'description',
        'created_by',
        'status',
        'scheduled_at',
        'scheduled_time',
        'recurrence',
        'frequency_type',
        'frequency_interval',
        'week_days',
        'last_run_at',
    ];

    protected $casts = [
        'schedule' => 'array',
        'scheduled_at' => 'datetime',
        'last_run_at' => 'datetime',
        'week_days' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files()
    {
        return $this->hasMany(DistributionFile::class);
    }

    public function targets()
    {
        return $this->hasMany(DistributionTarget::class);
    }
}
