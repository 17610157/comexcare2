<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Command;
use App\Models\Computer;
use App\Models\ResurtidoAgentVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ResurtidoAgentController extends Controller
{
    public function register(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (! $data) {
                return response()->json(['error' => 'Invalid JSON'], 400);
            }

            Log::info('Resurtido Agent registration request', $data);

            $validator = Validator::make($data, [
                'computer_name' => 'required|string|max:255',
                'mac_address' => 'required|string',
                'version' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
            }

            $existingWithMac = Computer::withTrashed()->where('mac_address', $data['mac_address'])->first();

            if ($existingWithMac) {
                $existingWithMac->restore();
                $existingWithMac->update([
                    'computer_name' => $data['computer_name'],
                    'ip_address' => $request->ip(),
                    'resurtido_agent_version' => $data['version'],
                    'status' => 'online',
                    'last_seen' => now(),
                    'deleted_at' => null,
                ]);
                $computer = $existingWithMac->fresh();
            } else {
                $computer = Computer::create([
                    'computer_name' => $data['computer_name'],
                    'mac_address' => $data['mac_address'],
                    'ip_address' => $request->ip(),
                    'resurtido_agent_version' => $data['version'],
                    'status' => 'online',
                    'last_seen' => now(),
                ]);
            }

            return response()->json([
                'id' => $computer->id,
                'message' => 'Registered successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Resurtido registration error', ['exception' => $e->getMessage()]);

            return response()->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }

    public function heartbeat(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer',
            'version' => 'required|string',
            'status' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed'], 422);
        }

        $computer = Computer::find($request->computer_id);

        if (! $computer) {
            return response()->json(['error' => 'Computer not found'], 404);
        }

        $computer->update([
            'status' => 'online',
            'last_seen' => now(),
            'resurtido_agent_version' => $request->version,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Heartbeat received',
            'computer_name' => $computer->computer_name,
        ]);
    }

    public function checkUpdate(Request $request)
    {
        $computerId = $request->computer_id;

        if (! $computerId) {
            return response()->json(['error' => 'computer_id requerido'], 422);
        }

        $computer = Computer::find($computerId);

        if (! $computer) {
            return response()->json(['error' => 'Computer no encontrado'], 404);
        }

        $currentVersion = $computer->resurtido_agent_version ?? '0.0.0';
        $latest = ResurtidoAgentVersion::active()->orderBy('created_at', 'desc')->first();

        if (! $latest) {
            return response()->json(['update_available' => false]);
        }

        $hasUpdate = version_compare($currentVersion, $latest->version) < 0;

        if (! $hasUpdate) {
            return response()->json(['update_available' => false]);
        }

        return response()->json([
            'update_available' => true,
            'version' => $latest->version,
            'download_url' => url('storage/'.$latest->file_path),
            'checksum' => $latest->checksum,
        ]);
    }

    public function getCommands(Request $request, $computerId)
    {
        $computer = Computer::findOrFail($computerId);

        $commands = Command::where('computer_id', $computerId)
            ->whereIn('status', ['pending', 'sent'])
            ->where('type', 'resurtido_update')
            ->orderBy('created_at')
            ->get();

        $commandsArray = [];

        foreach ($commands as $command) {
            $command->update(['status' => 'sent', 'sent_at' => now()]);

            $data = is_array($command->data) ? $command->data : json_decode($command->data, true);

            $commandsArray[] = [
                'id' => $command->id,
                'computer_id' => $command->computer_id,
                'type' => $command->type,
                'version' => $data['version'] ?? null,
                'checksum' => $data['checksum'] ?? null,
                'status' => 'sent',
            ];
        }

        return response()->json($commandsArray);
    }

    public function report(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer',
            'command_id' => 'nullable|integer',
            'status' => 'required|string',
            'response' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed'], 422);
        }

        $computer = Computer::find($request->computer_id);
        if ($computer) {
            $computer->update(['last_seen' => now()]);
        }

        if ($request->command_id) {
            $command = Command::where('id', $request->command_id)->first();
            if ($command) {
                $status = $request->status;
                if ($status === 'error') {
                    $status = 'failed';
                }

                $command->update([
                    'status' => $status,
                    'completed_at' => now(),
                    'response' => $request->response ?? '',
                ]);

                Log::info('Resurtido command executed', [
                    'command_id' => $command->id,
                    'status' => $status,
                ]);
            }
        }

        return response()->json(['message' => 'Report received']);
    }
}
