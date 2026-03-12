<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\Group;
use Illuminate\Http\Request;

class ComputersController extends Controller
{
    public function index()
    {
        $computers = Computer::with('group')->paginate(50);

        return view('admin.computers.index', compact('computers'));
    }

    public function show(Computer $computer)
    {
        $computer->load('commands', 'distributionTargets.distribution');

        // Obtener el último ID de las últimas 24 horas para polling
        $lastLogId = ComputerLog::where('computer_id', $computer->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->max('id') ?? 0;

        return view('admin.computers.show', compact('computer', 'lastLogId'));
    }

    public function edit(Computer $computer)
    {
        $groups = Group::all();

        return view('admin.computers.edit', compact('computer', 'groups'));
    }

    public function update(Request $request, Computer $computer)
    {
        $data = $request->all();

        if (isset($data['agent_config_json']) && is_string($data['agent_config_json'])) {
            $data['agent_config'] = json_decode($data['agent_config_json'], true) ?? [];
        } elseif (isset($data['agent_config']) && is_string($data['agent_config'])) {
            $data['agent_config'] = json_decode($data['agent_config'], true) ?? [];
        }

        if ($request->has('additional_download_paths')) {
            $additionalPaths = array_filter($request->additional_download_paths, function ($path) {
                return ! empty(trim($path));
            });
            if (! empty($additionalPaths)) {
                $data['agent_config']['additional_download_paths'] = array_values($additionalPaths);
            } else {
                unset($data['agent_config']['additional_download_paths']);
            }
        }

        $request->replace($data);

        $request->validate([
            'computer_name' => 'nullable|string|max:255',
            'short_key' => 'nullable|string|max:50|unique:computers,short_key,'.$computer->id,
            'group_id' => 'nullable|exists:groups,id',
            'agent_config' => 'nullable|array',
            'download_path' => 'nullable|string',
        ]);

        $fillableFields = [
            'computer_name',
            'short_key',
            'group_id',
            'agent_config',
            'receive_paths',
            'download_path',
            'download_path_1',
            'download_path_2',
            'download_path_3',
            'download_path_4',
            'download_path_5',
            'download_path_6',
            'download_path_7',
            'download_path_8',
            'download_path_9',
            'download_path_10',
        ];

        // Necesitamos actualizar receive_paths manualmente porque el request->only no funciona bien con arrays
        $computer->update($request->only($fillableFields));

        // Actualizar receive_paths por separado si viene en el request
        if ($request->has('receive_paths')) {
            $receivePaths = [];
            foreach ($request->receive_paths as $path) {
                if (! empty($path['local_path']) && ! empty($path['folder_name'])) {
                    $receivePaths[] = [
                        'local_path' => trim($path['local_path']),
                        'folder_name' => trim($path['folder_name']),
                        'type' => $path['type'] ?? 'file',
                    ];
                }
            }
            $computer->update(['receive_paths' => $receivePaths]);
        }

        return redirect()->route('admin.computers.index')->with('success', 'Computer updated');
    }

    public function destroy(Computer $computer)
    {
        $computer->delete();

        return redirect()->route('admin.computers.index')->with('success', 'Agente eliminado correctamente');
    }

    public function logs(Request $request, Computer $computer)
    {
        $lastId = $request->query('last_id', 0);

        // Si last_id es 0, mostrar los últimos 100 logs (para carga inicial)
        if ($lastId == 0) {
            $logs = ComputerLog::where('computer_id', $computer->id)
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('id', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'level' => $log->level,
                        'message' => $log->message,
                        'time' => $log->created_at->format('H:i:s'),
                    ];
                })
                ->reverse()
                ->values();
        } else {
            // Si hay un last_id, obtener solo logs nuevos
            $logs = ComputerLog::where('computer_id', $computer->id)
                ->where('id', '>', $lastId)
                ->where('created_at', '>=', now()->subHours(24))
                ->orderBy('id', 'asc')
                ->limit(100)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'level' => $log->level,
                        'message' => $log->message,
                        'time' => $log->created_at->format('H:i:s'),
                    ];
                });
        }

        return response()->json(['logs' => $logs]);
    }

    public function status(Computer $computer)
    {
        return response()->json([
            'status' => $computer->status,
            'last_seen' => $computer->last_seen?->toIso8601String(),
        ]);
    }
}
