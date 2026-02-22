<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPlazaTienda extends Model
{
    protected $table = 'user_plaza_tiendas';

    protected $fillable = [
        'user_id',
        'plaza',
        'tienda',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
