<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CortePlazaEmailConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'plaza',
        'emails',
        'envio_activo',
        'hora_envio',
        'ultimo_envio_at',
    ];

    protected function casts(): array
    {
        return [
            'emails' => 'array',
            'envio_activo' => 'boolean',
            'hora_envio' => 'integer',
            'ultimo_envio_at' => 'datetime',
        ];
    }
}
