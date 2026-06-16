<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vale extends Model
{
    use HasFactory;

    protected $fillable = [
        'tipo_movim',
        'no_consec',
        'fecha',
        'cve_pro_cl',
        'desc_mov',
        'ent_sal',
        'observinv',
        'almacen',
        'afectado',
        'estado',
        'fechacort',
        'precio_tot',
        'impues_tot',
        'cost_tot',
        'no_partida',
        'mov_origen',
        'ya_exporta',
        'clave_usu',
        'campo1c',
        'campo2n',
        'folio_ref',
        'lista_prec',
        'ccampo1',
        'ncampo2',
        'dcampo3',
        'lcampo4',
        'tienda',
        'modhora',
        'modfecha',
        'moduser',
        'computer_id',
        'plaza',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fechacort' => 'date',
        'dcampo3' => 'date',
        'modfecha' => 'date',
        'precio_tot' => 'decimal:5',
        'impues_tot' => 'decimal:5',
        'cost_tot' => 'decimal:5',
        'no_partida' => 'decimal:5',
        'campo2n' => 'decimal:5',
        'ncampo4' => 'decimal:5',
    ];

    // Mutator para lcampo4 (columna integer en PostgreSQL)
    public function setLcampo4Attribute($value)
    {
        $this->attributes['lcampo4'] = ($value === '1' || $value === 1 || $value === true || $value === 'true') ? 1 : 0;
    }

    // Mutators para varchar, convertir explicitamente a string
    public function setAfectadoAttribute($value)
    {
        $this->attributes['afectado'] = ($value === '1' || $value === 1 || $value === true || $value === 'true') ? '1' : '0';
    }

    public function setEstadoAttribute($value)
    {
        $this->attributes['estado'] = ($value === '1' || $value === 1 || $value === true || $value === 'true') ? '1' : '0';
    }

    public function setYaExportaAttribute($value)
    {
        $this->attributes['ya_exporta'] = ($value === '1' || $value === 1 || $value === true || $value === 'true') ? '1' : '0';
    }
}
