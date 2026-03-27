<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Group;
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

        return view('reportes.dbf-files.index', compact('plazas', 'groups', 'archivos'));
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

    public function data(Request $request)
    {
        $draw = (int) $request->input('draw', 1);
        $startIdx = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 50);
        $search = $request->input('search.value', '');
        $lengthInt = (int) $length;
        $offsetInt = (int) $startIdx;

        try {
            $query = Computer::with('group')->whereNotNull('agent_config');

            $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
            if (is_array($plazaInput) && count($plazaInput) > 0) {
                $query->whereHas('group', function ($q) use ($plazaInput) {
                    $q->where(function ($sub) use ($plazaInput) {
                        foreach ($plazaInput as $plaza) {
                            $sub->orWhere('name', 'LIKE', $plaza.'%');
                        }
                    });
                });
            }

            $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
            if (is_array($groupInput) && count($groupInput) > 0) {
                $query->whereIn('group_id', $groupInput);
            }

            $archivoInput = $request->query('archivo') ?? $request->input('archivo');
            if (! empty($archivoInput)) {
                $query->where('agent_config', 'ILIKE', '%'.$archivoInput.'%');
            }

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('computer_name', 'ILIKE', '%'.$search.'%')
                        ->orWhere('ip_address', 'ILIKE', '%'.$search.'%');
                });
            }

            $total = $query->count();

            $computers = $query->orderBy('computer_name')
                ->offset($offsetInt)
                ->limit($lengthInt)
                ->get();

            $data = $computers->map(function ($computer) {
                $dbfFiles = $computer->agent_config['dbf_files'] ?? [];

                $plaza = 'N/A';
                if ($computer->group) {
                    $groupName = $computer->group->name;
                    foreach (['PENINSULA', 'NICARAGUA', 'CHETUMAL', 'XALAPA', 'CANCUN', 'VILLA', 'MERIDA', 'COLIMA', 'TAMPICO', 'SOLIDARIO'] as $p) {
                        if (stripos($groupName, $p) !== false) {
                            $plaza = $p;
                            break;
                        }
                    }
                }

                return [
                    'id' => $computer->id,
                    'computer_name' => $computer->computer_name,
                    'plaza' => $plaza,
                    'group_name' => $computer->group->name ?? 'N/A',
                    'group_id' => $computer->group_id,
                    'status' => $computer->status,
                    'last_seen' => $computer->last_seen ? $computer->last_seen->format('Y-m-d H:i:s') : 'Never',
                    'dbf_files_count' => count($dbfFiles),
                    'dbf_files' => $dbfFiles,
                ];
            });

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $total,
                'recordsFiltered' => (int) $total,
                'data' => $data,
            ]);
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
            $archivo = $request->query('archivo') ?? $request->input('archivo', '');

            $query = Computer::with('group')->whereNotNull('agent_config');

            if (is_array($plazaInput) && count($plazaInput) > 0) {
                $query->whereHas('group', function ($q) use ($plazaInput) {
                    $q->where(function ($sub) use ($plazaInput) {
                        foreach ($plazaInput as $plaza) {
                            $sub->orWhere('name', 'LIKE', $plaza.'%');
                        }
                    });
                });
            }

            if (is_array($groupInput) && count($groupInput) > 0) {
                $query->whereIn('group_id', $groupInput);
            }

            if (! empty($archivo)) {
                $query->where('agent_config', 'ILIKE', '%'.$archivo.'%');
            }

            $computers = $query->orderBy('computer_name')->get();

            error_log('DbfFiles Export - Total computers found: '.$computers->count().' for archivo: '.$archivo);

            $computersData = $computers->map(function ($computer) {
                $dbfFiles = $computer->agent_config['dbf_files'] ?? [];

                $plaza = 'N/A';
                if ($computer->group) {
                    $groupName = $computer->group->name;
                    foreach (['PENINSULA', 'NICARAGUA', 'CHETUMAL', 'XALAPA', 'CANCUN', 'VILLA', 'MERIDA', 'COLIMA', 'TAMPICO', 'SOLIDARIO'] as $p) {
                        if (stripos($groupName, $p) !== false) {
                            $plaza = $p;
                            break;
                        }
                    }
                }

                return [
                    'computer_name' => $computer->computer_name,
                    'plaza' => $plaza,
                    'group_name' => $computer->group->name ?? 'N/A',
                    'status' => $computer->status,
                    'last_seen' => $computer->last_seen ? $computer->last_seen->format('Y-m-d H:i:s') : 'Never',
                    'dbf_files' => $dbfFiles,
                ];
            })->toArray();

            $filename = 'Reporte_DBF_Files_'.date('Ymd_His');

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
            ];

            $callback = function () use ($computersData) {
                $output = fopen('php://output', 'w');

                fputcsv($output, [
                    'Computadora', 'Plaza', 'Grupo', 'Estado', 'Última Conexión',
                    'Archivo DBF', 'Ruta', 'Tamaño (KB)', 'Última Modificación',
                ]);

                foreach ($computersData as $computer) {
                    $dbfFiles = $computer['dbf_files'] ?? [];

                    if (empty($dbfFiles)) {
                        fputcsv($output, [
                            $computer['computer_name'],
                            $computer['plaza'],
                            $computer['group_name'],
                            $computer['status'],
                            $computer['last_seen'],
                            'Sin archivos',
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

                            $modified = $dbfFile['modified'] ?? '';
                            if (! empty($modified) && strpos($modified, 'T') !== false) {
                                $parts = explode('T', $modified);
                                $modified = $parts[0].' '.substr($parts[1], 0, 8);
                            }

                            fputcsv($output, [
                                $computer['computer_name'],
                                $computer['plaza'],
                                $computer['group_name'],
                                $computer['status'],
                                $computer['last_seen'],
                                $dbfFile['name'] ?? 'N/A',
                                $dbfFile['path'] ?? '',
                                $sizeKb,
                                $modified,
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
}
