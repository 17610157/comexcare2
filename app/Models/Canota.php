<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Canota extends Model
{
    protected $table = 'canota';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nota_fecha',
        'cplaza',
        'ctienda',
        'vend_clave',
        'nota_impor',
        'ban_status'
    ];
}