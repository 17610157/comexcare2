<?php

namespace App\Http\Controllers\Api;

use App\Events\DistributionProgressUpdated;
use App\Http\Controllers\Controller;
use App\Models\AgentDefaultCategoryFile;
use App\Models\AgentDefaultDownload;
use App\Models\AgentVersion;
use App\Models\Command;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\DistributionFile;
use App\Models\DistributionTarget;
use App\Models\Group;
use App\Models\ReceptionTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'id' => 'nullable|integer',
                'computer_name' => 'required|string|max:255',
                'mac_address' => 'required|string',
                'agent_version' => 'required|string',
                'system_info' => 'nullable|array',
                'download_path' => 'nullable|string',
                'short_key' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()->toArray(), 'data' => $data], 422);
            }

            $computer = DB::transaction(function () use ($data, $request) {
                if (! empty($data['id'])) {
                    $existing = Computer::lockForUpdate()->find($data['id']);
                    if ($existing) {
                        $existing->update([
                            'computer_name' => $data['computer_name'],
                            'mac_address' => $data['mac_address'],
                            'ip_address' => $request->ip(),
                            'agent_version' => $data['agent_version'],
                            'status' => 'online',
                            'last_seen' => now(),
                            'system_info' => $data['system_info'] ?? null,
                            'download_path' => $data['download_path'] ?? 'C:\ProgramData\DistributionAgent\files',
                        ]);

                        if (! empty($data['short_key'])) {
                            $existing->update(['short_key' => strtoupper($data['short_key'])]);
                        }

                        return $existing->fresh();
                    }

                    $data['computer_id'] = $data['id'];
                }

                $existingWithMac = Computer::withTrashed()
                    ->where('mac_address', $data['mac_address'])
                    ->lockForUpdate()
                    ->first();

                $groupId = null;
                if (! empty($data['short_key'])) {
                    $group = Group::findByShortKey(strtoupper($data['short_key']));
                    if ($group) {
                        $groupId = $group->id;
                        Log::info('Agent registered with short_key', [
                            'short_key' => $data['short_key'],
                            'group_id' => $group->id,
                            'group_name' => $group->name,
                        ]);
                    } else {
                        Log::info('Agent short_key not found', ['short_key' => $data['short_key']]);
                    }
                }

                if ($existingWithMac) {
                    $existingWithMac->restore();
                    $updateData = [
                        'computer_name' => $data['computer_name'],
                        'ip_address' => $request->ip(),
                        'agent_version' => $data['agent_version'],
                        'status' => 'online',
                        'last_seen' => now(),
                        'system_info' => $data['system_info'] ?? null,
                        'download_path' => $data['download_path'] ?? 'C:\ProgramData\DistributionAgent\files',
                        'deleted_at' => null,
                    ];
                    if (! empty($data['short_key'])) {
                        $shortKeyToSet = strtoupper($data['short_key']);
                        $otherWithSameKey = Computer::where('short_key', $shortKeyToSet)
                            ->where('id', '!=', $existingWithMac->id)
                            ->first();
                        if ($otherWithSameKey) {
                            $otherWithSameKey->update(['short_key' => null]);
                        }
                        $updateData['short_key'] = $shortKeyToSet;
                    }
                    if ($groupId && ! $existingWithMac->group_id) {
                        $updateData['group_id'] = $groupId;
                    }
                    $existingWithMac->update($updateData);

                    return $existingWithMac->fresh();
                }

                $existingByShortKey = null;
                if (! empty($data['short_key'])) {
                    $existingByShortKey = Computer::withTrashed()
                        ->where('short_key', strtoupper($data['short_key']))
                        ->lockForUpdate()
                        ->first();
                }

                if ($existingByShortKey) {
                    $existingByShortKey->restore();

                    if ($existingByShortKey->mac_address !== $data['mac_address']) {
                        $duplicateWithNewMac = Computer::withTrashed()
                            ->where('mac_address', $data['mac_address'])
                            ->where('id', '!=', $existingByShortKey->id)
                            ->first();

                        if ($duplicateWithNewMac) {
                            $duplicateWithNewMac->restore();

                            $existingByShortKey->update(['short_key' => null]);

                            $updateForDuplicate = [
                                'computer_name' => $data['computer_name'],
                                'ip_address' => $request->ip(),
                                'agent_version' => $data['agent_version'],
                                'status' => 'online',
                                'last_seen' => now(),
                                'system_info' => $data['system_info'] ?? null,
                                'download_path' => $data['download_path'] ?? 'C:\ProgramData\DistributionAgent\files',
                                'short_key' => strtoupper($data['short_key']),
                                'deleted_at' => null,
                            ];
                            if ($groupId && ! $duplicateWithNewMac->group_id) {
                                $updateForDuplicate['group_id'] = $groupId;
                            }
                            $duplicateWithNewMac->update($updateForDuplicate);

                            return $duplicateWithNewMac->fresh();
                        }
                    }

                    $updateData = [
                        'computer_name' => $data['computer_name'],
                        'mac_address' => $data['mac_address'],
                        'ip_address' => $request->ip(),
                        'agent_version' => $data['agent_version'],
                        'status' => 'online',
                        'last_seen' => now(),
                        'system_info' => $data['system_info'] ?? null,
                        'download_path' => $data['download_path'] ?? 'C:\ProgramData\DistributionAgent\files',
                        'deleted_at' => null,
                    ];
                    if ($groupId && ! $existingByShortKey->group_id) {
                        $updateData['group_id'] = $groupId;
                    }
                    $existingByShortKey->update($updateData);

                    return $existingByShortKey->fresh();
                }

                $existingByName = Computer::withTrashed()
                    ->where('computer_name', $data['computer_name'])
                    ->lockForUpdate()
                    ->first();

                if ($existingByName) {
                    $existingByName->restore();

                    $updateData = [
                        'mac_address' => $data['mac_address'],
                        'ip_address' => $request->ip(),
                        'agent_version' => $data['agent_version'],
                        'status' => 'online',
                        'last_seen' => now(),
                        'system_info' => $data['system_info'] ?? null,
                        'download_path' => $data['download_path'] ?? 'C:\ProgramData\DistributionAgent\files',
                        'deleted_at' => null,
                    ];
                    if (! empty($data['short_key'])) {
                        $updateData['short_key'] = strtoupper($data['short_key']);
                    }
                    if ($groupId && ! $existingByName->group_id) {
                        $updateData['group_id'] = $groupId;
                    }
                    $existingByName->update($updateData);

                    return $existingByName->fresh();
                }

                $generatedMac = $data['mac_address'] ?? '';
                if (empty($generatedMac)) {
                    $generatedMac = strtolower(sprintf(
                        'AUTO-%08x-%04x',
                        crc32($data['computer_name']),
                        crc32($data['mac_address'] ?? 'register') & 0xFFFF
                    ));
                }

                Log::info('Auto-creando computadora por registro de agente', [
                    'computer_name' => $data['computer_name'],
                    'mac_address' => $generatedMac,
                ]);

                $createData = [
                    'computer_name' => $data['computer_name'],
                    'mac_address' => $generatedMac,
                    'ip_address' => $request->ip(),
                    'agent_version' => $data['agent_version'],
                    'status' => 'online',
                    'last_seen' => now(),
                    'system_info' => $data['system_info'] ?? null,
                    'download_path' => $data['download_path'] ?? 'C:\ProgramData\DistributionAgent\files',
                ];

                if (! empty($data['short_key'])) {
                    $createData['short_key'] = strtoupper($data['short_key']);
                }

                if ($groupId) {
                    $createData['group_id'] = $groupId;
                }

                return Computer::create($createData);
            });

            if (! $computer) {
                return response()->json([
                    'error' => 'Computer not found',
                    'message' => 'No se encontró la computadora. Debe ser registrada primero en el panel de administración.',
                ], 404);
            }

            return response()->json([
                'id' => $computer->id,
                'message' => 'Registered successfully',
                'group_id' => $computer->group_id,
                'group_name' => $computer->group?->name,
                'short_key' => $computer->short_key,
            ]);
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
            'computer_name' => 'nullable|string|max:255',
            'mac_address' => 'nullable|string',
            'short_key' => 'nullable|string|max:50',
            'system_info' => 'nullable|array',
            'logs' => 'nullable|string',
            'dbf_files' => 'nullable|array',
            'dbf_files.*.checksum' => 'nullable|string',
            'dbf_files.*.hash_md5' => 'nullable|string',
            'agent_file' => 'nullable|array',
            'agent_file.name' => 'nullable|string|max:255',
            'agent_file.path' => 'nullable|string|max:500',
            'agent_file.size' => 'nullable|integer',
            'agent_file.modified' => 'nullable|string|max:50',
            'receive_paths' => 'nullable|array',
            'pvsi_version' => 'nullable|string|max:50',
            'pvsi_fecha' => 'nullable|string|max:20',
            'pvsi_hora' => 'nullable|string|max:10',
            'pvsi_files' => 'nullable|array',
            'pvsi_files.*.file_name' => 'nullable|string|max:255',
            'pvsi_files.*.version' => 'nullable|string|max:50',
            'pvsi_files.*.fecha' => 'nullable|string|max:20',
            'pvsi_files.*.hora' => 'nullable|string|max:10',
            'pvsi_bepartners_version' => 'nullable|string|max:50',
            'pvsi_bepartners_fecha' => 'nullable|string|max:20',
            'pvsi_bepartners_hora' => 'nullable|string|max:10',
            'windows_version' => 'nullable|string|max:100',
            'architecture' => 'nullable|string|max:10',
            'total_ram' => 'nullable|integer',
            'total_disk_space' => 'nullable|integer',
            'bitlocker_status' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        // Log::debug('Heartbeat RAW body', [
        //     'raw_content' => $request->getContent(),
        //     'all_keys' => array_keys($request->all()),
        //     'pvsi_files_raw' => $request->input('pvsi_files'),
        //     'bitlocker_status' => $request->input('bitlocker_status'),
        //     'logs_present' => $request->has('logs'),
        //     'logs_length' => $request->input('logs') ? strlen($request->input('logs')) : 0,
        //     'windows_version' => $request->input('windows_version'),
        //     'architecture' => $request->input('architecture'),
        //     'total_ram' => $request->input('total_ram'),
        //     'total_disk_space' => $request->input('total_disk_space'),
        // ]);

        $computer = Computer::find($request->computer_id);

        if (! $computer) {
            $trashed = Computer::withTrashed()->where('id', $request->computer_id)->first();
            if ($trashed && $trashed->trashed()) {
                $activeDuplicate = Computer::where('computer_name', $trashed->computer_name)
                    ->where('id', '!=', $trashed->id)
                    ->first();

                if ($activeDuplicate) {
                    $activeDuplicate->update([
                        'status' => 'online',
                        'last_seen' => now(),
                        'agent_version' => $request->agent_version,
                        'ip_address' => $request->ip(),
                        'system_info' => $request->system_info ?? $activeDuplicate->system_info,
                    ]);
                    $computer = $activeDuplicate;
                } else {
                    $trashed->restore();
                    $computer = $trashed;
                }
            } else {
                if ($request->filled('mac_address')) {
                    $byMac = Computer::withTrashed()->where('mac_address', $request->mac_address)->first();
                    if ($byMac) {
                        $byMac->restore();
                        $computer = $byMac;
                    }
                }

                if (! $computer) {
                    $generatedMac = $request->mac_address;
                    if (empty($generatedMac)) {
                        $generatedMac = strtolower(sprintf(
                            'AUTO-%08x-%04x',
                            $request->computer_id,
                            crc32($request->computer_name ?? $request->computer_id) & 0xFFFF
                        ));
                    }

                    Log::info('Auto-creando computadora por heartbeat', [
                        'computer_id' => $request->computer_id,
                        'computer_name' => $request->computer_name,
                        'mac_address' => $generatedMac,
                    ]);

                    $computer = new Computer;
                    $computer->id = $request->computer_id;
                    $computer->forceFill([
                        'computer_name' => $request->computer_name ?? "Computer-{$request->computer_id}",
                        'mac_address' => $generatedMac,
                        'ip_address' => $request->ip(),
                        'agent_version' => $request->agent_version,
                        'status' => 'online',
                        'last_seen' => now(),
                        'system_info' => $request->system_info,
                    ]);
                    $computer->save();
                    $computer = $computer->fresh();
                }
            }
        }

        $updateData = [
            'status' => 'online',
            'last_seen' => now(),
            'agent_version' => $request->agent_version,
            'ip_address' => $request->ip(),
            'system_info' => $request->system_info ?? $computer->system_info,
        ];

        if ($request->filled('computer_name')) {
            $updateData['computer_name'] = $request->computer_name;
        }
        if ($request->filled('short_key')) {
            $updateData['short_key'] = strtoupper($request->short_key);
        }

        if ($request->filled('pvsi_version')) {
            $updateData['pvsi_version'] = $request->pvsi_version;
        }
        if ($request->filled('pvsi_fecha')) {
            $updateData['pvsi_fecha'] = $request->pvsi_fecha;
        }
        if ($request->filled('pvsi_bepartners_version')) {
            $updateData['pvsi_bepartners_version'] = $request->pvsi_bepartners_version;
        }
        if ($request->filled('pvsi_bepartners_fecha')) {
            $updateData['pvsi_bepartners_fecha'] = $request->pvsi_bepartners_fecha;
        }
        if ($request->filled('pvsi_bepartners_hora')) {
            $updateData['pvsi_bepartners_hora'] = $request->pvsi_bepartners_hora;
        }
        if ($request->filled('resurtido_version')) {
            $updateData['resurtido_version'] = $request->resurtido_version;
        }
        if ($request->filled('resurtido_fecha')) {
            $updateData['resurtido_fecha'] = $request->resurtido_fecha;
        }

        if ($request->filled('dbf_files') || $request->filled('agent_file')) {
            $dbfFiles = $request->filled('dbf_files') ? $request->dbf_files : [];

            if ($request->filled('agent_file')) {
                $agentFileName = $request->agent_file['name'] ?? null;
                if ($agentFileName) {
                    $exists = false;
                    foreach ($dbfFiles as $existing) {
                        if (($existing['name'] ?? null) === $agentFileName) {
                            $exists = true;
                            break;
                        }
                    }
                    if (! $exists) {
                        $dbfFiles[] = $request->agent_file;
                    }
                }
            }

            $updateData['agent_config'] = array_merge($computer->agent_config ?? [], ['dbf_files' => $dbfFiles]);

            $this->syncAgentDefaultsFromHeartbeat($computer->id, $dbfFiles);
        }

        // download_path y short_key se configuran ÚNICAMENTE desde el panel de administración
        // El agente NUNCA debe poder modificar estos valores
        // Se establecen durante el registro inicial y el panel tiene prioridad absoluta
        // $rawData = json_decode($request->getContent(), true) ?? [];

        if ($request->filled('agent_file')) {
            $updateData['agent_file'] = $request->agent_file;
        }

        // Temporal: log de debug para PVSI
        // Log::channel('single')->debug('Heartbeat received', [
        //     'computer_id' => $request->computer_id,
        //     'agent_version' => $request->agent_version,
        //     'pvsi_version' => $request->pvsi_version,
        //     'pvsi_fecha' => $request->pvsi_fecha,
        //     'pvsi_hora' => $request->pvsi_hora,
        //     'pvsi_files' => $request->pvsi_files,
        //     'all_params' => $request->all(),
        // ]);

        if ($request->filled('pvsi_files') && is_array($request->pvsi_files)) {
            $pvsiFiles = json_decode(json_encode($request->pvsi_files), true);
            $updateData['pvsi_files'] = $pvsiFiles;

            $firstPvsi = reset($pvsiFiles);
            if ($firstPvsi) {
                $updateData['pvsi_version'] = $firstPvsi['version'] ?? null;
                $updateData['pvsi_fecha'] = $firstPvsi['fecha'] ?? null;
                $updateData['pvsi_hora'] = $firstPvsi['hora'] ?? null;
            }

            // Log::info("PVSI files updated for computer {$computer->id}: ".json_encode($pvsiFiles));
        } elseif ($request->filled('pvsi_version')) {
            $oldPvsiVersion = $computer->pvsi_version;
            $newPvsiVersion = $request->pvsi_version;

            if ($oldPvsiVersion !== $newPvsiVersion) {
                $updateData['pvsi_version'] = $newPvsiVersion;
                $updateData['pvsi_fecha'] = $request->pvsi_fecha;
                $updateData['pvsi_hora'] = $request->pvsi_hora;

                // Log::info("PVSI version updated for computer {$computer->id}: {$oldPvsiVersion} -> {$newPvsiVersion}");
            }
        }

        if ($request->filled('windows_version')) {
            $updateData['windows_version'] = $request->windows_version;
        }
        if ($request->filled('architecture')) {
            $updateData['architecture'] = $request->architecture;
        }
        if ($request->filled('total_ram')) {
            $updateData['total_ram'] = $request->total_ram;
        }
        if ($request->filled('total_disk_space')) {
            $updateData['total_disk_space'] = $request->total_disk_space;
        }
        if ($request->filled('bitlocker_status') && is_array($request->bitlocker_status)) {
            $bitlockerData = json_decode(json_encode($request->bitlocker_status), true);
            $updateData['bitlocker_status'] = $bitlockerData;
            // Log::info('BitLocker status received', ['computer_id' => $computer->id, 'data' => $bitlockerData]);
        }

        // Las receive_paths se configuran ÚNICAMENTE desde el panel de administración
        // El agente NUNCA debe poder modificar estas rutas
        // Solo se guardan si no existen en el servidor Y el agente envía datos por primera vez

        // NO guardamos lo que el agente envía - el panel tiene prioridad absoluta
        // $computer->receive_paths ya tiene la configuración del panel

        $computer->update($updateData);

        // Process logs from the agent
        if ($request->filled('logs')) {
            $logCount = 0;
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

                try {
                    ComputerLog::create([
                        'computer_id' => $computer->id,
                        'level' => $level,
                        'message' => $line,
                    ]);
                    $logCount++;
                } catch (\Exception $e) {
                    Log::warning('Failed to save log entry: '.$e->getMessage());
                }
            }
            // Log::info("Saved {$logCount} log entries for computer {$computer->id}");
        }

        return response()->json([
            'message' => 'Heartbeat received',
            'computer_name' => $computer->computer_name,
            'download_path' => $computer->download_path ?? 'C:\ProgramData\DistributionAgent\files',
            'download_paths' => $computer->getAllDownloadPaths(),
            'receive_paths' => $computer->receive_paths ?? [],
            'report_url' => config('app.url').'/api/report',
        ]);
    }

    public function getCommands(Request $request, $id)
    {
        $computer = Computer::findOrFail($id);
        $computer->update(['last_seen' => now(), 'status' => 'online']);

        // Get pending commands — limit to 10 at a time to avoid flooding the agent
        // Exclude 'update' commands — those are handled by checkUpdate + CheckForUpdate()
        $commands = Command::where('computer_id', $id)
            ->whereIn('status', ['pending', 'sent'])
            ->where('type', '!=', 'update')
            ->orderBy('created_at')
            ->limit(10)
            ->get();

        // Log::info('Commands found', ['count' => $commands->count(), 'computer_id' => $id, 'limit' => 10]);

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
                'reception_target_id' => $data['reception_target_id'] ?? null,
                'status' => 'sent',
            ];

            if ($command->type === 'distribute' || $command->type === 'download') {
                $paths = $computer->getAllDownloadPaths();

                if (isset($data['subfolder']) && $data['subfolder']) {
                    $subfolder = ltrim($data['subfolder'], '/\\');
                    $pathsWithSubfolder = [];
                    foreach ($paths as $path) {
                        $pathsWithSubfolder[] = rtrim($path, '/\\').'\\'.$subfolder;
                    }
                    $commandArray['download_paths'] = $pathsWithSubfolder;
                } else {
                    $commandArray['download_paths'] = $paths;
                }

                if (isset($data['subfolder'])) {
                    $commandArray['subfolder'] = $data['subfolder'];
                }
            }

            if ($command->type === 'execute') {
                $commandArray['command_type'] = 'execute';

                $baseCommand = $data['command'] ?? '';

                if (preg_match('/UPDATECAREAGENTRESURTIDO\.BAT$/i', $baseCommand)) {
                    $commandArray['execute_target'] = 'care_agent_update';
                    $commandArray['command'] = $baseCommand;
                    $commandArray['cmd'] = $baseCommand;
                    $commandArray['execute_command'] = $baseCommand;
                    $commandArray['search_drives'] = ['C', 'D', 'E', 'F'];
                    $commandArray['search_folder'] = 'RBF\\exe_';
                    $commandArray['search_file'] = 'UPDATECAREAGENTRESURTIDO.BAT';
                    $commandArray['run_as_admin'] = true;
                } elseif (preg_match('/^(DALISTA|DAPROMO|DAOFERTA|DACOMBO)\.BAT$/i', $baseCommand)) {
                    $commandArray['command'] = $baseCommand;
                    $commandArray['cmd'] = $baseCommand;
                    $commandArray['execute_command'] = $baseCommand;
                    $commandArray['search_drives'] = ['C', 'D', 'E', 'F'];
                    $commandArray['search_folder'] = 'RBF\\ejecutables';
                    $commandArray['search_file'] = strtoupper($baseCommand);
                    $commandArray['run_as_admin'] = true;
                } else {
                    $commandArray['command'] = $baseCommand;
                    $commandArray['cmd'] = $baseCommand;
                    $commandArray['execute_command'] = $baseCommand;
                }

                $commandArray['command_args'] = $data['command_args'] ?? '';
            }

            if ($command->type === 'update') {
                $commandArray['download_paths'] = $computer->getAllDownloadPaths();

                if (isset($data['subfolder'])) {
                    $commandArray['subfolder'] = $data['subfolder'];
                }

                if (isset($data['version'])) {
                    $agentVersion = AgentVersion::where('version', $data['version'])->first();
                    if ($agentVersion) {
                        $files = $agentVersion->files;
                        if (! empty($files)) {
                            $commandArray['agent_files'] = $files;
                            $commandArray['version'] = $data['version'];
                            $commandArray['checksum'] = $agentVersion->checksum;
                        }
                    }
                }
            }

            // Para comandos de recepción, enviar las rutas de receive_paths y configuración
            if ($command->type === 'receive') {
                $commandArray['receive_paths'] = $computer->receive_paths ?? [];
                $commandArray['reception_target_id'] = $data['reception_target_id'] ?? null;
                $commandArray['file_types'] = $data['file_types'] ?? null;

                if (isset($data['subfolder'])) {
                    $commandArray['subfolder'] = $data['subfolder'];
                }
                $commandArray['specific_files'] = $data['specific_files'] ?? null;
                $commandArray['all_files'] = $data['all_files'] ?? true;
                $commandArray['scheduled_time'] = $data['scheduled_time'] ?? null;
                $commandArray['reception_type'] = $data['type'] ?? 'immediate';
                $commandArray['recurrence'] = $data['recurrence'] ?? null;
                $commandArray['frequency_interval'] = $data['frequency_interval'] ?? null;
                $commandArray['week_days'] = $data['week_days'] ?? null;
            }

            $commandsArray[] = $commandArray;
        }

        // Mantenemos compatibilidad: el agente espera un array
        // Las rutas de receive_paths se envían en el heartbeat
        return response()->json($commandsArray);
    }

    public function report(Request $request)
    {
        Log::info('Report received', $request->all());

        $validator = Validator::make($request->all(), [
            'computer_id' => 'nullable|integer',
            'command_id' => 'nullable|integer',
            'distribution_target_id' => 'nullable|integer',
            'reception_target_id' => 'nullable|integer',
            'file_id' => 'nullable|integer',
            'status' => 'required|string',
            'progress' => 'nullable|integer|min:0|max:100',
            'response' => 'nullable|string',
            'output' => 'nullable|string',
            'exit_code' => 'nullable|integer',
            'error' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('Report validation failed', ['errors' => $validator->errors()->toArray()]);

            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $computerId = $request->computer_id;
        if ($computerId && ! Computer::where('id', $computerId)->exists()) {
            Log::warning('Report received for unknown computer', ['computer_id' => $computerId]);
        }

        if ($computerId) {
            $computer = Computer::find($computerId);
            if ($computer) {
                $computer->update(['last_seen' => now(), 'status' => 'online']);
            }
        }

        if ($request->command_id) {
            $command = Command::where('id', $request->command_id)->first();
            if ($command) {
                $status = $request->status;
                if ($status === 'error') {
                    $status = 'failed';
                }

                $responseData = $request->response ?? '';
                if ($request->filled('output')) {
                    $responseData .= "\n[OUTPUT]\n".$request->output;
                }
                if ($request->filled('error')) {
                    $responseData .= "\n[ERROR]\n".$request->error;
                }
                if ($request->filled('exit_code')) {
                    $responseData .= "\n[EXIT_CODE: ".$request->exit_code.']';
                }

                $updateData = [
                    'response' => trim($responseData),
                ];

                $updateData['status'] = $status;

                if (in_array($status, ['completed', 'failed'])) {
                    $updateData['completed_at'] = now();
                }

                $command->update($updateData);

                Log::info('Command executed', [
                    'command_id' => $command->id,
                    'status' => $status,
                    'exit_code' => $request->exit_code,
                    'has_output' => $request->filled('output'),
                    'has_error' => $request->filled('error'),
                ]);
            }
        }

        if ($request->progress !== null) {
            $targetId = null;

            if ($request->distribution_target_id) {
                $targetId = $request->distribution_target_id;
            } elseif ($request->file_id) {
                $target = DistributionTarget::where('distribution_id', function ($query) use ($request) {
                    $query->select('distribution_id')
                        ->from('distribution_files')
                        ->where('id', $request->file_id);
                })->where('computer_id', $request->computer_id)->first();
                if ($target) {
                    $targetId = $target->id;
                }
            } elseif ($request->command_id) {
                $command = Command::find($request->command_id);
                $data = $command && $command->data ? (is_array($command->data) ? $command->data : json_decode($command->data, true)) : null;
                $targetId = $data['distribution_target_id'] ?? null;
            }

            if ($targetId) {
                $target = DistributionTarget::find($targetId);
                if ($target) {
                    $status = $request->status;
                    if ($status === 'error') {
                        $status = 'failed';
                    }

                    $updateData = [
                        'progress' => $request->progress,
                        'status' => in_array($status, ['completed', 'failed']) ? $status : 'in_progress',
                    ];

                    if ($status === 'failed' && $request->response) {
                        $updateData['error_message'] = $request->response;
                    }

                    $target->update($updateData);

                    $command = Command::where('computer_id', $request->computer_id)
                        ->where('status', 'sent')
                        ->whereRaw("data->>'distribution_target_id' = ?", [(string) $targetId])
                        ->first();
                    if ($command) {
                        $cmdUpdate = [
                            'response' => $request->response,
                        ];

                        if (in_array($status, ['completed', 'failed'])) {
                            $cmdUpdate['status'] = $status;
                            $cmdUpdate['completed_at'] = now();
                        } elseif (in_array($status, ['running', 'downloading'])) {
                            $cmdUpdate['status'] = $status;
                        }

                        $command->update($cmdUpdate);
                    }

                    $distribution = $target->distribution;

                    broadcast(new DistributionProgressUpdated(
                        $distribution->fresh(['targets']),
                        $target->id,
                        $target->status,
                        $request->progress ?? 100
                    ))->toOthers();
                    $allTargets = $distribution->targets;
                    $completedCount = $allTargets->where('status', 'completed')->count();
                    $failedCount = $allTargets->where('status', 'failed')->count();
                    $totalCount = $allTargets->count();

                    if ($completedCount + $failedCount === $totalCount) {
                        $distribution->update(['status' => $completedCount === $totalCount ? 'completed' : 'failed']);
                    }
                }
            }

            // Actualizar ReceptionTarget si existe
            if ($request->reception_target_id) {
                $receptionTarget = ReceptionTarget::find($request->reception_target_id);
                if ($receptionTarget) {
                    $updateData = [
                        'progress' => $request->progress ?? 100,
                        'status' => $request->status === 'completed' ? 'completed' : ($request->status === 'error' ? 'failed' : 'in_progress'),
                        'completed_at' => $request->status === 'completed' ? now() : null,
                    ];

                    if ($request->status === 'failed' && $request->response) {
                        $updateData['error_message'] = $request->response;
                    }

                    $receptionTarget->update($updateData);

                    // Actualizar estado de la recepción
                    $reception = $receptionTarget->reception;
                    $allTargets = $reception->targets;
                    $completedCount = $allTargets->where('status', 'completed')->count();
                    $failedCount = $allTargets->where('status', 'failed')->count();
                    $totalCount = $allTargets->count();

                    if ($completedCount + $failedCount === $totalCount) {
                        $reception->update(['status' => $completedCount === $totalCount ? 'completed' : 'failed']);
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
        // Intentar interpretar como computer_id
        $computer = Computer::find((int) $version);

        if ($computer) {
            $computer->update(['last_seen' => now(), 'status' => 'online']);

            return $this->checkUpdateForComputer($computer);
        }

        return response()->json(['update_available' => false]);
    }

    public function checkUpdateByComputerId(Request $request, $computer_id)
    {
        $computer = Computer::find($computer_id);

        if (! $computer) {
            return response()->json(['error' => 'Computer not found'], 404);
        }

        $computer->update(['last_seen' => now(), 'status' => 'online']);

        return $this->checkUpdateForComputer($computer);
    }

    private function checkUpdateForComputer(Computer $computer)
    {
        $pendingCommand = Command::where('computer_id', $computer->id)
            ->where('type', 'update')
            ->whereIn('status', ['pending', 'sent'])
            ->latest()
            ->first();

        if (! $pendingCommand) {
            return response()->json(['update_available' => false]);
        }

        $data = is_array($pendingCommand->data) ? $pendingCommand->data : json_decode($pendingCommand->data, true);
        $version = $data['version'] ?? null;

        if (! $version) {
            return response()->json(['update_available' => false]);
        }

        $agentVersion = AgentVersion::where('version', $version)->first();
        if (! $agentVersion) {
            return response()->json(['update_available' => false]);
        }

        return response()->json([
            'update_available' => true,
            'version' => $agentVersion->version,
            'download_url' => url('storage/'.$agentVersion->file_path),
            'channel' => $agentVersion->channel,
            'checksum' => $agentVersion->checksum,
            'changelog' => $agentVersion->changelog,
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
        $computer->update([
            'agent_config' => array_merge($computer->agent_config ?? [], ['inventory' => $request->inventory]),
            'last_seen' => now(),
        ]);

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

        $computer = Computer::find($request->computer_id);
        if ($computer) {
            $computer->update(['last_seen' => now(), 'status' => 'online']);
        }

        foreach ($request->logs as $log) {
            ComputerLog::create([
                'computer_id' => $request->computer_id,
                'level' => $log['level'] ?? 'info',
                'message' => $log['message'] ?? '',
            ]);
        }

        return response()->json(['message' => 'Logs received', 'count' => count($request->logs)]);
    }

    public function pvsiUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'computer_id' => 'required|integer',
            'pvsi_version' => 'required|string|max:50',
            'pvsi_fecha' => 'nullable|string|max:20',
            'pvsi_hora' => 'nullable|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Validation failed', 'messages' => $validator->errors()], 422);
        }

        $computer = Computer::find($request->computer_id);

        if (! $computer) {
            return response()->json(['error' => 'Computer not found'], 404);
        }

        $oldVersion = $computer->pvsi_version;
        $newVersion = $request->pvsi_version;

        if ($oldVersion !== $newVersion) {
            $computer->update([
                'pvsi_version' => $newVersion,
                'pvsi_fecha' => $request->pvsi_fecha,
                'pvsi_hora' => $request->pvsi_hora,
                'status' => 'online',
                'last_seen' => now(),
            ]);

            Log::info("PVSI version updated for computer {$computer->id}: {$oldVersion} -> {$newVersion}");
        }

        return response()->json([
            'message' => 'PVSI version updated',
            'computer_id' => $computer->id,
            'pvsi_version' => $computer->pvsi_version,
            'pvsi_fecha' => $computer->pvsi_fecha,
            'pvsi_hora' => $computer->pvsi_hora,
        ]);
    }

    public function getComputerConfig(Request $request, $computer_id)
    {
        $computer = Computer::find($computer_id);

        if (! $computer) {
            return response()->json(['error' => 'Computer not found'], 404);
        }

        $computer->update(['last_seen' => now(), 'status' => 'online']);

        return response()->json([
            'computer_id' => $computer->id,
            'computer_name' => $computer->computer_name,
            'download_path' => $computer->download_path ?? 'C:\ProgramData\DistributionAgent\files',
            'download_paths' => $computer->getAllDownloadPaths(),
            'receive_paths' => $computer->receive_paths ?? [],
            'agent_config' => $computer->agent_config ?? [],
        ]);
    }

    public function uploadReception(Request $request)
    {
        // Loguear todo lo que llega
        Log::info('UploadReception RAW', [
            'all' => $request->all(),
            'files' => array_keys($request->allFiles()),
            'headers' => $request->headers->all(),
        ]);

        // Intentar obtener valores - aceptar tanto form-data como JSON
        $computer_id = $request->computer_id ?? $request->input('computer_id') ?? ($request->json('computer_id') ?? null);
        $reception_target_id = $request->reception_target_id ?? $request->input('reception_target_id') ?? ($request->json('reception_target_id') ?? null);
        $folder_name = $request->folder_name ?? $request->input('folder_name') ?? ($request->json('folder_name') ?? null);

        // Buscar el archivo
        $uploadedFile = null;
        $files = $request->allFiles();
        if (! empty($files)) {
            $uploadedFile = array_values($files)[0];
        }

        Log::info('UploadReception parsed', [
            'computer_id' => $computer_id,
            'reception_target_id' => $reception_target_id,
            'folder_name' => $folder_name,
            'has_file' => $uploadedFile ? true : false,
        ]);

        if (! $computer_id || ! $reception_target_id || ! $folder_name) {
            return response()->json([
                'error' => 'Missing required fields',
                'computer_id' => $computer_id,
                'reception_target_id' => $reception_target_id,
                'folder_name' => $folder_name,
            ], 422);
        }

        if (! $uploadedFile) {
            return response()->json(['error' => 'No file provided'], 422);
        }

        $computer = Computer::find($computer_id);
        $receptionTarget = ReceptionTarget::find($reception_target_id);

        if (! $computer || ! $receptionTarget) {
            return response()->json(['error' => 'Computer or reception not found'], 404);
        }

        $shortKey = $computer->short_key ?? 'NO_KEY';

        $path = storage_path('app/distributions/'.$shortKey.'/'.$folder_name);

        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $fileName = $uploadedFile->getClientOriginalName();
        $filePath = $path.'/'.$fileName;

        $uploadedFile->move($path, $fileName);

        Log::info('Reception file uploaded', [
            'computer_id' => $computer->id,
            'reception_target_id' => $reception_target_id,
            'file' => $fileName,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'file_name' => $fileName,
        ]);
    }

    private function syncAgentDefaultsFromHeartbeat(int $computerId, array $dbfFiles): void
    {
        $computer = Computer::with('group')->find($computerId);

        if (! $computer) {
            return;
        }

        $groupIds = [];

        if ($computer->group) {
            $groupIds[] = $computer->group->id;
        }

        $assignedFiles = AgentDefaultCategoryFile::whereHas('route.assignments', function ($q) use ($computerId, $groupIds) {
            $q->where(function ($q) use ($computerId) {
                $q->where('assignable_type', Computer::class)
                    ->where('assignable_id', $computerId);
            })->orWhere(function ($q) use ($groupIds) {
                $q->where('assignable_type', Group::class)
                    ->whereIn('assignable_id', $groupIds);
            });
        })->whereHas('route.category', function ($q) {
            $q->where('is_active', true);
        })->with('route.category')->get();

        $filesByCategory = $assignedFiles->groupBy(fn ($f) => $f->route->agent_default_category_id);
        $dbfByName = collect($dbfFiles)->keyBy(fn ($f) => $f['name'] ?? $f['path'] ?? null);

        foreach ($filesByCategory as $categoryId => $categoryFiles) {
            $category = $categoryFiles->first()->route->category;

            if (! $category->auto_sync && ! $category->auto_validation) {
                continue;
            }

            foreach ($categoryFiles as $file) {
                $dbfFile = $dbfByName->get($file->file_name);

                if (! $dbfFile) {
                    continue;
                }

                $heartbeatChecksum = $dbfFile['checksum'] ?? null;

                if ($category->auto_validation) {
                    if ($heartbeatChecksum && $heartbeatChecksum === $file->checksum) {
                        $syncStatus = 'synced';
                    } elseif ($heartbeatChecksum) {
                        $syncStatus = 'different';
                    } else {
                        $syncStatus = 'pending';
                    }
                } else {
                    $syncStatus = 'synced';
                }

                $downloadData = [
                    'sync_status' => $syncStatus,
                    'synced_at' => now(),
                ];

                if ($category->auto_sync) {
                    $downloadData['local_path'] = $dbfFile['path'] ?? null;
                    $downloadData['local_checksum'] = $heartbeatChecksum;
                    $downloadData['ruta_local'] = $dbfFile['path'] ?? null;
                    $downloadData['ruta_servidor'] = $file->route->route_pattern.'\\'.$file->file_name;
                }

                AgentDefaultDownload::updateOrCreate(
                    [
                        'computer_id' => $computerId,
                        'agent_default_category_file_id' => $file->id,
                    ],
                    $downloadData
                );
            }
        }
    }
}
