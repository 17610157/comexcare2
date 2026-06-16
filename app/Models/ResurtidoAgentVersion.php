<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResurtidoAgentVersion extends Model
{
    use HasFactory;

    protected $table = 'resurtido_agent_versions';

    protected $fillable = [
        'version',
        'channel',
        'file_path',
        'checksum',
        'changelog',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->whereRaw('"is_active" = true');
    }

    public function getFilesAttribute()
    {
        $changelogData = json_decode($this->changelog, true);
        if (isset($changelogData['files']) && is_array($changelogData['files'])) {
            return $changelogData['files'];
        }

        return [];
    }

    public function getChangelogNotesAttribute()
    {
        $changelogData = json_decode($this->changelog, true);

        return $changelogData['notes'] ?? $this->changelog;
    }
}
