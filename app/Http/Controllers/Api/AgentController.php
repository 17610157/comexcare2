<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentVersion;
use App\Models\Command;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\DistributionFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    public function register(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (! $data) {
                return response()->json(['error' => 'Invalid JSON', 'raw' => $request->getContent()], 400);
            }

            Log::info('Agent registration request', $data);

            $validator = Validator::make($data, [
                'computer_name' => 'required|string|max:255',
                'mac_address' => 'required|string',
                'agent_version' => 'required|string',
                'system_info' => 'nullable|array',
                'download_path' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()->toArray(), 'data' => $data], 422);
            }

            $existingWithMac = Computer::withTrashed()->where('mac_address', $data['mac_address'])->first();

            if ($existingWithMac) {
                $existingWithMac->restore();
                $existingWithMac->update([
                    'computer_name' => $data['computer_name'],
                    'ip_address' => $request->ip(),
                    'agent_version' => $data['agent_version'],
                    'status' => 'online',
                    'last_seen' => now(),
                    'system_info' => $data['system_info'] ?? null,
                    'download_path' => $data['download_path'] ?? 'C:\ProgramData\DistributionAgent\files',
                    'deleted_at' => null,
                ]);
                $computer = $existingWithMac->fresh();
            } else {
                $computer = Computer::create([
                    'computer_name' => $data['computer_name'],
                    'mac_address' => $data['mac_address'],
                    'ip_address' => $request->ip(),
                    'agent_version' => $data['agent_version'],
                    'status' => 'online',
                    'last_seen' => now(),
                    'system_info' => $data['system_info'] ?? null,
                    'download_path' => $data['download_path'] ?? 'C:\ProgramData\DistributionAgent\files',
                ]);
            }

            return response()->json(['id' => $computer->id, 'message' => 'Registered successfully']);
        } catch (\Exception $e) {
            Log::error('Registration error', ['exception' => $e->getMessage(), 'data' => $data ?? null]);

            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    public function heartbeat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer',
            'agent_version' => 'required|string',
            'system_info' => 'nullable|array',
            'logs' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $computer = Computer::find($request->computer_id);

        if (! $computer) {
            $computerWithMac = Computer::withTrashed()->where('id', $request->computer_id)->first();
            if ($computerWithMac && $computerWithMac->trashed()) {
                return response()->json([
                    'error' => 'Computer was deleted. Please re-register.',
                    'needs_registration' => true,
                    'mac_address' => $computerWithMac->mac_address,
                ], 404);
            }

            return response()->json(['error' => 'Computer not found. Please register first.'], 404);
        }
        $computer->update([
            'status' => 'online',
            'last_seen' => now(),
            'agent_version' => $request->agent_version,
            'ip_address' => $request->ip(),
            'system_info' => $request->system_info ?? $computer->system_info,
        ]);

        // Process logs from the agent
        if ($request->filled('logs')) {
            $logLines = explode("\n", $request->logs);
            foreach ($logLines as $line) {
                $line = trim($line);
                if (empty($line)) {
                    continue;
                }

                // Parse log level from line (e.g., "[2026-02-27 09:12:15.736] INFO: message")
                $level = 'info';
                if (preg_match('/\[([\d\s:.]+)\]\s+(INFO|WARN|ERROR|DEBUG)/i', $line, $matches)) {
                    $level = strtolower($matches[2]);
                }

                ComputerLog::create([
                    'computer_id' => $computer->id,
                    'level' => $level,
                    'message' => $line,
                ]);
            }
        }

        return response()->json([
            'message' => 'Heartbeat received',
            'computer_name' => $computer->computer_name,
            'download_path' => $computer->download_path ?? 'C:\ProgramData\DistributionAgent\files',
            'download_paths' => $computer->getAllDownloadPaths(),
        ]);
    }

    public function getCommands(Request $request, $id)
    {
        Log::info('getCommands request', ['computer_id' => $id]);

        $computer = Computer::findOrFail($id);

        $commands = Command::where('computer_id', $id)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        Log::info('Commands found', ['count' => $commands->count(), 'computer_id' => $id]);

        $commandsArray = [];

        foreach ($commands as $command) {
            $command->update(['status' => 'sent', 'sent_at' => now()]);

            $data = is_array($command->data) ? $command->data : json_decode($command->data, true);

            $fileName = null;
            if (! empty($data['file_id'])) {
                $file = DistributionFile::find($data['file_id']);
                if ($file) {
                    $fileName = $file->file_name;
                }
            }

            $commandArray = [
                'id' => $command->id,
                'computer_id' => $command->computer_id,
                'type' => $command->type,
                'file_id' => $data['file_id'] ?? null,
                'file_name' => $fileName,
                'distribution_target_id' => $data['distribution_target_id'] ?? null,
                'status' => 'sent',
            ];

            if ($command->type === 'distribute' || $command->type === 'update') {
                $commandArray['download_paths'] = $computer->getAllDownloadPaths();
            }

            $commandsArray[] = $commandArray;
        }

        return response()->json($commandsArray);
    }

    public function report(Request $request)
    {
        Log::info('Report received', $request->all());

        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer|exists:computers,id',
            'command_id' => 'nullable|integer|exists:commands,id',
            'distribution_target_id' => 'nullable|integer|exists:distribution_targets,id',
            'file_id' => 'nullable|integer|exists:distribution_files,id',
            'status' => 'required|in:completed,failed',
            'progress' => 'nullable|integer|min:0|max:100',
            'response' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        if ($request->command_id) {
            $command = Command::find($request->command_id);
            $command->update([
                'status' => $request->status,
                'completed_at' => now(),
                'response' => $request->response,
            ]);
        }

        if ($request->progress !== null) {
            $targetId = null;

            if ($request->distribution_target_id) {
                $targetId = $request->distribution_target_id;
            } elseif ($request->file_id) {
                $target = \App\Models\DistributionTarget::where('distribution_id', function ($query) use ($request) {
                    $query->select('distribution_id')
                        ->from('distribution_files')
                        ->where('id', $request->file_id);
                })->where('computer_id', $request->computer_id)->first();
                if ($target) {
                    $targetId = $target->id;
                }
            } elseif ($request->command_id) {
                $command = Command::find($request->command_id);
                $data = is_array($command->data) ? $command->data : json_decode($command->data, true);
                $targetId = $data['distribution_target_id'] ?? null;
            }

            if ($targetId) {
                $target = \App\Models\DistributionTarget::find($targetId);
                if ($target) {
                    $target->update([
                        'progress' => $request->progress,
                        'status' => $request->status === 'completed' ? 'completed' : 'failed',
                    ]);

                    $distribution = $target->distribution;
                    $allTargets = $distribution->targets;
                    $completedCount = $allTargets->where('status', 'completed')->count();
                    $failedCount = $allTargets->where('status', 'failed')->count();
                    $totalCount = $allTargets->count();

                    if ($completedCount + $failedCount === $totalCount) {
                        $distribution->update(['status' => $completedCount === $totalCount ? 'completed' : 'failed']);
                    }
                }
            }
        }

        return response()->json(['message' => 'Report received']);
    }

    public function download(Request $request, $fileId)
    {
        $file = DistributionFile::findOrFail($fileId);

        if (! Storage::disk('public')->exists($file->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::disk('public')->download($file->file_path, $file->file_name);
    }

    public function checkUpdate(Request $request, $version)
    {
        $current = AgentVersion::where('version', $version)->first();
        $latest = AgentVersion::where('is_active', true)->orderBy('created_at', 'desc')->first();

        if (! $latest || $current && $current->id >= $latest->id) {
            return response()->json(['update_available' => false]);
        }

        $filename = basename($latest->file_path);

        return response()->json([
            'update_available' => true,
            'version' => $latest->version,
            'download_url' => url('agent-updates/'.$filename),
            'channel' => $latest->channel,
            'checksum' => $latest->checksum,
            'changelog' => $latest->changelog,
        ]);
    }

    public function checkUpdateByComputerId(Request $request, $computer_id)
    {
        $computer = Computer::find($computer_id);

        if (! $computer) {
            return response()->json(['error' => 'Computer not found'], 404);
        }

        $currentVersion = $computer->agent_version;
        $current = AgentVersion::where('version', $currentVersion)->first();
        $latest = AgentVersion::where('is_active', true)->orderBy('created_at', 'desc')->first();

        if (! $latest || $current && $current->id >= $latest->id) {
            return response()->json(['update_available' => false]);
        }

        $filename = basename($latest->file_path);

        return response()->json([
            'update_available' => true,
            'version' => $latest->version,
            'download_url' => url('agent-updates/'.$filename),
            'channel' => $latest->channel,
            'checksum' => $latest->checksum,
            'changelog' => $latest->changelog,
        ]);
    }

    public function inventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer|exists:computers,id',
            'inventory' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $computer = Computer::find($request->computer_id);
        $computer->update(['agent_config' => array_merge($computer->agent_config ?? [], ['inventory' => $request->inventory])]);

        return response()->json(['message' => 'Inventory received']);
    }

    public function logs(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer|exists:computers,id',
            'logs' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        foreach ($request->logs as $log) {
            \App\Models\ComputerLog::create([
                'computer_id' => $request->computer_id,
                'level' => $log['level'] ?? 'info',
                'message' => $log['message'] ?? '',
            ]);
        }

        return response()->json(['message' => 'Logs received', 'count' => count($request->logs)]);
    }
}
