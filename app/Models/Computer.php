<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Computer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'computer_name',
        'mac_address',
        'ip_address',
        'group_id',
        'agent_version',
        'last_seen',
        'status',
        'system_info',
        'agent_config'
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'system_info' => 'array',
        'agent_config' => 'array',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function distributionTargets()
    {
        return $this->hasMany(DistributionTarget::class);
    }

    public function commands()
    {
        return $this->hasMany(Command::class);
    }
}