<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComputerLog extends Model
{
    protected $table = 'computer_logs';

    protected $fillable = [
        'computer_id',
        'level',
        'message',
    ];
}
