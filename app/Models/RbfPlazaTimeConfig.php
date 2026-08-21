<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RbfPlazaTimeConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'plaza',
        'meridiano',
        'zona_horaria',
    ];

    protected function casts(): array
    {
        return [
            'meridiano' => 'integer',
            'zona_horaria' => 'integer',
        ];
    }

    public static function offsetsByPlaza(): array
    {
        return static::query()
            ->get()
            ->mapWithKeys(fn (self $config) => [
                strtolower(trim($config->plaza)) => $config->zona_horaria - $config->meridiano,
            ])
            ->all();
    }
}
