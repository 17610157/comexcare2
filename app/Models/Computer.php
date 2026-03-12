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
        'agent_config',
        'download_path',
        'download_path_1',
        'download_path_2',
        'download_path_3',
        'download_path_4',
        'download_path_5',
        'download_path_6',
        'download_path_7',
        'download_path_8',
        'download_path_9',
        'download_path_10',
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

    public function logs()
    {
        return $this->hasMany(ComputerLog::class);
    }

    public function getAllDownloadPaths(): array
    {
        $paths = [];

        if ($this->download_path) {
            $paths[] = $this->download_path;
        }

        for ($i = 1; $i <= 10; $i++) {
            $path = $this->{"download_path_{$i}"};
            if ($path) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
