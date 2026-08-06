<?php

namespace App\Http\Controllers;

use App\Models\Command;
use App\Models\Computer;
use App\Models\ComputerLog;
use App\Models\Group;
use App\Models\RbfFileHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteDbfFilesEspecificosController extends Controller
{
    private const SPECIFIC_FILES = [
        'ARCERO.DBF',
        'CABLISTA.DBF',
        'CLIECATP.DBF',
        'LISTA.DBF',
        'OFERTAS.DBF',
        'PCOMB.DBF',
        'PDCOMB.DBF',
        'PROMARTS.DBF',
    ];

    private const TIPO_MAP = [
        'lista' => ['dbf' => ['LISTA.DBF', 'CABLISTA.DBF'], 'bat' => 'DALISTA.BAT'],
        'promocion' => ['dbf' => ['PROMARTS.DBF', 'ARCERO.DBF'], 'bat' => 'DAPROMO.BAT'],
        'oferta' => ['dbf' => ['OFERTAS.DBF'], 'bat' => 'DAOFERTA.BAT'],
        'combo' => ['dbf' => ['PCOMB.DBF', 'PDCOMB.DBF'], 'bat' => 'DACOMBO.BAT'],
    ];

    public function index(Request $request)
    {
        $plazasTiendas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza')
            ->pluck('id_plaza')
            ->filter()
            ->values()
            ->toArray();

        $plazasComputers = Computer::whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->distinct()
            ->orderBy('plaza')
            ->pluck('plaza')
            ->toArray();

        $plazas = collect(array_merge($plazasTiendas, $plazasComputers))
            ->unique()
            ->sort()
            ->values();

        $archivos = self::SPECIFIC_FILES;

        $groups = Group::orderBy('name')->get();

        return response()
            ->view('reportes.dbf-files-especificos.index', compact('plazas', 'archivos', 'groups'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function getRbfHashLookup(): array
    {
        $map = [];
        $records = RbfFileHash::all();
        foreach ($records as $r) {
            $key = strtolower($r->plaza ?? '').'|'.($r->hash ?? '').'|'.($r->name ?? '');
            $map[$key] = $r;
        }

        return $map;
    }

    private function formatAgentModifiedTime($modified)
    {
        $modified = trim((string) $modified);
        if ($modified === '') {
            return '';
        }

        $patterns = [
            '/^(?<date>\d{4}-\d{2}-\d{2})[ T](?<time>\d{1,2}:\d{2}(?::\d{2})?)(?:\.\d+)?(?:\s?(?<ampm>AM|PM|am|pm))?(?:[+-].*)?$/',
            '/^(?<date>\d{2}\/\d{2}\/\d{4})[ T](?<time>\d{1,2}:\d{2}(?::\d{2})?)(?:\s?(?<ampm>AM|PM|am|pm))?$/',
            '/^(?<time>\d{1,2}:\d{2}(?::\d{2})?)(?:\s?(?<ampm>AM|PM|am|pm))?$/',
        ];

        if (preg_match('/\b(?:AM|PM|am|pm)\b/', $modified)) {
            return $modified;
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $modified, $matches)) {
                $date = $matches['date'] ?? '';
                $time = $matches['time'];
                $ampm = isset($matches['ampm']) ? strtoupper($matches['ampm']) : '';

                $parts = explode(':', $time);
                $hour = (int) $parts[0];
                $minute = isset($parts[1]) ? (int) $parts[1] : 0;
                $second = isset($parts[2]) ? (int) $parts[2] : 0;

                if ($ampm === '') {
                    $ampm = $hour >= 12 ? 'PM' : 'AM';
                    $hour12 = $hour % 12;
                    if ($hour12 === 0) {
                        $hour12 = 12;
                    }
                } else {
                    $hour12 = $hour % 12;
                    if ($hour12 === 0) {
                        $hour12 = 12;
                    }
                }

                $time = sprintf('%d:%02d%s', $hour12, $minute, $second ? sprintf(':%02d', $second) : '');
                $formatted = trim(trim($date.' '.$time.($ampm ? ' '.$ampm : '')));

                return $formatted;
            }
        }

        return $modified;
    }

    private function excelTextValue($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return "'{$value}";
    }

    private function isModifiedToday($modified): bool
    {
        $modified = trim((string) $modified);
        if ($modified === '') {
            return false;
        }

        $timestamp = strtotime($modified);
        if ($timestamp === false) {
            return false;
        }

        return date('Y-m-d', $timestamp) === date('Y-m-d');
    }

    private function filterSpecificFiles(array $dbfFiles): array
    {
        return array_values(array_filter($dbfFiles, fn ($f) => in_array(strtoupper($f['name'] ?? ''), self::SPECIFIC_FILES)));
    }

    private function buildFlatRows(Request $request): array
    {
        $search = $request->query('search') ?? $request->input('search.value', '');

        $query = Computer::with('group');

        $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
        if (is_array($plazaInput) && count($plazaInput) > 0) {
            $query->whereIn('plaza', $plazaInput);
        }

        $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
        if (is_array($groupInput) && count($groupInput) > 0) {
            $query->whereIn('group_id', $groupInput);
        }

        if (! empty($search)) {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(nombre_instalacion) LIKE LOWER(?)', [$term])
                    ->orWhereRaw('LOWER(ip_address) LIKE LOWER(?)', [$term]);
            });
        }

        $allComputers = $query->orderBy('nombre_instalacion')->get();

        $archivoInput = $request->query('archivo') ?? $request->input('archivo');
        $rbfLookup = $this->getRbfHashLookup();

        $flatRows = [];
        foreach ($allComputers as $computer) {
            $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
            $dbfFiles = $this->filterSpecificFiles($dbfFiles);

            if (empty($dbfFiles)) {
                continue;
            }

            foreach ($dbfFiles as $file) {
                $fileName = $file['name'] ?? 'N/A';

                if (! empty($archivoInput) && strtoupper($fileName) !== strtoupper($archivoInput)) {
                    continue;
                }

                $key = strtolower($computer->plaza ?? '').'|'.($file['hash_md5'] ?? '').'|'.$fileName;
                $rbfRecord = $rbfLookup[$key] ?? null;
                $isMatched = $rbfRecord !== null;
                $status = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : 'offline';

                if ($isMatched) {
                    $rbfStatus = 'actualizado';
                } elseif ($this->isModifiedToday($file['modified'] ?? '')) {
                    $rbfStatus = 'cambio_manual';
                } else {
                    $rbfStatus = 'desactualizado';
                }

                $flatRows[] = [
                    'id' => $computer->id,
                    'nombre_instalacion' => $computer->nombre_instalacion,
                    'plaza' => $computer->plaza ?? 'N/A',
                    'status' => $status,
                    'last_seen' => $computer->last_seen ? $computer->last_seen->format('Y-m-d H:i:s') : 'Never',
                    'archivo' => $fileName,
                    'ruta' => $file['path'] ?? '',
                    'tamano' => isset($file['size']) ? round($file['size'] / 1024, 2) : null,
                    'modificacion' => $this->formatAgentModifiedTime($file['modified'] ?? ''),
                    'md5' => $file['hash_md5'] ?? '',
                    'rbf_path' => $rbfRecord?->path,
                    'rbf_hash' => $rbfRecord?->hash,
                    'rbf_matched' => $isMatched,
                    'rbf_status' => $rbfStatus,
                ];
            }
        }

        $estadoInput = $request->query('estado') ?? $request->input('estado', '');
        if (! empty($estadoInput) && in_array($estadoInput, ['actualizado', 'desactualizado', 'cambio_manual'])) {
            $flatRows = array_values(array_filter($flatRows, fn ($row) => $row['rbf_status'] === $estadoInput));
        }

        return $flatRows;
    }

    public function data(Request $request)
    {
        $draw = (int) ($request->query('draw') ?? $request->input('draw', 1));
        $startIdx = (int) ($request->query('start') ?? $request->input('start', 0));
        $length = (int) ($request->query('length') ?? $request->input('length', 50));
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;
        $sortColumn = $request->query('sort') ?? 'nombre_instalacion';
        $sortDirection = $request->query('direction') ?? 'asc';

        try {
            $flatRows = $this->buildFlatRows($request);

            $total = count($flatRows);

            $sortMap = [
                'nombre_instalacion' => fn ($r) => strtolower($r['nombre_instalacion']),
                'plaza' => fn ($r) => strtolower($r['plaza']),
                'status' => fn ($r) => $r['status'] === 'online' ? 0 : 1,
                'last_seen' => fn ($r) => $r['last_seen'] === 'Never' ? 0 : strtotime($r['last_seen']),
                'archivo' => fn ($r) => strtolower($r['archivo']),
                'rbf_matched' => fn ($r) => ['actualizado' => 0, 'cambio_manual' => 1, 'desactualizado' => 2][$r['rbf_status']] ?? 2,
            ];
            $sortFn = $sortMap[$sortColumn] ?? fn ($r) => strtolower($r['nombre_instalacion']);

            usort($flatRows, function ($a, $b) use ($sortFn, $sortDirection) {
                $valA = $sortFn($a);
                $valB = $sortFn($b);
                $cmp = is_string($valA) ? strcmp($valA, $valB) : $valA <=> $valB;

                return $sortDirection === 'desc' ? -$cmp : $cmp;
            });

            $slicedRows = array_slice($flatRows, $offsetInt, $lengthInt);

            $globalMatched = count(array_filter($flatRows, fn ($r) => $r['rbf_status'] === 'actualizado'));
            $globalCambioManual = count(array_filter($flatRows, fn ($r) => $r['rbf_status'] === 'cambio_manual'));
            $globalUnmatched = count(array_filter($flatRows, fn ($r) => $r['rbf_status'] === 'desactualizado'));
            $globalTotal = count($flatRows);

            $plazaStats = [];
            $fileStats = [];
            foreach ($flatRows as $row) {
                $plaza = $row['plaza'];
                $fileName = $row['archivo'];
                if (! isset($plazaStats[$plaza])) {
                    $plazaStats[$plaza] = ['total' => 0, 'matched' => 0, 'cambio_manual' => 0];
                }
                $plazaStats[$plaza]['total']++;
                if ($row['rbf_status'] === 'actualizado') {
                    $plazaStats[$plaza]['matched']++;
                } elseif ($row['rbf_status'] === 'cambio_manual') {
                    $plazaStats[$plaza]['cambio_manual']++;
                }
                if (! isset($fileStats[$fileName])) {
                    $fileStats[$fileName] = ['total' => 0, 'matched' => 0, 'cambio_manual' => 0];
                }
                $fileStats[$fileName]['total']++;
                if ($row['rbf_status'] === 'actualizado') {
                    $fileStats[$fileName]['matched']++;
                } elseif ($row['rbf_status'] === 'cambio_manual') {
                    $fileStats[$fileName]['cambio_manual']++;
                }
            }

            $perPlaza = [];
            foreach ($plazaStats as $plaza => $stats) {
                $perPlaza[] = [
                    'plaza' => $plaza,
                    'total' => $stats['total'],
                    'matched' => $stats['matched'],
                    'cambio_manual' => $stats['cambio_manual'],
                    'unmatched' => $stats['total'] - $stats['matched'] - $stats['cambio_manual'],
                    'percent' => $stats['total'] > 0 ? round(($stats['matched'] / $stats['total']) * 100, 1) : 0,
                ];
            }
            usort($perPlaza, fn ($a, $b) => $b['total'] <=> $a['total']);

            $perFile = [];
            foreach ($fileStats as $name => $stats) {
                $perFile[] = [
                    'name' => $name,
                    'total' => $stats['total'],
                    'matched' => $stats['matched'],
                    'cambio_manual' => $stats['cambio_manual'],
                    'unmatched' => $stats['total'] - $stats['matched'] - $stats['cambio_manual'],
                    'percent' => $stats['total'] > 0 ? round(($stats['matched'] / $stats['total']) * 100, 1) : 0,
                ];
            }
            usort($perFile, fn ($a, $b) => $b['total'] <=> $a['total']);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $slicedRows,
                'rbf_stats' => [
                    'total_files' => $globalTotal,
                    'total_matched' => $globalMatched,
                    'total_cambio_manual' => $globalCambioManual,
                    'total_unmatched' => $globalUnmatched,
                    'percent' => $globalTotal > 0 ? round(($globalMatched / $globalTotal) * 100, 1) : 0,
                    'per_plaza' => $perPlaza,
                    'per_file' => $perFile,
                ],
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('DbfFilesEspecificos data error: '.$e->getMessage());

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function ids(Request $request)
    {
        try {
            $flatRows = $this->buildFlatRows($request);
            $ids = array_values(array_unique(array_map(fn ($row) => (int) $row['id'], $flatRows)));

            return response()->json([
                'success' => true,
                'ids' => $ids,
                'count' => count($ids),
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('DbfFilesEspecificos ids error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function ejecutar(Request $request, string $tipo)
    {
        if (! isset(self::TIPO_MAP[$tipo])) {
            return response()->json(['success' => false, 'message' => 'Tipo inválido'], 400);
        }

        $dbfTargets = (array) self::TIPO_MAP[$tipo]['dbf'];
        $dbfTargetsUpper = array_map('strtoupper', $dbfTargets);
        $batCommand = self::TIPO_MAP[$tipo]['bat'];
        $isPreview = $request->boolean('preview', false);
        $computerIds = $request->input('computer_ids', []);

        try {
            $query = Computer::with('group');

            if (is_array($computerIds) && count($computerIds) > 0) {
                $query->whereIn('id', $computerIds);
            } else {
                $plazaInput = $request->input('plaza', []);
                if (is_array($plazaInput) && count($plazaInput) > 0) {
                    $query->whereIn('plaza', $plazaInput);
                }

                $groupInput = $request->input('group_id', []);
                if (is_array($groupInput) && count($groupInput) > 0) {
                    $query->whereIn('group_id', $groupInput);
                }

                $search = $request->input('search', '');
                if (! empty($search)) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre_instalacion', 'ILIKE', '%'.$search.'%')
                            ->orWhere('ip_address', 'ILIKE', '%'.$search.'%');
                    });
                }
            }

            $allComputers = $query->orderBy('nombre_instalacion')->get();

            $rbfLookup = $this->getRbfHashLookup();

            $matchingComputers = [];
            foreach ($allComputers as $computer) {
                $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
                $dbfFiles = $this->filterSpecificFiles($dbfFiles);

                if (empty($dbfFiles)) {
                    continue;
                }

                foreach ($dbfFiles as $file) {
                    $fileName = $file['name'] ?? '';

                    if (! in_array(strtoupper($fileName), $dbfTargetsUpper, true)) {
                        continue;
                    }

                    $key = strtolower($computer->plaza ?? '').'|'.($file['hash_md5'] ?? '').'|'.$fileName;
                    $isMatched = isset($rbfLookup[$key]);

                    if (! $isMatched) {
                        $matchingComputers[] = [
                            'id' => $computer->id,
                            'nombre_instalacion' => $computer->nombre_instalacion,
                            'plaza' => $computer->plaza ?? 'N/A',
                        ];

                        break;
                    }
                }
            }

            if ($isPreview) {
                return response()->json([
                    'success' => true,
                    'computers' => $matchingComputers,
                    'count' => count($matchingComputers),
                    'bat' => $batCommand,
                    'dbf' => implode(' / ', $dbfTargets),
                ]);
            }

            $commands = [];
            foreach ($matchingComputers as $comp) {
                $commands[] = [
                    'computer_id' => $comp['id'],
                    'type' => 'execute',
                    'data' => json_encode([
                        'command' => $batCommand,
                        'command_args' => '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($commands)) {
                Command::insert($commands);
                Log::info('ejecutar: created '.count($commands).' commands for '.$batCommand);
            }

            return response()->json([
                'success' => true,
                'count' => count($commands),
                'bat' => $batCommand,
            ]);
        } catch (\Exception $e) {
            Log::error('DbfFilesEspecificos ejecutar error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function bitacora(Request $request)
    {
        $batFiles = array_column(self::TIPO_MAP, 'bat');
        $limit = min((int) $request->query('limit', 50), 200);

        $commands = Command::where('type', 'execute')
            ->where(function ($q) use ($batFiles) {
                foreach ($batFiles as $bat) {
                    $q->orWhere('data->command', $bat);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get(['id', 'computer_id', 'data', 'status', 'response', 'created_at']);

        $batLabels = [];
        foreach (self::TIPO_MAP as $key => $cfg) {
            $batLabels[$cfg['bat']] = strtoupper($key);
        }

        $groups = [];
        foreach ($commands as $cmd) {
            $ts = $cmd->created_at->format('Y-m-d H:i:s');
            $bat = strtoupper($cmd->data['command'] ?? '');
            $error = '';
            if ($cmd->status === 'failed' && $cmd->response) {
                if (preg_match('/\[ERROR\](.*?)\[EXIT_CODE/s', $cmd->response, $m)) {
                    $errorSection = $m[1];
                    $lines = explode("\n", $errorSection);
                    $clean = [];
                    foreach ($lines as $line) {
                        $t = trim($line);
                        if ($t === '') {
                            continue;
                        }
                        if (preg_match('/^[#=O\-]+$/', $t)) {
                            continue;
                        }
                        if (preg_match('/^(#=#=|##O#|##O=|#=#=|-#O#|-=#=|-=O#|-=O=|-=O=-)/', $t)) {
                            continue;
                        }
                        if (preg_match('/^\d+%$/', $t)) {
                            continue;
                        }
                        if (preg_match('/^[#=\-O]+ *\d*%*$/', $t)) {
                            continue;
                        }
                        $clean[] = $t;
                    }
                    $error = implode("\n", $clean);
                } elseif (str_contains($cmd->response, 'Comando fallo')) {
                    if (preg_match('/Comando fallo[^\n]+/s', $cmd->response, $m)) {
                        $error = trim($m[0]);
                    }
                }
                if (strlen($error) > 300) {
                    $error = mb_substr($error, 0, 300);
                }
            }

            $computer = Computer::find($cmd->computer_id);

            $groups[$ts][] = [
                'id' => $cmd->id,
                'computer' => $computer?->nombre_instalacion ?? 'N/A',
                'plaza' => $computer?->plaza ?? 'N/A',
                'bat' => $bat,
                'label' => $batLabels[$bat] ?? $bat,
                'status' => $cmd->status,
                'error' => $error,
            ];
        }

        $result = [];
        foreach ($groups as $ts => $items) {
            $statusCounts = collect($items)->groupBy('status')->map->count();
            $result[] = [
                'created_at' => $ts,
                'total' => count($items),
                'counts' => $statusCounts,
                'items' => $items,
            ];
        }

        return response()->json([
            'success' => true,
            'groups' => array_slice($result, 0, $limit),
        ]);
    }

    public function historial(Request $request)
    {
        $computerId = (int) $request->query('computer_id');
        $archivo = strtoupper(trim((string) $request->query('archivo', '')));

        if ($computerId <= 0 || $archivo === '') {
            return response()->json(['success' => false, 'message' => 'Parámetros inválidos'], 422);
        }

        if (! in_array($archivo, self::SPECIFIC_FILES)) {
            return response()->json(['success' => false, 'message' => 'Archivo no válido'], 422);
        }

        $computer = Computer::find($computerId);
        if (! $computer) {
            return response()->json(['success' => false, 'message' => 'Computadora no encontrada'], 404);
        }

        try {
            $desde = now()->subDays(3);

            $mensajes = ComputerLog::query()
                ->where('computer_id', $computerId)
                ->where('created_at', '>=', $desde)
                ->whereRaw('LENGTH(message) > 1000')
                ->pluck('message');

            $historial = [];
            foreach ($mensajes as $mensaje) {
                if (! str_contains($mensaje, 'Heartbeat JSON payload')) {
                    continue;
                }

                $pos = strpos($mensaje, 'Heartbeat JSON payload: ');
                if ($pos === false) {
                    continue;
                }

                $json = substr($mensaje, $pos + strlen('Heartbeat JSON payload: '));
                $data = json_decode($json, true);
                if (! is_array($data)) {
                    continue;
                }

                foreach (($data['dbf_files'] ?? []) as $file) {
                    if (strtoupper($file['name'] ?? '') !== $archivo) {
                        continue;
                    }

                    $hash = $file['hash_md5'] ?? '';
                    if ($hash === '') {
                        continue;
                    }

                    $modified = $this->formatAgentModifiedTime($file['modified'] ?? '');

                    if (! isset($historial[$hash])) {
                        $historial[$hash] = ['hash' => $hash, 'modified' => $modified];

                        continue;
                    }

                    if (strtotime($modified) > strtotime($historial[$hash]['modified'])) {
                        $historial[$hash]['modified'] = $modified;
                    }
                }
            }

            uasort($historial, function ($a, $b) {
                return strtotime($b['modified']) <=> strtotime($a['modified']);
            });

            return response()->json([
                'success' => true,
                'computer_id' => $computerId,
                'archivo' => $archivo,
                'historial' => array_values($historial),
            ]);
        } catch (\Exception $e) {
            Log::error('DbfFilesEspecificos historial error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al obtener el historial'], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
            $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
            $archivoInput = $request->query('archivo') ?? $request->input('archivo', '');

            $query = Computer::with('group');

            if (is_array($plazaInput) && count($plazaInput) > 0) {
                $query->whereIn('plaza', $plazaInput);
            }

            if (is_array($groupInput) && count($groupInput) > 0) {
                $query->whereIn('group_id', $groupInput);
            }

            $computers = $query->orderBy('nombre_instalacion')->get();

            $rbfLookup = $this->getRbfHashLookup();

            $filename = 'Reporte_DBF_Especificos_'.date('Ymd_His');

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ];

            $callback = function () use ($computers, $rbfLookup, $archivoInput) {
                $output = fopen('php://output', 'w');

                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($output, [
                    'Computadora', 'Plaza', 'Archivo', 'Ruta', 'Tamano (KB)',
                    'Ultima Modificacion', 'MD5', 'Ruta RBF', 'Hash RBF', 'Estado',
                ]);

                foreach ($computers as $computer) {
                    $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
                    $dbfFiles = $this->filterSpecificFiles($dbfFiles);

                    foreach ($dbfFiles as $dbfFile) {
                        $fileName = $dbfFile['name'] ?? 'N/A';

                        if (! empty($archivoInput) && strtoupper($fileName) !== strtoupper($archivoInput)) {
                            continue;
                        }

                        $key = strtolower($computer->plaza ?? '').'|'.($dbfFile['hash_md5'] ?? '').'|'.$fileName;
                        $rbfRecord = $rbfLookup[$key] ?? null;
                        $sizeKb = isset($dbfFile['size']) ? round($dbfFile['size'] / 1024, 2).' KB' : '';
                        $modified = $this->excelTextValue($this->formatAgentModifiedTime($dbfFile['modified'] ?? ''));

                        fputcsv($output, [
                            $computer->nombre_instalacion,
                            $computer->plaza ?? 'N/A',
                            $dbfFile['name'] ?? 'N/A',
                            $dbfFile['path'] ?? '',
                            $sizeKb,
                            $modified,
                            $dbfFile['hash_md5'] ?? '',
                            $rbfRecord?->path ?? '',
                            $rbfRecord?->hash ?? '',
                            $rbfRecord !== null ? 'Actualizado' : 'Desactualizado',
                        ]);
                    }
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('DbfFilesEspecificos export error: '.$e->getMessage());

            return redirect()->route('reportes.dbf-files-especificos')
                ->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }
}
