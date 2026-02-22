<?php

namespace App\Services;

use App\Models\AgentVersion;
use App\Models\Computer;
use App\Models\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AgentUpdateService
{
    public function createVersion(array $data): AgentVersion
    {
        $path = $data['file']->store('agent_updates');

        return AgentVersion::create([
            'version' => $data['version'],
            'channel' => $data['channel'] ?? 'stable',
            'file_path' => $path,
            'checksum' => hash_file('sha256', Storage::path($path)),
            'changelog' => $data['changelog'] ?? null,
            'is_active' => true,
        ]);
    }

    public function deployUpdate(Computer $computer, AgentVersion $version)
    {
        // Send update command
        Command::create([
            'computer_id' => $computer->id,
            'type' => 'update',
            'data' => [
                'version' => $version->version,
                'file_id' => null, // Or create a file entry if needed
                'checksum' => $version->checksum,
            ],
        ]);

        $computer->update(['status' => 'updating']);
    }

    public function rollback(Computer $computer, string $previousVersion)
    {
        $previous = AgentVersion::where('version', $previousVersion)->first();
        if ($previous) {
            $this->deployUpdate($computer, $previous);
        }

        Log::warning("Agent rollback initiated for computer {$computer->id} to version {$previousVersion}");
    }

    public function getLatestVersion(string $channel = 'stable'): ?AgentVersion
    {
        return AgentVersion::where('channel', $channel)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function deactivateVersion(AgentVersion $version)
    {
        $version->update(['is_active' => false]);
    }
}