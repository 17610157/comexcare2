<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RbfFileHash extends Model
{
    use HasFactory;

    protected $fillable = [
        'servicio',
        'plaza',
        'zona',
        'path',
        'name',
        'hash',
        'last_modified',
        'last_sync',
    ];

    protected function casts(): array
    {
        return [
            'last_modified' => 'datetime',
            'last_sync' => 'datetime',
        ];
    }
}
