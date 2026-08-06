<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuthorizationToken extends Model
{
    protected $fillable = [
        'file_list_id',
        'authorizable_email_id',
        'token',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function fileList(): BelongsTo
    {
        return $this->belongsTo(FileList::class);
    }

    public function authorizableEmail(): BelongsTo
    {
        return $this->belongsTo(AuthorizableEmail::class);
    }

    public function isValid(): bool
    {
        return $this->used_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture();
    }

    public static function generate(): string
    {
        return Str::random(64);
    }
}
