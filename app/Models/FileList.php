<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FileList extends Model
{
    protected $fillable = [
        'type',
        'file_name',
        'description',
        'created_by',
        'status',
        'module_id',
        'authorization_token',
        'token_expires_at',
    ];

    protected function casts(): array
    {
        return [
            'token_expires_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function authorizations(): HasMany
    {
        return $this->hasMany(FileListAuthorization::class);
    }

    public function generateAuthorizationToken(): string
    {
        $token = Str::random(64);

        $this->update([
            'authorization_token' => $token,
            'token_expires_at' => now()->addHours(48),
        ]);

        return $token;
    }

    public function isTokenValid(): bool
    {
        return $this->authorization_token !== null
            && $this->token_expires_at !== null
            && $this->token_expires_at->isFuture();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
