<?php

namespace App\Services;

use App\Models\Command;
use App\Models\Computer;
use App\Models\ResurtidoAgentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ResurtidoAgentUpdateService
{
    public function createVersion(array $data): ResurtidoAgentVersion
    {
        $filesData = [];
        $file = $data['file'] ?? null;

        if ($file && $file->isValid()) {
            $path = $file->store('resurtido_agent_updates', 'public');
            $filesData[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'checksum' => hash_file('sha256', Storage::disk('public')->path($path)),
                'size' => $file->getSize(),
            ];
        }

        $mainFile = $filesData[0] ?? null;

        $channel = $data['channel'] ?? 'stable';

        DB::table('resurtido_agent_versions')
            ->where('channel', $channel)
            ->where('is_active', true)
            ->update([
                'is_active' => DB::raw('false'),
                'updated_at' => now(),
            ]);

        $version = ResurtidoAgentVersion::create([
            'version' => $data['version'],
            'channel' => $channel,
            'file_path' => $mainFile['path'] ?? null,
            'checksum' => $mainFile['checksum'] ?? null,
            'changelog' => ! empty($filesData) ? json_encode(['files' => $filesData, 'notes' => $data['changelog'] ?? '']) : ($data['changelog'] ?? null),
            'is_active' => true,
        ]);

        return $version;
    }

    public function deployUpdate(Computer $computer, ResurtidoAgentVersion $version)
    {
        Command::create([
            'computer_id' => $computer->id,
            'type' => 'resurtido_update',
            'data' => [
                'version' => $version->version,
                'checksum' => $version->checksum,
            ],
        ]);

        $computer->update(['status' => 'updating']);
    }

    public function rollback(Computer $computer, string $previousVersion)
    {
        $previous = ResurtidoAgentVersion::where('version', $previousVersion)->first();
        if ($previous) {
            $this->deployUpdate($computer, $previous);
        }

        Log::warning("Resurtido Agent rollback initiated for computer {$computer->id} to version {$previousVersion}");
    }

    public function getLatestVersion(string $channel = 'stable'): ?ResurtidoAgentVersion
    {
        return ResurtidoAgentVersion::where('channel', $channel)
            ->active()
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function deactivateVersion(ResurtidoAgentVersion $version)
    {
        DB::table('resurtido_agent_versions')
            ->where('id', $version->id)
            ->update([
                'is_active' => DB::raw('false'),
                'updated_at' => now(),
            ]);
    }
}
