<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsesoresVvt extends Model
{
    protected $table = 'asesores_vvt';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'plaza',
        'asesor',
        'nombre',
        'tipo',
        'estatus'
    ];

    /**
     * Relación con canota
     */
    public function canotas()
    {
        return $this->hasMany(Canota::class, 'vend_clave', 'asesor')
                    ->whereColumn('cplaza', 'plaza');
    }

    
}