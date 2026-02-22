<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetasMensual extends Model
{
    use HasFactory;

    protected $table = 'metas_mensual';
    public $timestamps = false;

    protected $fillable = [
        'plaza',
        'tienda',
        'periodo',
        'meta',
    ];
}
