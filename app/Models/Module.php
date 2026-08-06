<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function authorizableEmails(): HasMany
    {
        return $this->hasMany(AuthorizableEmail::class);
    }

    public function fileLists(): HasMany
    {
        return $this->hasMany(FileList::class);
    }
}
