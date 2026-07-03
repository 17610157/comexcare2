<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentDefaultDownload extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_default_category_file_id',
        'computer_id',
        'downloaded_at',
        'local_path',
        'local_checksum',
        'ruta_local',
        'ruta_servidor',
        'sync_status',
        'synced_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function file()
    {
        return $this->belongsTo(AgentDefaultCategoryFile::class, 'agent_default_category_file_id');
    }

    public function computer()
    {
        return $this->belongsTo(Computer::class);
    }
}
