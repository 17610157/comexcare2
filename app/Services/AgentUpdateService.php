<?php

namespace App\Services;

use App\Models\AgentVersion;
use App\Models\Command;
use App\Models\Computer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AgentUpdateService
{
    public function createVersion(array $data): AgentVersion
    {
        $filesData = [];

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('agent_updates', 'public');
                    $filesData[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'checksum' => hash_file('sha256', Storage::disk('public')->path($path)),
                        'size' => $file->getSize(),
                    ];
                }
            }
        }

        $mainFile = $filesData[0] ?? null;

        $channel = $data['channel'] ?? 'stable';

        DB::table('agent_versions')
            ->where('channel', $channel)
            ->whereRaw('"is_active" = true')
            ->update([
                'is_active' => DB::raw('false'),
                'updated_at' => now(),
            ]);

        $id = DB::table('agent_versions')->insertGetId([
            'version' => $data['version'],
            'channel' => $channel,
            'file_path' => $mainFile['path'] ?? null,
            'checksum' => $mainFile['checksum'] ?? null,
            'changelog' => ! empty($filesData) ? json_encode(['files' => $filesData, 'notes' => $data['changelog'] ?? '']) : ($data['changelog'] ?? null),
            'is_active' => DB::raw('true'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return AgentVersion::findOrFail($id);
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
            ->whereRaw('"is_active" = true')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function deactivateVersion(AgentVersion $version)
    {
        DB::table('agent_versions')
            ->where('id', $version->id)
            ->update([
                'is_active' => DB::raw('false'),
                'updated_at' => now(),
            ]);
    }
}
