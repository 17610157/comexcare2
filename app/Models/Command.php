<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    use HasFactory;

    protected $fillable = [
        'computer_id',
        'type',
        'data',
        'status',
        'sent_at',
        'completed_at',
        'response'
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }
}