<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaMensual extends Model
{
    use HasFactory;

    protected $table = 'metas_mensual';
    
    protected $fillable = [
        'plaza',
        'tienda', 
        'periodo',
        'meta'
    ];

    protected $casts = [
        'meta' => 'decimal:4',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Scope para filtrar por periodo
     */
    public function scopeByPeriodo($query, $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    /**
     * Scope para filtrar por plaza
     */
    public function scopeByPlaza($query, $plaza)
    {
        return $query->where('plaza', $plaza);
    }

    /**
     * Scope para filtrar por tienda
     */
    public function scopeByTienda($query, $tienda)
    {
        return $query->where('tienda', $tienda);
    }
}