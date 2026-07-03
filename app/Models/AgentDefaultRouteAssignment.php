<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDefaultRouteAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_default_category_route_id',
        'assignable_id',
        'assignable_type',
    ];

    public function route()
    {
        return $this->belongsTo(AgentDefaultCategoryRoute::class, 'agent_default_category_route_id');
    }

    public function assignable()
    {
        return $this->morphTo();
    }
}
