<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\ConciliacionHashArchivo;
use App\Models\Group;
use App\Services\ConciliacionHashArchivoService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteDbfFilesQuickbckController extends Controller
{
    public function index(Request $request)
    {
        $plazas = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza')
            ->pluck('id_plaza')
            ->filter()
            ->values();

        $groups = Group::orderBy('name')->get();

        $archivos = $this->getUniqueQuickBckFiles();

        return response()
            ->view('reportes.dbf-files-quickbck.index', compact('plazas', 'groups', 'archivos'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function getUniqueQuickBckFiles(): array
    {
        $computers = Computer::whereNotNull('agent_config')
            ->where('agent_config', '!=', '[]')
            ->get();

        $archivos = [];
        foreach ($computers as $computer) {
            $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
            foreach ($dbfFiles as $file) {
                if ($this->isQuickBckFile($file)) {
                    $name = $file['name'] ?? null;
                    if ($name && ! in_array($name, $archivos)) {
                        $archivos[] = $name;
                    }
                }
            }
        }

        sort($archivos);

        return $archivos;
    }

    private function isQuickBckFile(array $file): bool
    {
        $name = $file['name'] ?? '';
        $path = $file['path'] ?? '';

        return stripos($path, 'quickbck') !== false || stripos($name, 'quickbck') !== false;
    }

    private function getConciliacionLookup(): array
    {
        $map = [];
        $records = ConciliacionHashArchivo::all();
        foreach ($records as $r) {
            $key = strtolower($r->sucursal ?? '').'|'.strtolower($r->archivo ?? '');
            $disparador = strtolower($r->disparador ?? '');

            if (! isset($map[$key])) {
                $map[$key] = [];
            }

            $map[$key][$disparador] = $r;

            if (in_array($disparador, ['pvsi', 'cortefin'])) {
                if (! isset($map[$key]['_pvsi_latest'])) {
                    $map[$key]['_pvsi_latest'] = $r;
                } elseif ($r->fecha_modificacion > $map[$key]['_pvsi_latest']->fecha_modificacion) {
                    $map[$key]['_pvsi_latest'] = $r;
                }
            }
        }

        foreach ($map as $key => $entry) {
            if (isset($entry['_pvsi_latest'])) {
                $map[$key]['pvsi'] = $entry['_pvsi_latest'];
                unset($map[$key]['_pvsi_latest']);
            }
        }

        return $map;
    }

    private function formatAgentModifiedTime($modified)
    {
        $modified = trim((string) $modified);
        if ($modified === '') {
            return '';
        }

        if (preg_match('/\b(?:AM|PM|am|pm)\b/', $modified)) {
            return $modified;
        }

        $patterns = [
            '/^(?<date>\d{4}-\d{2}-\d{2})[ T](?<time>\d{1,2}:\d{2}(?::\d{2})?)(?:\.\d+)?(?:\s?(?<ampm>AM|PM|am|pm))?(?:[+-].*)?$/',
            '/^(?<date>\d{2}\/\d{2}\/\d{4})[ T](?<time>\d{1,2}:\d{2}(?::\d{2})?)(?:\s?(?<ampm>AM|PM|am|pm))?$/',
            '/^(?<time>\d{1,2}:\d{2}(?::\d{2})?)(?:\s?(?<ampm>AM|PM|am|pm))?$/',
        ];

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

                return trim(trim($date.' '.$time.($ampm ? ' '.$ampm : '')));
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

    private function parseAgentModified($modified): ?Carbon
    {
        $modified = trim((string) $modified);
        if ($modified === '') {
            return null;
        }

        try {
            return Carbon::parse($modified);
        } catch (\Throwable) {
            return null;
        }
    }

    private function classifyConciliacion(
        bool $pvsiMatched,
        bool $rbfMatched,
        ?ConciliacionHashArchivo $pvsiRecord,
        ?ConciliacionHashArchivo $rbfRecord,
        ?Carbon $quickModified
    ): array {
        $pvsiEqualsRbf = $pvsiRecord !== null
            && $rbfRecord !== null
            && strtolower($pvsiRecord->md5) === strtolower($rbfRecord->md5);

        if ($pvsiEqualsRbf) {
            return ['status' => 'conciliado', 'desactualizado' => false];
        }

        if (! $pvsiMatched && ! $rbfMatched) {
            return ['status' => 'sin_conciliar', 'desactualizado' => false];
        }

        $desactualizado = true;

        if ($pvsiRecord?->fecha_modificacion !== null
            && $rbfRecord?->fecha_modificacion !== null
            && $quickModified !== null) {
            $dates = [$pvsiRecord->fecha_modificacion, $rbfRecord->fecha_modificacion, $quickModified];
            $minDate = $dates[0];
            $maxDate = $dates[0];
            foreach ($dates as $date) {
                if ($date->lt($minDate)) {
                    $minDate = $date;
                }
                if ($date->gt($maxDate)) {
                    $maxDate = $date;
                }
            }
            $desactualizado = abs($maxDate->diffInSeconds($minDate)) > 300;
        }

        return [
            'status' => $desactualizado ? 'parcial_error' : 'parcial_ok',
            'desactualizado' => $desactualizado,
        ];
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

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre_instalacion', 'ILIKE', '%'.$search.'%')
                        ->orWhere('ip_address', 'ILIKE', '%'.$search.'%');
                });
            }

            $allComputers = $query->orderBy('nombre_instalacion')->get();

            $archivoInput = $request->query('archivo') ?? $request->input('archivo');

            $conciliacionLookup = $this->getConciliacionLookup();

            $flatRows = [];
            $globalStats = ['total' => 0, 'pvsi_matched' => 0, 'rbf_matched' => 0, 'both_matched' => 0, 'none_matched' => 0, 'conciliado' => 0, 'parcial_ok' => 0, 'parcial_error' => 0, 'sin_conciliar' => 0];
            $plazaStats = [];
            $fileStats = [];

            foreach ($allComputers as $computer) {
                $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
                $status = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : 'offline';

                foreach ($dbfFiles as $file) {
                    if (! $this->isQuickBckFile($file)) {
                        continue;
                    }

                    $fileName = $file['name'] ?? 'N/A';

                    if (! empty($archivoInput) && strtoupper($fileName) !== strtoupper($archivoInput)) {
                        continue;
                    }

                    $shortKey = strtolower($computer->short_key ?? '');
                    $archivoLower = strtolower($fileName);
                    $last5 = substr(strtolower($file['hash_md5'] ?? ''), -5);

                    $matches = $conciliacionLookup[$shortKey.'|'.$archivoLower] ?? [];
                    $pvsiRecord = $matches['pvsi'] ?? null;
                    $rbfRecord = $matches['rbf'] ?? null;

                    $pvsiMatched = $pvsiRecord !== null && $last5 === strtolower($pvsiRecord->md5);
                    $rbfMatched = $rbfRecord !== null && $last5 === strtolower($rbfRecord->md5);

                    $classification = $this->classifyConciliacion(
                        $pvsiMatched,
                        $rbfMatched,
                        $pvsiRecord,
                        $rbfRecord,
                        $this->parseAgentModified($file['modified'] ?? '')
                    );
                    $statusConciliacion = $classification['status'];

                    if ($pvsiMatched && $rbfMatched) {
                        $globalStats['both_matched']++;
                    }
                    if (! $pvsiMatched && ! $rbfMatched) {
                        $globalStats['none_matched']++;
                    }

                    $globalStats['total']++;
                    $globalStats[$statusConciliacion]++;
                    if ($pvsiMatched) {
                        $globalStats['pvsi_matched']++;
                    }
                    if ($rbfMatched) {
                        $globalStats['rbf_matched']++;
                    }

                    $rowPlaza = $computer->plaza ?? 'N/A';
                    if (! isset($plazaStats[$rowPlaza])) {
                        $plazaStats[$rowPlaza] = ['total' => 0, 'pvsi_matched' => 0, 'rbf_matched' => 0];
                    }
                    $plazaStats[$rowPlaza]['total']++;
                    if ($pvsiMatched) {
                        $plazaStats[$rowPlaza]['pvsi_matched']++;
                    }
                    if ($rbfMatched) {
                        $plazaStats[$rowPlaza]['rbf_matched']++;
                    }

                    if (! isset($fileStats[$fileName])) {
                        $fileStats[$fileName] = ['total' => 0, 'pvsi_matched' => 0, 'rbf_matched' => 0];
                    }
                    $fileStats[$fileName]['total']++;
                    if ($pvsiMatched) {
                        $fileStats[$fileName]['pvsi_matched']++;
                    }
                    if ($rbfMatched) {
                        $fileStats[$fileName]['rbf_matched']++;
                    }

                    $flatRows[] = [
                        'nombre_instalacion' => $computer->nombre_instalacion,
                        'plaza' => $rowPlaza,
                        'status' => $status,
                        'last_seen' => $computer->last_seen ? $computer->last_seen->format('Y-m-d H:i:s') : 'Never',
                        'archivo' => $fileName,
                        'tamano' => isset($file['size']) ? round($file['size'] / 1024, 2) : null,
                        'modificacion' => $this->formatAgentModifiedTime($file['modified'] ?? ''),
                        'md5' => substr($file['hash_md5'] ?? '', -5),
                        'pvsi_md5' => $pvsiRecord?->md5,
                        'pvsi_fecha' => $pvsiRecord?->fecha_modificacion?->format('Y-m-d H:i:s'),
                        'rbf_md5' => $rbfRecord?->md5,
                        'rbf_fecha' => $rbfRecord?->fecha_modificacion?->format('Y-m-d H:i:s'),
                        'pvsi_matched' => $pvsiMatched,
                        'rbf_matched' => $rbfMatched,
                        'status_conciliacion' => $statusConciliacion,
                        'desactualizado' => $classification['desactualizado'],
                    ];
                }
            }

            $estadoInput = $request->query('estado') ?? $request->input('estado', []);
            $estados = is_array($estadoInput) ? $estadoInput : array_values(array_filter([$estadoInput]));
            $allowedEstados = ['conciliado', 'parcial_ok', 'parcial_error', 'sin_conciliar'];
            $estados = array_values(array_intersect($estados, $allowedEstados));

            if (! empty($estados)) {
                $flatRows = array_values(array_filter($flatRows, fn ($row) => in_array($row['status_conciliacion'], $estados)));
            }

            $total = count($flatRows);

            $sortMap = [
                'nombre_instalacion' => fn ($r) => strtolower($r['nombre_instalacion']),
                'plaza' => fn ($r) => strtolower($r['plaza']),
                'status' => fn ($r) => $r['status'] === 'online' ? 0 : 1,
                'archivo' => fn ($r) => strtolower($r['archivo']),
                'status_conciliacion' => fn ($r) => match ($r['status_conciliacion']) {
                    'conciliado' => 0,
                    'parcial_ok' => 1,
                    'parcial_error' => 2,
                    default => 3,
                },
            ];
            $sortFn = $sortMap[$sortColumn] ?? fn ($r) => strtolower($r['nombre_instalacion']);

            usort($flatRows, function ($a, $b) use ($sortFn, $sortDirection) {
                $valA = $sortFn($a);
                $valB = $sortFn($b);
                $cmp = is_string($valA) ? strcmp($valA, $valB) : $valA <=> $valB;

                return $sortDirection === 'desc' ? -$cmp : $cmp;
            });

            $slicedRows = array_slice($flatRows, $offsetInt, $lengthInt);

            $perPlaza = [];
            foreach ($plazaStats as $plaza => $stats) {
                $perPlaza[] = [
                    'plaza' => $plaza,
                    'total' => $stats['total'],
                    'pvsi_matched' => $stats['pvsi_matched'],
                    'rbf_matched' => $stats['rbf_matched'],
                ];
            }
            usort($perPlaza, fn ($a, $b) => $b['total'] <=> $a['total']);

            $perFile = [];
            foreach ($fileStats as $name => $stats) {
                $perFile[] = [
                    'name' => $name,
                    'total' => $stats['total'],
                    'pvsi_matched' => $stats['pvsi_matched'],
                    'rbf_matched' => $stats['rbf_matched'],
                ];
            }
            usort($perFile, fn ($a, $b) => $b['total'] <=> $a['total']);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $slicedRows,
                'conciliacion_stats' => $globalStats,
                'per_plaza' => $perPlaza,
                'per_file' => $perFile,
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('DbfFilesQuickbck data error: '.$e->getMessage());

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
            $archivoInput = $request->query('archivo') ?? $request->input('archivo', '');

            $query = Computer::with('group');

            if (is_array($plazaInput) && count($plazaInput) > 0) {
                $query->whereIn('plaza', $plazaInput);
            }

            if (is_array($groupInput) && count($groupInput) > 0) {
                $query->whereIn('group_id', $groupInput);
            }

            $computers = $query->orderBy('nombre_instalacion')->get();

            $conciliacionLookup = $this->getConciliacionLookup();

            $filename = 'Reporte_QuickBCK_Conciliacion_'.date('Ymd_His');

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ];

            $callback = function () use ($computers, $conciliacionLookup, $archivoInput) {
                $output = fopen('php://output', 'w');

                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($output, [
                    'Computadora', 'Plaza', 'Archivo',
                    'Tamano (KB)', 'MD5 Pvsi', 'Fecha Pvsi', 'MD5 Quick', 'Fecha Quick', 'MD5 RBF', 'Fecha RBF',
                    'Conciliacion', 'Desactualizado',
                ]);

                foreach ($computers as $computer) {
                    $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
                    $status = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : 'offline';

                    foreach ($dbfFiles as $dbfFile) {
                        if (! $this->isQuickBckFile($dbfFile)) {
                            continue;
                        }

                        $fileName = $dbfFile['name'] ?? 'N/A';

                        if (! empty($archivoInput) && strtoupper($fileName) !== strtoupper($archivoInput)) {
                            continue;
                        }

                        $shortKey = strtolower($computer->short_key ?? '');
                        $last5 = substr(strtolower($dbfFile['hash_md5'] ?? ''), -5);
                        $matches = $conciliacionLookup[$shortKey.'|'.strtolower($fileName)] ?? [];
                        $pvsiRecord = $matches['pvsi'] ?? null;
                        $rbfRecord = $matches['rbf'] ?? null;

                        $pvsiMatched = $pvsiRecord !== null && $last5 === strtolower($pvsiRecord->md5);
                        $rbfMatched = $rbfRecord !== null && $last5 === strtolower($rbfRecord->md5);

                        $classification = $this->classifyConciliacion(
                            $pvsiMatched,
                            $rbfMatched,
                            $pvsiRecord,
                            $rbfRecord,
                            $this->parseAgentModified($dbfFile['modified'] ?? '')
                        );
                        $conciliacion = match ($classification['status']) {
                            'conciliado' => 'Conciliado',
                            'parcial_ok' => 'Parcial OK',
                            'parcial_error' => 'Parcial Error',
                            default => 'Sin Conciliar',
                        };

                        $sizeKb = isset($dbfFile['size']) ? round($dbfFile['size'] / 1024, 2).' KB' : '';
                        $modified = $this->excelTextValue($this->formatAgentModifiedTime($dbfFile['modified'] ?? ''));

                        fputcsv($output, [
                            $computer->nombre_instalacion,
                            $computer->plaza ?? 'N/A',
                            $fileName,
                            $sizeKb,
                            $pvsiRecord?->md5 ?? '',
                            $pvsiRecord?->fecha_modificacion?->format('Y-m-d H:i:s') ?? '',
                            $dbfFile['hash_md5'] ?? '',
                            $modified,
                            $rbfRecord?->md5 ?? '',
                            $rbfRecord?->fecha_modificacion?->format('Y-m-d H:i:s') ?? '',
                            $conciliacion,
                            $classification['desactualizado'] ? 'Si' : 'No',
                        ]);
                    }
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('DbfFilesQuickbck export error: '.$e->getMessage());

            return redirect()->route('reportes.dbf-files-quickbck')
                ->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }

    public function sync(ConciliacionHashArchivoService $service)
    {
        $result = $service->fetchAndSync();

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => "Sincronizacion completada: {$result['count']} registros actualizados.",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Error al sincronizar: '.$result['message'],
        ], 500);
    }
}
