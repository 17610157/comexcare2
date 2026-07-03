<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDefaultCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
        'auto_sync',
        'auto_validation',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
        'auto_validation' => 'boolean',
    ];

    public function routes()
    {
        return $this->hasMany(AgentDefaultCategoryRoute::class, 'agent_default_category_id');
    }

    public function files()
    {
        return $this->hasManyThrough(
            AgentDefaultCategoryFile::class,
            AgentDefaultCategoryRoute::class,
            'agent_default_category_id',
            'agent_default_category_route_id',
        );
    }
}
