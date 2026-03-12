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
        'recurrence',
        'status',
        'group_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
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
