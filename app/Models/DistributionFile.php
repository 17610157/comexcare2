<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_id',
        'file_name',
        'file_path',
        'checksum',
        'file_size'
    ];

    public function distribution()
    {
        return $this->belongsTo(Distribution::class);
    }
}