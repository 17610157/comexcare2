<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HashArchivoHistorial extends Model
{
    use HasFactory;

    protected $table = 'hash_archivos_historial';

    protected $fillable = [
        'sucursal',
        'ip',
        'archivo',
        'md5',
        'md5_completo',
        'disparador',
        'fecha_modificacion',
        'fecha_consulta_api',
    ];

    protected function casts(): array
    {
        return [];
    }
}
