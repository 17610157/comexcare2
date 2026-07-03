<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDefaultCategoryRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_default_category_id',
        'route_pattern',
        'label',
        'download_path_index',
        'sort_order',
    ];

    protected $casts = [
        'download_path_index' => 'integer',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(AgentDefaultCategory::class, 'agent_default_category_id');
    }

    public function assignments()
    {
        return $this->hasMany(AgentDefaultRouteAssignment::class, 'agent_default_category_route_id');
    }

    public function files()
    {
        return $this->hasMany(AgentDefaultCategoryFile::class, 'agent_default_category_route_id');
    }
}
