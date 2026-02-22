<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'version',
        'channel',
        'file_path',
        'checksum',
        'changelog',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}