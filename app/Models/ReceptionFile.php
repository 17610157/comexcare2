<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceptionFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'reception_id',
        'file_name',
        'file_path',
        'file_size',
    ];

    public function reception()
    {
        return $this->belongsTo(Reception::class);
    }
}
