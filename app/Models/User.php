<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'plaza',
        'tienda',
        'rol',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function plazaTiendas()
    {
        return $this->hasMany(UserPlazaTienda::class);
    }

    public function getPlazasAttribute()
    {
        return $this->plazaTiendas()->pluck('plaza')->filter()->unique()->values();
    }

    public function getTiendasAttribute()
    {
        return $this->plazaTiendas()->pluck('tienda')->filter()->unique()->values();
    }
}
