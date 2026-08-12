<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HashArchivoLote extends Model
{
    use HasFactory;

    protected $table = 'hash_archivos_lotes';

    protected $fillable = [
        'cliente',
        'sucursal',
        'nombre_carpeta',
        'ruta_base',
        'fecha_envio',
        'disparador',
        'num_archivos',
        'peso_total',
        'estado',
        'payload',
        'errores',
        'ip',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'fecha_envio' => 'datetime',
            'errores' => 'array',
        ];
    }
}
