<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileListAuthorization extends Model
{
    protected $fillable = [
        'file_list_id',
        'user_id',
        'email',
        'ip_address',
        'notes',
        'authorized_at',
    ];

    protected function casts(): array
    {
        return [
            'authorized_at' => 'datetime',
        ];
    }

    public function fileList(): BelongsTo
    {
        return $this->belongsTo(FileList::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
