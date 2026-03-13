<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reception extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'scheduled_at',
        'scheduled_time',
        'recurrence',
        'frequency_type',
        'frequency_interval',
        'week_days',
        'file_types',
        'specific_files',
        'all_files',
        'status',
        'group_id',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'week_days' => 'array',
        'file_types' => 'array',
        'specific_files' => 'array',
        'all_files' => 'boolean',
    ];

    public function computers()
    {
        return $this->belongsToMany(Computer::class, 'reception_targets');
    }

    public function targets()
    {
        return $this->hasMany(ReceptionTarget::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function files()
    {
        return $this->hasMany(ReceptionFile::class);
    }
}
