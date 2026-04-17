<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;

class ComputersController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $columns = Schema::getColumnListing('computers');

            $query = Computer::with('group');

            $searchValue = $request->input('search.value');
            if (! empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('short_key', 'like', "%{$searchValue}%")
                        ->orWhere('computer_name', 'like', "%{$searchValue}%")
                        ->orWhere('mac_address', 'like', "%{$searchValue}%")
                        ->orWhere('ip_address', 'like', "%{$searchValue}%");
                });
            }

            if ($request->filled('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('short_key')) {
                $query->where('short_key', 'like', "%{$request->short_key}%");
            }

            $start = $request->input('start', 0);
            $length = $request->input('length', 100);

            $allowedLengths = [10, 25, 50, 100, 500, 1000];
            if (! in_array($length, $allowedLengths)) {
                $length = 100;
            }

            $maxLength = 1000;
            if ($length > $maxLength) {
                $length = $maxLength;
            }

            $total = $query->count();

            $computers = $query->orderBy('id', 'desc')
                ->offset($start)
                ->limit($length)
                ->get();

            $tiendas = DB::table('bi_sys_tiendas')
                ->whereIn('clave_tienda', $computers->pluck('short_key')->filter())
                ->orWhereIn('clave_alterna', $computers->pluck('short_key')->filter())
                ->get()
                ->keyBy(function ($t) {
                    return $t->clave_tienda === $t->clave_alterna ? $t->clave_tienda : $t->clave_tienda.'|'.$t->clave_alterna;
                });

            $data = $computers->map(function ($computer) use ($tiendas) {
                $tienda = $tiendas->first(function ($t) use ($computer) {
                    return $t->clave_tienda === $computer->short_key || $t->clave_alterna === $computer->short_key;
                });
                $plaza = $computer->plaza ?? '';

                $status = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : 'offline';

                return [
                    'id' => $computer->id,
                    'short_key' => $computer->short_key ?? '-',
                    'computer_name' => $computer->computer_name,
                    'status' => $status,
                    'group_name' => $computer->group->name ?? 'N/A',
                    'group_id' => $computer->group_id,
                    'agent_version' => $computer->agent_version ?? '-',
                    'pvsi_version' => $computer->pvsi_version ?? '-',
                    'pvsi_fecha' => $computer->pvsi_fecha ?? '-',
                    'pvsi_hora' => $computer->pvsi_hora ?? '-',
                    'pvsi_files' => $computer->pvsi_files ?? [],
                    'windows_version' => $computer->windows_version ?? '-',
                    'plaza' => $plaza,
                    'bitlocker_status' => $computer->bitlocker_status ? json_decode($computer->bitlocker_status, true) : null,
                    'mac_address' => $computer->mac_address,
                    'ip_address' => $computer->ip_address,
                    'download_path' => $computer->download_path ?? 'C:\ProgramData\DistributionAgent\files',
                    'last_seen' => $computer->last_seen ? $computer->last_seen->diffForHumans() : 'Never',
                    'last_seen_raw' => $computer->last_seen ? $computer->last_seen->toIso8601String() : null,
                ];
            });

            return response()->json([
                'draw' => $request->input('draw', 1),
                'recordsTotal' => Computer::count(),
                'recordsFiltered' => $total,
                'data' => $data,
            ]);
        }

        $groups = Group::orderBy('name')->get();
        $statuses = Computer::distinct()->pluck('status')->filter()->values()->toArray();

        return view('admin.computers.index', compact('groups', 'statuses'));
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
        $plazas = DB::table('bi_sys_tiendas')->distinct()->pluck('id_plaza')->sort();

        return view('admin.computers.edit', compact('computer', 'groups', 'plazas'));
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
            'plaza',
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

    public function export()
    {
        $computers = Computer::with('group')->orderBy('computer_name')->get();

        $csvData = [];
        $csvData[] = [
            'Short Key', 'Nombre', 'MAC', 'IP', 'Estado', 'Plaza', 'Grupo',
            'Agent', 'PVSI', 'PVSI Fecha', 'PVSI Hora',
            'Windows', 'Arquitectura', 'RAM (GB)', 'Disco (GB)',
            'BitLocker', 'Download Path', 'Última Actividad',
        ];

        foreach ($computers as $computer) {
            $bitlocker = '';
            if ($computer->bitlocker_status && is_array($computer->bitlocker_status)) {
                $parts = [];
                foreach ($computer->bitlocker_status as $drive => $status) {
                    $parts[] = $drive.': '.$status;
                }
                $bitlocker = implode('; ', $parts);
            }

            $plaza = $computer->plaza ?? '';
            $status = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : 'offline';

            $csvData[] = [
                $computer->short_key ?? '',
                $computer->computer_name,
                $computer->mac_address,
                $computer->ip_address,
                $status,
                $plaza,
                $computer->group->name ?? '',
                $computer->status,
                $plaza,
                $computer->group->name ?? 'N/A',
                $computer->agent_version ?? '',
                $computer->pvsi_version ?? '',
                $computer->pvsi_fecha ?? '',
                $computer->pvsi_hora ?? '',
                $computer->windows_version ?? '',
                $computer->architecture ?? '',
                $computer->total_ram ? round($computer->total_ram / 1073741824) : '',
                $computer->total_disk_space ? round($computer->total_disk_space / 1073741824) : '',
                $bitlocker,
                $computer->download_path ?? '',
                $computer->last_seen ? $computer->last_seen->format('Y-m-d H:i:s') : '',
            ];
        }

        $filename = 'computadoras_'.date('Y-m-d_His').'.csv';
        $handle = fopen('php://temp', 'r+');

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $content = chr(239).chr(187).chr(191).$content;

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
