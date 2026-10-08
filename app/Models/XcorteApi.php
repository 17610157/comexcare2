<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XcorteApi extends Model
{
    use HasFactory;

    protected $table = 'xcorte_api';

    protected $fillable = [
        'fecha_corte',
        'clave_tienda',
        'monto_contado',
        'monto_credito',
        'fecha_registro',
        'plaza',
        'computer_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_registro' => 'datetime',
            'monto_contado' => 'decimal:5',
            'monto_credito' => 'decimal:5',
        ];
    }

    public function computer(): BelongsTo
    {
        return $this->belongsTo(Computer::class);
    }
}
