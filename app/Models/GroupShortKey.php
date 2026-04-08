<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupShortKey extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'short_key'];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
