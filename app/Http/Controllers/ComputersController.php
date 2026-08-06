<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ComputersController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $threshold = now()->subMinutes(5);

            $query = Computer::with('group');

            $searchValue = $request->input('search.value');
            if (! empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('short_key', 'like', "%{$searchValue}%")
                        ->orWhere('nombre_instalacion', 'like', "%{$searchValue}%")
                        ->orWhere('mac_address', 'like', "%{$searchValue}%")
                        ->orWhere('ip_address', 'like', "%{$searchValue}%");
                });
            }

            if ($request->filled('group_id')) {
                $query->where('group_id', $request->group_id);
            }

            if ($request->filled('plaza')) {
                $query->where('plaza', $request->plaza);
            }

            if ($request->filled('status_type')) {
                if ($request->status_type === 'online') {
                    $query->where('last_seen', '>=', $threshold)
                        ->where('status', '!=', 'updating');
                } elseif ($request->status_type === 'updating') {
                    $query->where('last_seen', '>=', $threshold)
                        ->where('status', 'updating');
                } elseif ($request->status_type === 'offline') {
                    $query->where(function ($q) use ($threshold) {
                        $q->where('last_seen', '<', $threshold)->orWhereNull('last_seen');
                    });
                }
            } elseif ($request->filled('status')) {
                if ($request->status === 'online') {
                    $query->where('last_seen', '>=', $threshold)
                        ->where('status', '!=', 'updating');
                } elseif ($request->status === 'updating') {
                    $query->where('last_seen', '>=', $threshold)
                        ->where('status', 'updating');
                } elseif ($request->status === 'offline') {
                    $query->where(function ($q) use ($threshold) {
                        $q->where('last_seen', '<', $threshold)->orWhereNull('last_seen');
                    });
                }
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

            $data = $computers->map(function ($computer) use ($threshold) {
                $plaza = $computer->plaza ?? '';

                if (! $computer->last_seen || $computer->last_seen->lt($threshold)) {
                    $status = 'offline';
                } elseif ($computer->status === 'updating') {
                    $status = 'updating';
                } else {
                    $status = 'online';
                }

                return [
                    'id' => $computer->id,
                    'short_key' => $computer->short_key ?? '-',
                    'nombre_instalacion' => $computer->nombre_instalacion,
                    'status' => $status,
                    'group_name' => $computer->group->name ?? 'N/A',
                    'group_id' => $computer->group_id,
                    'agent_version' => $computer->agent_version ?? '-',
                    'pvsi_version' => $computer->pvsi_version ?? '-',
                    'pvsi_fecha' => $computer->pvsi_fecha ?? '-',
                    'pvsi_hora' => $computer->pvsi_hora ?? '-',
                    'pvsi_files' => $computer->pvsi_files ?? [],
                    'pvsi_bepartners_version' => $computer->pvsi_bepartners_version ?? '-',
                    'pvsi_bepartners_fecha' => $computer->pvsi_bepartners_fecha ?? '-',
                    'pvsi_bepartners_hora' => $computer->pvsi_bepartners_hora ?? '-',
                    'resurtido_version' => $computer->resurtido_version ?? '-',
                    'resurtido_fecha' => $computer->resurtido_fecha ?? '-',
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
        $plazas = Computer::distinct()->pluck('plaza')->filter()->sort()->values()->toArray();

        return view('admin.computers.index', compact('groups', 'plazas'));
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
            'nombre_instalacion' => 'nullable|string|max:150',
            'short_key' => 'nullable|string|max:50|unique:computers,short_key,'.$computer->id,
            'group_id' => 'nullable|exists:groups,id',
            'agent_config' => 'nullable|array',
            'download_path' => 'nullable|string',
        ]);

        $fillableFields = [
            'computer_name',
            'nombre_instalacion',
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

        if (request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Agente eliminado correctamente']);
        }

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
        $threshold = now()->subMinutes(5);

        if (! $computer->last_seen || $computer->last_seen->lt($threshold)) {
            $status = 'offline';
        } elseif ($computer->status === 'updating') {
            $status = 'updating';
        } else {
            $status = 'online';
        }

        return response()->json([
            'status' => $status,
            'last_seen' => $computer->last_seen?->toIso8601String(),
        ]);
    }

    public function export()
    {
        $threshold = now()->subMinutes(5);
        $computers = Computer::with('group')->orderBy('nombre_instalacion')->get();

        $csvData = [];
        $csvData[] = [
            'Short Key', 'Nombre', 'MAC', 'IP', 'Estado', 'Plaza', 'Grupo',
            'Agent', 'PVSI', 'PVSI Fecha', 'PVSI Hora',
            'PVSI Bepartners', 'PVSI Bepartners Fecha', 'PVSI Bepartners Hora',
            'AgentResurtido', 'Resurtido Fecha',
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
            if (! $computer->last_seen || $computer->last_seen->lt($threshold)) {
                $status = 'offline';
            } elseif ($computer->status === 'updating') {
                $status = 'updating';
            } else {
                $status = 'online';
            }

            $csvData[] = [
                $computer->short_key ?? '',
                $computer->nombre_instalacion,
                $computer->mac_address,
                $computer->ip_address,
                $status,
                $plaza,
                $computer->group->name ?? '',
                $computer->agent_version ?? '',
                $computer->pvsi_version ?? '',
                $computer->pvsi_fecha ?? '',
                $computer->pvsi_hora ?? '',
                $computer->pvsi_bepartners_version ?? '',
                $computer->pvsi_bepartners_fecha ?? '',
                $computer->pvsi_bepartners_hora ?? '',
                $computer->resurtido_version ?? '',
                $computer->resurtido_fecha ?? '',
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

    public function fixDuplicates(): JsonResponse
    {
        $duplicates = $this->findDuplicateGroups();

        if (empty($duplicates)) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontraron equipos duplicados.',
                'processed' => 0,
            ]);
        }

        $totalUpdated = 0;
        $totalDeleted = 0;
        $details = [];

        foreach ($duplicates as $group) {
            $result = $this->processDuplicateGroup($group);
            if ($result['processed']) {
                $totalUpdated += $result['updated'];
                $totalDeleted += $result['deleted'];
                $details[] = $result;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Procesados {$totalUpdated} actualizaciones y {$totalDeleted} eliminaciones.",
            'processed' => count($details),
            'updated' => $totalUpdated,
            'deleted' => $totalDeleted,
            'details' => $details,
        ]);
    }

    private function findDuplicateGroups(): array
    {
        $duplicateNames = Computer::select('nombre_instalacion')
            ->whereNotNull('nombre_instalacion')
            ->where('nombre_instalacion', '!=', '')
            ->groupBy('nombre_instalacion')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('nombre_instalacion')
            ->toArray();

        $groups = [];

        foreach ($duplicateNames as $name) {
            $computers = Computer::where('nombre_instalacion', $name)->orderBy('id')->get();

            if ($computers->count() > 1) {
                $groups[] = [
                    'key' => $name,
                    'computers' => $computers,
                ];
            }
        }

        return $groups;
    }

    private function processDuplicateGroup(array $group): array
    {
        $computers = $group['computers'];

        $online = $computers->firstWhere('status', 'online');
        $offline = $computers->firstWhere('status', 'offline');

        if (! $online || ! $offline || $online->id === $offline->id) {
            return ['processed' => 0, 'updated' => 0, 'deleted' => 0];
        }

        $newShortKey = $offline->short_key ?? $online->short_key;
        $newPlaza = $offline->plaza ?? $online->plaza;
        $newGroupId = $offline->group_id ?? $online->group_id;

        DB::transaction(function () use ($offline, $newShortKey, $newPlaza, $newGroupId, $online) {
            $offline->update(['short_key' => null]);
            $online->update([
                'short_key' => $newShortKey,
                'plaza' => $newPlaza,
                'group_id' => $newGroupId,
            ]);
            $offline->delete();
        });

        return [
            'processed' => 1,
            'updated' => 1,
            'deleted' => 1,
            'nombre_instalacion' => $online->nombre_instalacion,
            'online_id' => $online->id,
            'offline_id' => $offline->id,
            'short_key' => $newShortKey,
            'plaza' => $newPlaza,
            'group_id' => $newGroupId,
        ];
    }
}
