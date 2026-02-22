<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_id',
        'computer_id',
        'status',
        'progress',
        'attempts',
        'next_retry_at',
        'error_message'
    ];

    protected $casts = [
        'next_retry_at' => 'datetime',
    ];

    public function distribution()
    {
        return $this->belongsTo(Distribution::class);
    }

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }
}