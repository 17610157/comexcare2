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
        'scheduled_at'
    ];

    protected $casts = [
        'schedule' => 'array',
        'scheduled_at' => 'datetime',
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