<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bitacora extends Model
{
    use HasFactory;

    protected $fillable = [
        'empleado_id',
        'fecha',
        'descripcion',
        'categoria',
        'hora_inicio',
        'hora_fin',
        'archivo',
        'archivo_nombre',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
