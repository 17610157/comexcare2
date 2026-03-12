<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceptionTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'reception_id',
        'computer_id',
        'status',
        'progress',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }
}
