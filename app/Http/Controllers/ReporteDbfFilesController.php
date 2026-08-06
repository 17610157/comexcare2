<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Group;
use App\Models\RbfFileHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteDbfFilesController extends Controller
{
    public function index(Request $request)
    {
        $groups = Group::orderBy('name')->get();

        $plazas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza')
            ->pluck('id_plaza')
            ->filter()
            ->values();

        $archivos = $this->getUniqueFiles();

        return response()
            ->view('reportes.dbf-files.index', compact('plazas', 'groups', 'archivos'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function getUniqueFiles()
    {
        $computers = Computer::whereNotNull('agent_config')
            ->where('agent_config', '!=', '[]')
            ->get();

        $archivos = [];
        foreach ($computers as $computer) {
            $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
            foreach ($dbfFiles as $file) {
                $name = $file['name'] ?? null;
                if ($name && ! in_array($name, $archivos)) {
                    $archivos[] = $name;
                }
            }
        }

        sort($archivos);

        return $archivos;
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

    private function isValidDbfFile(array $file): bool
    {
        return true;
    }

    private function getFileCategory(array $file): string
    {
        $name = $file['name'] ?? '';
        $path = $file['path'] ?? '';
        $ext = strtoupper(pathinfo($name, PATHINFO_EXTENSION));

        if ($ext === 'EXE') {
            return 'exe';
        }

        if (stripos($path, 'quickbck') !== false || stripos($name, 'quickbck') !== false) {
            return 'quickbck';
        }

        return 'other';
    }

    public function data(Request $request)
    {
        $draw = (int) ($request->query('draw') ?? $request->input('draw', 1));
        $startIdx = (int) ($request->query('start') ?? $request->input('start', 0));
        $length = (int) ($request->query('length') ?? $request->input('length', 50));
        $search = $request->query('search') ?? $request->input('search.value', '');
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;
        $sortColumn = $request->query('sort') ?? 'nombre_instalacion';
        $sortDirection = $request->query('direction') ?? 'asc';

        try {
            $query = Computer::with('group');

            $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
            if (is_array($plazaInput) && count($plazaInput) > 0) {
                $query->whereIn('plaza', $plazaInput);
            }

            $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
            if (is_array($groupInput) && count($groupInput) > 0) {
                $query->whereIn('group_id', $groupInput);
            }

            $archivoInput = $request->query('archivo') ?? $request->input('archivo');
            if (! empty($archivoInput)) {
                $query->where('agent_config', 'ILIKE', '%'.$archivoInput.'%');
            }

            $hashInput = $request->query('hash') ?? $request->input('hash');
            if (! empty($hashInput)) {
                $query->where('agent_config', 'ILIKE', '%'.$hashInput.'%');
            }

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre_instalacion', 'ILIKE', '%'.$search.'%')
                        ->orWhere('ip_address', 'ILIKE', '%'.$search.'%');
                });
            }

            $allComputers = $query->orderBy('nombre_instalacion')->get();

            $rbfLookup = $this->getRbfHashLookup();

            $globalMatched = 0;
            $globalTotal = 0;
            $plazaStats = [];
            $fileStats = [];
            $groupStats = [];
            $computerOutdated = [];
            $computerMatchMap = [];
            $globalCategoryStats = ['exe' => ['total' => 0, 'matched' => 0], 'quickbck' => ['total' => 0, 'matched' => 0], 'other' => ['total' => 0, 'matched' => 0]];

            foreach ($allComputers as $computer) {
                $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
                $dbfFiles = array_filter($dbfFiles, fn ($f) => $this->isValidDbfFile($f));
                $plaza = $computer->plaza ?? 'N/A';
                $groupName = $computer->group->name ?? 'N/A';
                $isOnline = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5;
                $computerMatched = 0;
                $categoryStats = ['exe' => ['total' => 0, 'matched' => 0], 'quickbck' => ['total' => 0, 'matched' => 0], 'other' => ['total' => 0, 'matched' => 0]];

                foreach ($dbfFiles as $file) {
                    $fileName = $file['name'] ?? 'N/A';
                    $key = strtolower($computer->plaza ?? '').'|'.($file['hash_md5'] ?? '').'|'.$fileName;
                    $isMatched = isset($rbfLookup[$key]);
                    $cat = $this->getFileCategory($file);

                    $categoryStats[$cat]['total']++;
                    if ($isMatched) {
                        $computerMatched++;
                        $categoryStats[$cat]['matched']++;
                    }

                    if (! isset($fileStats[$fileName])) {
                        $fileStats[$fileName] = ['total' => 0, 'matched' => 0];
                    }
                    $fileStats[$fileName]['total']++;
                    if ($isMatched) {
                        $fileStats[$fileName]['matched']++;
                    }
                }

                $computerTotal = count($dbfFiles);
                $computerMatchMap[$computer->id] = [
                    'matched' => $computerMatched,
                    'total' => $computerTotal,
                    'exe' => $categoryStats['exe'],
                    'quickbck' => $categoryStats['quickbck'],
                    'other' => $categoryStats['other'],
                ];

                foreach ($categoryStats as $cat => $stats) {
                    $globalCategoryStats[$cat]['total'] += $stats['total'];
                    $globalCategoryStats[$cat]['matched'] += $stats['matched'];
                }

                $globalMatched += $computerMatched;
                $globalTotal += $computerTotal;

                if (! isset($plazaStats[$plaza])) {
                    $plazaStats[$plaza] = ['total' => 0, 'matched' => 0];
                }
                $plazaStats[$plaza]['total'] += $computerTotal;
                $plazaStats[$plaza]['matched'] += $computerMatched;

                if (! isset($groupStats[$groupName])) {
                    $groupStats[$groupName] = ['total' => 0, 'online' => 0, 'offline' => 0];
                }
                $groupStats[$groupName]['total']++;
                if ($isOnline) {
                    $groupStats[$groupName]['online']++;
                } else {
                    $groupStats[$groupName]['offline']++;
                }

                $computerUnmatched = $computerTotal - $computerMatched;
                if ($computerUnmatched > 0) {
                    $computerOutdated[] = [
                        'name' => $computer->nombre_instalacion,
                        'plaza' => $plaza,
                        'group' => $groupName,
                        'total' => $computerTotal,
                        'matched' => $computerMatched,
                        'unmatched' => $computerUnmatched,
                    ];
                }
            }

            $estadoInput = $request->query('estado') ?? $request->input('estado', '');
            if (! empty($estadoInput) && in_array($estadoInput, ['actualizado', 'desactualizado'])) {
                $allComputers = $allComputers->filter(function ($computer) use ($computerMatchMap, $estadoInput) {
                    $map = $computerMatchMap[$computer->id] ?? ['matched' => 0, 'total' => 0];
                    if ($map['total'] === 0) {
                        return $estadoInput === 'desactualizado';
                    }
                    $allMatched = $map['matched'] === $map['total'];

                    return $estadoInput === 'actualizado' ? $allMatched : ! $allMatched;
                })->values();
            }

            $total = $allComputers->count();

            $allComputers = $allComputers->sortBy(function ($computer) use ($computerMatchMap, $sortColumn) {
                $map = $computerMatchMap[$computer->id] ?? ['matched' => 0, 'total' => 0, 'exe' => ['total' => 0, 'matched' => 0], 'quickbck' => ['total' => 0, 'matched' => 0], 'other' => ['total' => 0, 'matched' => 0]];
                $total = $map['total'];
                $matched = $map['matched'];
                $pct = $total > 0 ? round(($matched / $total) * 100) : 0;

                return match ($sortColumn) {
                    'nombre_instalacion' => strtolower($computer->nombre_instalacion ?? ''),
                    'plaza' => strtolower($computer->plaza ?? ''),
                    'group_name' => strtolower($computer->group->name ?? ''),
                    'status' => ($computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5) ? 0 : 1,
                    'last_seen' => $computer->last_seen ? $computer->last_seen->timestamp : 0,
                    'dbf_files_count' => $total,
                    'dbf_files_matched' => $matched,
                    'pct' => $pct,
                    default => strtolower($computer->nombre_instalacion ?? ''),
                };
            }, SORT_REGULAR, $sortDirection === 'desc')->values();

            $computers = $allComputers->slice($offsetInt, $lengthInt);

            $fileCategoryFilter = $request->query('file_category') ?? '';
            $data = $computers->map(function ($computer) use ($rbfLookup, $fileCategoryFilter) {
                $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
                $dbfFiles = array_values(array_filter($dbfFiles, fn ($f) => $this->isValidDbfFile($f)));

                $dbfFiles = array_map(function ($file) use ($computer, $rbfLookup) {
                    $key = strtolower($computer->plaza ?? '').'|'.($file['hash_md5'] ?? '').'|'.($file['name'] ?? '');
                    $rbfRecord = $rbfLookup[$key] ?? null;
                    $file['rbf_path'] = $rbfRecord ? $rbfRecord->path : null;
                    $file['rbf_hash'] = $rbfRecord ? $rbfRecord->hash : null;
                    $file['rbf_matched'] = $rbfRecord !== null;

                    unset($file['checksum']);

                    return $file;
                }, $dbfFiles);

                if (! empty($fileCategoryFilter) && in_array($fileCategoryFilter, ['exe', 'quickbck', 'other'])) {
                    $dbfFiles = array_values(array_filter($dbfFiles, fn ($f) => $this->getFileCategory($f) === $fileCategoryFilter));
                }

                $computerMatched = count(array_filter($dbfFiles, fn ($f) => $f['rbf_matched']));
                $status = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : 'offline';

                return [
                    'id' => $computer->id,
                    'nombre_instalacion' => $computer->nombre_instalacion,
                    'plaza' => $computer->plaza ?? 'N/A',
                    'group_name' => $computer->group->name ?? 'N/A',
                    'group_id' => $computer->group_id,
                    'status' => $status,
                    'last_seen' => $computer->last_seen ? $computer->last_seen->format('Y-m-d H:i:s') : 'Never',
                    'dbf_files_count' => count($dbfFiles),
                    'dbf_files_matched' => $computerMatched,
                    'dbf_files' => $dbfFiles,
                    'pvsi_bepartners_version' => $computer->pvsi_bepartners_version ?? null,
                    'pvsi_bepartners_fecha' => $computer->pvsi_bepartners_fecha ?? null,
                    'pvsi_bepartners_hora' => $computer->pvsi_bepartners_hora ?? null,
                ];
            });

            $perPlaza = [];
            foreach ($plazaStats as $plaza => $stats) {
                $perPlaza[] = [
                    'plaza' => $plaza,
                    'total' => $stats['total'],
                    'matched' => $stats['matched'],
                    'unmatched' => $stats['total'] - $stats['matched'],
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
                    'unmatched' => $stats['total'] - $stats['matched'],
                    'percent' => $stats['total'] > 0 ? round(($stats['matched'] / $stats['total']) * 100, 1) : 0,
                ];
            }
            usort($perFile, fn ($a, $b) => $b['total'] <=> $a['total']);
            $perFile = array_slice($perFile, 0, 15);

            $perGroup = [];
            foreach ($groupStats as $name => $stats) {
                $perGroup[] = [
                    'name' => $name,
                    'total' => $stats['total'],
                    'online' => $stats['online'],
                    'offline' => $stats['offline'],
                ];
            }
            usort($perGroup, fn ($a, $b) => $b['total'] <=> $a['total']);

            usort($computerOutdated, fn ($a, $b) => $b['unmatched'] <=> $a['unmatched']);
            $topOutdated = array_slice($computerOutdated, 0, 10);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
                'rbf_stats' => [
                    'total_files' => $globalTotal,
                    'total_matched' => $globalMatched,
                    'total_unmatched' => $globalTotal - $globalMatched,
                    'percent' => $globalTotal > 0 ? round(($globalMatched / $globalTotal) * 100, 1) : 0,
                    'per_category' => [
                        'exe' => [
                            'total' => $globalCategoryStats['exe']['total'],
                            'matched' => $globalCategoryStats['exe']['matched'],
                            'unmatched' => $globalCategoryStats['exe']['total'] - $globalCategoryStats['exe']['matched'],
                            'percent' => $globalCategoryStats['exe']['total'] > 0 ? round(($globalCategoryStats['exe']['matched'] / $globalCategoryStats['exe']['total']) * 100, 1) : 0,
                        ],
                        'quickbck' => [
                            'total' => $globalCategoryStats['quickbck']['total'],
                            'matched' => $globalCategoryStats['quickbck']['matched'],
                            'unmatched' => $globalCategoryStats['quickbck']['total'] - $globalCategoryStats['quickbck']['matched'],
                            'percent' => $globalCategoryStats['quickbck']['total'] > 0 ? round(($globalCategoryStats['quickbck']['matched'] / $globalCategoryStats['quickbck']['total']) * 100, 1) : 0,
                        ],
                        'other' => [
                            'total' => $globalCategoryStats['other']['total'],
                            'matched' => $globalCategoryStats['other']['matched'],
                            'unmatched' => $globalCategoryStats['other']['total'] - $globalCategoryStats['other']['matched'],
                            'percent' => $globalCategoryStats['other']['total'] > 0 ? round(($globalCategoryStats['other']['matched'] / $globalCategoryStats['other']['total']) * 100, 1) : 0,
                        ],
                    ],
                    'per_plaza' => $perPlaza,
                    'per_file' => $perFile,
                    'per_group' => $perGroup,
                    'top_outdated' => $topOutdated,
                ],
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('DbfFiles data error: '.$e->getMessage());

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
            $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
            $fileCategory = $request->query('file_category') ?? $request->input('file_category', '');
            $hash = $request->query('hash') ?? $request->input('hash', '');

            $query = Computer::with('group');

            if (is_array($plazaInput) && count($plazaInput) > 0) {
                $query->whereIn('plaza', $plazaInput);
            }

            if (is_array($groupInput) && count($groupInput) > 0) {
                $query->whereIn('group_id', $groupInput);
            }

            if (! empty($hash)) {
                $query->where('agent_config', 'ILIKE', '%'.$hash.'%');
            }

            $computers = $query->orderBy('nombre_instalacion')->get();

            $rbfLookup = $this->getRbfHashLookup();

            $computersData = $computers->map(function ($computer) use ($rbfLookup, $fileCategory) {
                $dbfFiles = $computer->agent_config['dbf_files'] ?? [];

                $dbfFiles = array_map(function ($file) use ($computer, $rbfLookup) {
                    $key = strtolower($computer->plaza ?? '').'|'.($file['hash_md5'] ?? '').'|'.($file['name'] ?? '');
                    $rbfRecord = $rbfLookup[$key] ?? null;
                    $file['rbf_path'] = $rbfRecord ? $rbfRecord->path : null;
                    $file['rbf_hash'] = $rbfRecord ? $rbfRecord->hash : null;

                    return $file;
                }, $dbfFiles);

                if (! empty($fileCategory) && in_array($fileCategory, ['exe', 'quickbck', 'other'])) {
                    $dbfFiles = array_values(array_filter($dbfFiles, fn ($f) => $this->getFileCategory($f) === $fileCategory));
                }

                $status = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : 'offline';

                return [
                    'nombre_instalacion' => $computer->nombre_instalacion,
                    'short_key' => $computer->short_key ?? '',
                    'plaza' => $computer->plaza ?? 'N/A',
                    'group_name' => $computer->group->name ?? 'N/A',
                    'status' => $status,
                    'last_seen' => $computer->last_seen ? $computer->last_seen->format('Y-m-d H:i:s') : 'Never',
                    'dbf_files' => $dbfFiles,
                ];
            })->toArray();

            $filename = 'Reporte_DBF_Files_'.date('Ymd_His');

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ];

            $callback = function () use ($computersData) {
                $output = fopen('php://output', 'w');

                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($output, [
                    'Computadora', 'ShortKey', 'Plaza', 'Grupo', 'Estado', 'Ultima Conexion',
                    'Archivo', 'Ruta', 'Tamano (KB)', 'Ultima Modificacion',
                    'MD5', 'Ruta RBF', 'Hash RBF',
                ]);

                foreach ($computersData as $computer) {
                    $dbfFiles = $computer['dbf_files'] ?? [];

                    if (empty($dbfFiles)) {
                        fputcsv($output, [
                            $computer['nombre_instalacion'],
                            $computer['short_key'],
                            $computer['plaza'],
                            $computer['group_name'],
                            $computer['status'],
                            $computer['last_seen'],
                            'Sin archivos',
                            '',
                            '',
                            '',
                            '',
                            '',
                            '',
                        ]);
                    } else {
                        foreach ($dbfFiles as $dbfFile) {
                            $sizeKb = '';
                            if (isset($dbfFile['size'])) {
                                $sizeKb = round($dbfFile['size'] / 1024, 2).' KB';
                            }

                            $modified = $this->excelTextValue($this->formatAgentModifiedTime($dbfFile['modified'] ?? ''));

                            fputcsv($output, [
                                $computer['nombre_instalacion'],
                                $computer['short_key'],
                                $computer['plaza'],
                                $computer['group_name'],
                                $computer['status'],
                                $computer['last_seen'],
                                $dbfFile['name'] ?? 'N/A',
                                $dbfFile['path'] ?? '',
                                $sizeKb,
                                $modified,
                                $dbfFile['hash_md5'] ?? '',
                                $dbfFile['rbf_path'] ?? '',
                                $dbfFile['rbf_hash'] ?? '',
                            ]);
                        }
                    }
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('DbfFiles export error: '.$e->getMessage());

            return redirect()->route('reportes.dbf-files')
                ->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }

    public function api(Request $request)
    {
        $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
        $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
        $fileCategory = $request->query('file_category') ?? $request->input('file_category', '');
        $hash = $request->query('hash') ?? $request->input('hash', '');
        $format = $request->query('format', 'json');

        $query = Computer::with('group');

        if (is_array($plazaInput) && count($plazaInput) > 0) {
            $query->whereIn('plaza', $plazaInput);
        }

        if (is_array($groupInput) && count($groupInput) > 0) {
            $query->whereIn('group_id', $groupInput);
        }

        if (! empty($hash)) {
            $query->where('agent_config', 'ILIKE', '%'.$hash.'%');
        }

        $computers = $query->orderBy('nombre_instalacion')->get();

        $rbfLookup = $this->getRbfHashLookup();

        $rows = [];
        foreach ($computers as $computer) {
            $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
            $dbfFiles = array_filter($dbfFiles, fn ($f) => $this->isValidDbfFile($f));
            $status = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : 'offline';

            foreach ($dbfFiles as $dbfFile) {
                $category = $this->getFileCategory($dbfFile);

                if (! empty($fileCategory) && in_array($fileCategory, ['exe', 'quickbck', 'other']) && $category !== $fileCategory) {
                    continue;
                }

                $key = strtolower($computer->plaza ?? '').'|'.($dbfFile['hash_md5'] ?? '').'|'.($dbfFile['name'] ?? '');
                $rbfRecord = $rbfLookup[$key] ?? null;

                $rows[] = [
                    'computadora' => $computer->nombre_instalacion,
                    'short_key' => $computer->short_key ?? '',
                    'plaza' => $computer->plaza ?? 'N/A',
                    'grupo' => $computer->group->name ?? 'N/A',
                    'estado' => $status,
                    'ultima_conexion' => $computer->last_seen ? $computer->last_seen->format('Y-m-d H:i:s') : 'Never',
                    'archivo' => $dbfFile['name'] ?? 'N/A',
                    'ruta' => $dbfFile['path'] ?? '',
                    'tamano_kb' => isset($dbfFile['size']) ? round($dbfFile['size'] / 1024, 2) : null,
                    'ultima_modificacion' => $this->formatAgentModifiedTime($dbfFile['modified'] ?? ''),
                    'md5' => $dbfFile['hash_md5'] ?? '',
                    'ruta_rbf' => $rbfRecord->path ?? null,
                    'hash_rbf' => $rbfRecord->hash ?? null,
                ];
            }
        }

        if ($format === 'csv') {
            $filename = 'Reporte_DBF_Files_API_'.date('Ymd_His').'.csv';

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ];

            $callback = function () use ($rows) {
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($output, [
                    'Computadora', 'ShortKey', 'Plaza', 'Grupo', 'Estado', 'UltimaConexion',
                    'Archivo', 'Ruta', 'TamanoKB', 'UltimaModificacion',
                    'MD5', 'RutaRBF', 'HashRBF',
                ]);

                foreach ($rows as $row) {
                    fputcsv($output, [
                        $row['computadora'],
                        $row['short_key'],
                        $row['plaza'],
                        $row['grupo'],
                        $row['estado'],
                        $row['ultima_conexion'],
                        $row['archivo'],
                        $row['ruta'],
                        $row['tamano_kb'],
                        $row['ultima_modificacion'],
                        $row['md5'],
                        $row['ruta_rbf'] ?? '',
                        $row['hash_rbf'] ?? '',
                    ]);
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json([
            'data' => $rows,
            'total' => count($rows),
            'total_computadoras' => $computers->count(),
        ]);
    }
}
