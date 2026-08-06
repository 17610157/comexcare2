<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRequest extends Model
{
    protected $table = 'api_demo';

    protected $fillable = [
        'param1',
        'param2',
    ];
}
