<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentVersion extends Model
{
    use HasFactory;

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
