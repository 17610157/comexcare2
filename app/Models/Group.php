<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'type', 'description'];

    public function computers()
    {
        return $this->hasMany(Computer::class);
    }

    public function shortKeys()
    {
        return $this->hasMany(GroupShortKey::class);
    }

    public function getShortKeyListAttribute()
    {
        return $this->shortKeys->pluck('short_key')->toArray();
    }

    public static function findByShortKey(string $shortKey): ?self
    {
        return static::join('group_short_keys', 'groups.id', '=', 'group_short_keys.group_id')
            ->where('group_short_keys.short_key', $shortKey)
            ->select('groups.*')
            ->first();
    }
}
