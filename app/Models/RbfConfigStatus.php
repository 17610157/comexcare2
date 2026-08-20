<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RbfConfigStatus extends Model
{
    protected $fillable = ['pl', 'rs', 'ti', 'ca', 'li', 'of', 'pr', 'co', 'ex', 'db', 'pv', 'us', 'synced_at'];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
