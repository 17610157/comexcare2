<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Computer;
use App\Models\Command;
use App\Models\DistributionFile;
use App\Models\AgentVersion;
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
            if (!$data) {
                return response()->json(['error' => 'Invalid JSON', 'raw' => $request->getContent()], 400);
            }

            $validator = Validator::make($data, [
                'computer_name' => 'required|string|max:255',
                'mac_address' => 'required|string',
                'agent_version' => 'required|string',
                'system_info' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()->toArray(), 'data' => $data], 422);
            }

            $computer = Computer::updateOrCreate(
                ['mac_address' => $data['mac_address']],
                [
                    'computer_name' => $data['computer_name'],
                    'ip_address' => $request->ip(),
                    'agent_version' => $data['agent_version'],
                    'status' => 'online',
                    'last_seen' => now(),
                    'system_info' => $data['system_info'] ?? null,
                ]
            );

            return response()->json(['id' => $computer->id, 'message' => 'Registered successfully']);
        } catch (\Exception $e) {
            Log::error('Registration error', ['exception' => $e->getMessage(), 'data' => $data ?? null]);
            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    public function heartbeat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer|exists:computers,id',
            'agent_version' => 'required|string',
            'system_info' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $computer = Computer::find($request->computer_id);
        $computer->update([
            'status' => 'online',
            'last_seen' => now(),
            'agent_version' => $request->agent_version,
            'ip_address' => $request->ip(),
            'system_info' => $request->system_info ?? $computer->system_info,
        ]);

        return response()->json(['message' => 'Heartbeat received']);
    }

    public function getCommands(Request $request, $id)
    {
        $computer = Computer::findOrFail($id);

        $commands = Command::where('computer_id', $id)
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        foreach ($commands as $command) {
            $command->update(['status' => 'sent', 'sent_at' => now()]);
        }

        return response()->json($commands);
    }

    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer|exists:computers,id',
            'command_id' => 'nullable|integer|exists:commands,id',
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

        if ($request->progress !== null && $request->command_id) {
            $command = Command::find($request->command_id);
            if ($command->type === 'download' && isset($command->data['distribution_target_id'])) {
                $target = \App\Models\DistributionTarget::find($command->data['distribution_target_id']);
                if ($target) {
                    $target->update([
                        'progress' => $request->progress,
                        'status' => $request->status === 'completed' ? 'completed' : 'failed',
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Report received']);
    }

    public function download(Request $request, $fileId)
    {
        $file = DistributionFile::findOrFail($fileId);

        if (!Storage::exists($file->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        return Storage::download($file->file_path, $file->file_name);
    }

    public function checkUpdate(Request $request, $version)
    {
        $current = AgentVersion::where('version', $version)->first();
        $latest = AgentVersion::where('is_active', true)->orderBy('created_at', 'desc')->first();

        if (!$latest || $current && $current->id >= $latest->id) {
            return response()->json(['update_available' => false]);
        }

        return response()->json([
            'update_available' => true,
            'version' => $latest->version,
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
}