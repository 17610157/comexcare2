<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDefaultCategoryFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_default_category_route_id',
        'file_name',
        'file_path',
        'checksum',
        'file_size',
    ];

    public function route()
    {
        return $this->belongsTo(AgentDefaultCategoryRoute::class, 'agent_default_category_route_id');
    }

    public function downloads()
    {
        return $this->hasMany(AgentDefaultDownload::class, 'agent_default_category_file_id');
    }
}
