<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConciliacionHashArchivo extends Model
{
    use HasFactory;

    protected $fillable = [
        'sucursal',
        'archivo',
        'md5',
        'fecha_modificacion',
        'disparador',
        'fecha_consulta_api',
    ];

    protected function casts(): array
    {
        return [
            'fecha_modificacion' => 'datetime',
            'fecha_consulta_api' => 'datetime',
        ];
    }
}
