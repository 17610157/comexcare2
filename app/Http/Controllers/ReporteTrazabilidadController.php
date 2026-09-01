<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\ConciliacionHashArchivo;
use App\Models\Group;
use App\Models\HashArchivoHistorial;
use App\Models\HashArchivoLote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteTrazabilidadController extends Controller
{
    private const ORDER = ['cortefin', 'rbf', 'canov', 'nicar', 'guate', 'guada', 'rebsa'];

    private const EXCLUIDOS = ['quickbck', 'tienda'];

    private const SIN_FILTRO_IP = ['canov', 'rebsa', 'rbf', 'guada', 'guate', 'nicar'];

    private const ARCHIVOS_PERMITIDOS = [
        'AJTFLU.DBF', 'ASISTE.DBF', 'CAJAS.DBF', 'CANCFDI.DBF', 'CANOTA.DBF',
        'CANOTA.DBT', 'CANOTA.FPT', 'CANOTAEX.DBF', 'CARPORT.DBF', 'CASEMANA.DBF',
        'CAT_NEG.DBF', 'CAT_PROD.DBF', 'CAT_PROD2.DBF', 'CATPROD3.DBF',
        'CCOTIZA.DBF', 'CENTER.DBF', 'CFDREL.DBF', 'CG3_VAEN.DBF', 'CG3_VAPA.DBF',
        'CLIENTE.DBF', 'COBRANZA.DBF', 'COMPRAS.DBF', 'CONCXC.DBF', 'CONVTODO.DBF',
        'CPEDIDO.DBF', 'CPENDIE.DBF', 'CPXCORTE.DBF', 'CRMC_OBS.DBF', 'CUENGAS.DBF',
        'CUNOTA.DBF', 'DD_CONTROL.DBF', 'DD_DATOS.DBF', 'DESEMANA.DBF',
        'ES_COBRO.DBF', 'EYSIENC.DBF', 'EYSIPAR.DBF', 'FACCFD.DBF', 'FACCFD.DBT',
        'FACCFD.FPT', 'FLUJO01.DBF', 'FLUJORES.DBF', 'HISTORIA.DBF', 'INVFSIC.DBF',
        'INVFISIC.DBF', 'M_CONF.DBF', 'MASTER.DBF', 'MINTINV.DBF', 'MOVCXCD.DBF',
        'MOVSINV.DBF', 'N_CONF.DBF', 'N_RESP.DBF', 'N_RESP_M.DBF', 'NEGADOS.DBF',
        'NOESTA.DBF', 'NOHAY.DBF', 'OBSDOCS.DBF', 'PAGDOCS.DBF', 'PAGMULT.DBF',
        'PAGSPEI.DBF', 'PARAMS.DBF', 'PARXCAR.DBF', 'PARTCOMP.DBF', 'PARTCOT.DBF',
        'PARTMINT.DBF', 'PARTVTA.DBF', 'PARTVTPP.DBF', 'PAVACL.DBF', 'PCOTIZA.DBF',
        'PEDIDO.DBF', 'PEDIDO1.DBF', 'PEDIDO2.DBF', 'PPEDIDO.DBF', 'PPENDIE.DBF',
        'PROVPROD.DBF', 'R_BBVA.DBF', 'R_KUSHKI.DBF', 'RESP_PIN.DBF', 'SERCFD2.DBF',
        'STOCK.DBF', 'SUCURCTAI.DBF', 'TABLA004.DBF', 'TABLA005.DBF', 'TABLA010.DBF',
        'TABLACON.DBF', 'TERCAJAS.DBF', 'TLSERVI.DBF', 'USUARIOS.DBF', 'VACLI.DBF',
        'VALES.DBF', 'VALPE.DBF', 'VCPENDI.DBF', 'VENDEDOR.DBF', 'VENTA.DBF',
        'VENTA.DBT', 'VENTA.FPT', 'VENTAPP.DBF', 'VPPENDI.DBF', 'XCORTE.DBF',
    ];

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

        return response()
            ->view('reportes.trazabilidad.index', compact('plazas', 'groups'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function data(Request $request)
    {
        $draw = (int) ($request->query('draw') ?? $request->input('draw', 1));
        $startIdx = (int) ($request->query('start') ?? $request->input('start', 0));
        $length = (int) ($request->query('length') ?? $request->input('length', 50));
        $search = $request->query('search') ?? $request->input('search.value', '');

        try {
            $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
            $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
            $typeInput = $request->query('type') ?? $request->input('type', []);
            $estadoInput = $request->query('estado') ?? $request->input('estado', '');
            $archivoInput = $request->query('archivo') ?? $request->input('archivo', []);

            $query = Computer::with('group');
            if (is_array($plazaInput) && count($plazaInput) > 0) {
                $query->whereIn('plaza', $plazaInput);
            }
            if (is_array($typeInput) && count($typeInput) > 0) {
                $typeGroupIds = Group::whereIn('type', $typeInput)->pluck('id');
                $query->whereIn('group_id', $typeGroupIds);
            }
            if (is_array($groupInput) && count($groupInput) > 0) {
                $query->whereIn('group_id', $groupInput);
            }
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre_instalacion', 'ILIKE', '%'.$search.'%')
                        ->orWhere('short_key', 'ILIKE', '%'.$search.'%')
                        ->orWhere('ip_address', 'ILIKE', '%'.$search.'%');
                });
            }

            $computers = $query->orderBy('nombre_instalacion')->get();

            $estadoFiltro = strtolower(trim((string) $estadoInput));
            $archivosFiltro = array_values(array_filter(array_map(
                fn ($a) => strtolower(trim((string) $a)),
                is_array($archivoInput) ? $archivoInput : explode(',', (string) $archivoInput)
            ), fn ($a) => $a !== ''));

            $rows = [];
            $colSet = [];
            $rowIndex = 0;
            $endIdx = $length ? ($startIdx + $length) : null;
            foreach ($computers as $c) {
                $estado = $c->last_seen && $c->last_seen->diffInMinutes(now()) <= 5
                    ? 'online'
                    : ($c->status ?? 'offline');

                if (! empty($estadoFiltro) && $estado !== $estadoFiltro) {
                    continue;
                }

                $col = $this->construirTrazabilidadTienda($c);

                foreach ($col['columnas'] as $disp) {
                    if (! in_array($disp, $colSet)) {
                        $colSet[] = $disp;
                    }
                }

                foreach ($col['files'] as $file) {
                    $archivo = $file['archivo'];
                    if (! empty($archivosFiltro) && ! in_array($archivo, $archivosFiltro)) {
                        continue;
                    }

                    $inSlice = $rowIndex >= $startIdx && ($endIdx === null || $rowIndex < $endIdx);
                    $rowIndex++;

                    if ($inSlice) {
                        $rows[] = $this->filaReporte($c, $estado, $archivo, $file, $col['columnas']);
                    }
                }
            }

            $colDisp = $this->calcularColumnasGlobalesFromSet($colSet);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => (int) $rowIndex,
                'recordsFiltered' => (int) $rowIndex,
                'columnas' => $colDisp,
                'data' => $rows,
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('Trazabilidad data error: '.$e->getMessage());

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'columnas' => [],
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function celdaArchivo(array $file, string $disp): ?array
    {
        if ($disp === 'quickbck') {
            $hashes = [];
            $rutas = [];
            $fechas = [];
            foreach ($file['quickbck'] ?? [] as $qb) {
                if (! empty($qb['hash'])) {
                    $hashes[] = $qb['hash'];
                    $rutas[] = $qb['path'] ?? '';
                    if (! empty($qb['modified'])) {
                        $fechas[] = $qb['modified'];
                    }
                }
            }
            if (empty($hashes)) {
                return null;
            }

            return [
                'hash' => implode(' | ', $hashes),
                'path' => implode(' | ', $rutas),
                'fecha_modificacion' => $fechas ? implode(' | ', $fechas) : null,
                'fecha_creacion' => null,
                'fecha_consulta_api' => null,
            ];
        }

        $cell = $file['disparadores'][$disp] ?? null;
        if (! $cell || empty($cell['hash'])) {
            return null;
        }

        return [
            'hash' => (string) $cell['hash'],
            'path' => $cell['path'] ?? null,
            'fecha_modificacion' => $cell['fecha_modificacion'] ?? null,
            'fecha_creacion' => $cell['fecha_creacion'] ?? null,
            'fecha_consulta_api' => $cell['fecha_consulta_api'] ?? null,
            'historial' => $cell['historial'] ?? [],
            'instante' => $this->instanteDeFecha($cell['fecha_consulta_api'] ?? null),
        ];
    }

    private function filaReporte(Computer $c, string $estado, string $archivo, array $file, array $columnas): array
    {
        $hashes = [];
        foreach ($columnas as $disp) {
            $hashes[$disp] = $this->celdaArchivo($file, $disp);
        }

        $rutas = [];
        $fechaMod = null;
        foreach ($columnas as $disp) {
            $cell = $hashes[$disp] ?? null;
            if (! $cell || empty($cell['hash'])) {
                continue;
            }
            $rutas[] = [
                'disparador' => $disp,
                'hash' => (string) $cell['hash'],
                'ruta' => (isset($cell['path']) && $cell['path'] !== '') ? (string) $cell['path'] : null,
                'fecha_modificacion' => isset($cell['fecha_modificacion']) && $cell['fecha_modificacion'] ? $cell['fecha_modificacion'] : null,
                'fecha_creacion' => isset($cell['fecha_creacion']) && $cell['fecha_creacion'] ? $cell['fecha_creacion'] : null,
                'fecha_consulta_api' => isset($cell['fecha_consulta_api']) && $cell['fecha_consulta_api'] ? $cell['fecha_consulta_api'] : null,
                'historial' => array_map(fn ($h) => [
                    'hash' => $h['hash'] ?? '',
                    'fecha_modificacion' => $h['fecha_modificacion'] ?? null,
                    'fecha_consulta_api' => $h['fecha_consulta_api'] ?? null,
                    'disparador' => $h['disparador'] ?? $disp,
                ], $cell['historial'] ?? []),
            ];
            if (! $fechaMod && $disp !== 'quickbck' && ! empty($cell['fecha_modificacion'])) {
                $fechaMod = $cell['fecha_modificacion'];
            }
        }

        return [
            'id' => $c->id,
            'nombre_instalacion' => $c->nombre_instalacion,
            'short_key' => $c->short_key,
            'plaza' => $c->plaza ?? 'N/A',
            'grupo' => $c->group?->name ?? 'N/A',
            'estado' => $estado,
            'archivo' => strtoupper($archivo),
            'hashes' => $hashes,
            'fecha_modificacion' => $fechaMod,
            'rutas' => $rutas,
        ];
    }

    private function calcularColumnasGlobalesFromSet(array $colSet): array
    {
        $base = ['cortefin/pvsi', 'quickbck', 'rbf'];
        $resto = [];
        foreach ($colSet as $disp) {
            if (! in_array($disp, $base) && ! in_array($disp, $resto)) {
                $resto[] = $disp;
            }
        }

        return array_merge($base, $resto);
    }

    private function estaArchivoPermitido(string $archivo): bool
    {
        $nombre = strtolower(trim($archivo));

        return in_array($nombre, $this->archivosPermitidosLower(), true);
    }

    private function archivosPermitidosLower(): array
    {
        static $cache = null;

        if ($cache === null) {
            $cache = array_map(fn ($a) => strtolower(trim($a)), self::ARCHIVOS_PERMITIDOS);
        }

        return $cache;
    }

    private function filtrarArchivosPermitidos(array $archivos): array
    {
        $permitidos = $this->archivosPermitidosLower();

        return array_values(array_filter($archivos, fn ($a) => in_array(strtolower(trim((string) $a)), $permitidos, true)));
    }

    public function archivos(Request $request)
    {
        $shortKey = strtolower(trim((string) $request->query('short_key')));

        try {
            $computer = Computer::with('group')->get()
                ->first(fn ($c) => strtolower(trim((string) $c->short_key)) === $shortKey);

            if (! $computer) {
                return response()->json([
                    'tienda' => ['nombre' => $shortKey, 'short_key' => $shortKey, 'plaza' => 'N/A', 'grupo' => 'N/A', 'estado' => 'N/A'],
                    'columnas' => [],
                    'files' => [],
                ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            }

            return response()->json($this->construirTrazabilidadTienda($computer))
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('Trazabilidad archivos error: '.$e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
                'tienda' => null,
                'columnas' => [],
                'files' => [],
            ], 500);
        }
    }

    private function construirTrazabilidadTienda(Computer $computer, ?array $recordsPorSucursal = null, ?array $rutaBasePorSucursal = null, ?array $historialPorSucursal = null): array
    {
        $shortKey = strtolower(trim((string) $computer->short_key));

        $records = $recordsPorSucursal !== null
            ? ($recordsPorSucursal[$shortKey] ?? collect())
            : ConciliacionHashArchivo::whereRaw('lower(sucursal) = ?', [$shortKey])
                ->where(function ($q) use ($computer) {
                    $q->where('ip', $computer->ip_address)
                        ->orWhereIn(DB::raw('lower(disparador)'), self::SIN_FILTRO_IP);
                })
                ->whereNotIn(DB::raw('lower(disparador)'), array_merge(self::EXCLUIDOS, ['pruebas', 'pruebas2', 'pruebat']))
                ->get();

        $porArchivo = [];
        foreach ($records as $r) {
            $archivo = strtolower($r->archivo ?? '');
            $disp = strtolower($r->disparador);
            $porArchivo[$archivo][$disp] = $r;
        }

        $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
        $quickbckPorArchivo = [];
        foreach ($dbfFiles as $file) {
            $name = strtolower($file['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $quickbckPorArchivo[$name][] = [
                'path' => $file['path'] ?? 'N/A',
                'hash' => $file['hash_md5'] ?? '',
                'hash_corto' => substr(strtolower($file['hash_md5'] ?? ''), -5),
                'modified' => $file['modified'] ?? null,
            ];
        }

        $rutaBasePorDisparador = $rutaBasePorSucursal !== null
            ? ($rutaBasePorSucursal[$shortKey] ?? [])
            : HashArchivoLote::whereRaw('lower(sucursal) = ?', [$shortKey])
                ->where(function ($q) use ($computer) {
                    $q->where('ip', $computer->ip_address)
                        ->orWhereIn(DB::raw('lower(disparador)'), self::SIN_FILTRO_IP);
                })
                ->orderByDesc('fecha_envio')
                ->get(['disparador', 'ruta_base'])
                ->reduce(function ($carry, $lote) {
                    $disp = strtolower((string) $lote->disparador);
                    if (! isset($carry[$disp]) && $lote->ruta_base) {
                        $carry[$disp] = rtrim((string) $lote->ruta_base, '\\/');
                    }

                    return $carry;
                }, []);

        $todosArchivos = array_unique(array_merge(array_keys($porArchivo), array_keys($quickbckPorArchivo)));
        $todosArchivos = array_values(array_filter($todosArchivos, fn ($a) => in_array(strtolower(pathinfo($a, PATHINFO_EXTENSION)), ['dbf', 'dbt', 'fpt'])));
        $todosArchivos = $this->filtrarArchivosPermitidos($todosArchivos);
        sort($todosArchivos);

        $historial = $historialPorSucursal !== null
            ? ($historialPorSucursal[$shortKey] ?? [])
            : $this->historialDeSucursal($shortKey, $computer, $todosArchivos);

        $disparadoresPresentes = [];
        foreach ($porArchivo as $archivo => $dispMap) {
            foreach ($dispMap as $disp => $rec) {
                if (! in_array($disp, $disparadoresPresentes)) {
                    $disparadoresPresentes[] = $disp;
                }
            }
        }

        $colDisparadores = ['cortefin/pvsi', 'quickbck', 'rbf'];
        foreach (self::ORDER as $disp) {
            if (in_array($disp, ['cortefin', 'rbf'])) {
                continue;
            }
            if (in_array($disp, $disparadoresPresentes)) {
                $colDisparadores[] = $disp;
            }
        }
        foreach ($disparadoresPresentes as $disp) {
            if (! in_array($disp, $colDisparadores) && ! in_array($disp, ['pvsi', 'cortefin', 'rbf', 'quickbck'])) {
                $colDisparadores[] = $disp;
            }
        }

        $files = [];
        foreach ($todosArchivos as $archivo) {
            $dispMap = $porArchivo[$archivo] ?? [];
            $qbFiles = $quickbckPorArchivo[$archivo] ?? [];

            $fileRow = ['archivo' => $archivo];

            $fileRow['quickbck'] = array_map(fn ($f) => [
                'hash' => $f['hash_corto'] ?: $f['hash'],
                'path' => $f['path'],
                'modified' => $f['modified'] ?? null,
            ], $qbFiles);

            $cortefinRec = $dispMap['cortefin'] ?? null;
            $pvsiRec = $dispMap['pvsi'] ?? null;
            $primerRec = $this->anclaCortefinPvsi($cortefinRec, $pvsiRec);
            $primerHash = $primerRec ? strtolower($primerRec->md5) : null;
            $colNombre = $cortefinRec && $pvsiRec
                ? ($primerRec === $pvsiRec ? 'pvsi' : 'cortefin')
                : ($cortefinRec ? 'cortefin' : 'pvsi');

            foreach ($colDisparadores as $disp) {
                if ($disp === 'quickbck') {
                    continue;
                }
                if ($disp === 'cortefin/pvsi') {
                    if ($primerRec) {
                        $fileRow['disparadores']['cortefin/pvsi'] = [
                            'hash' => $primerRec->md5,
                            'path' => $this->rutaDeDisparador($rutaBasePorDisparador, $colNombre, $archivo),
                            'fecha_modificacion' => isset($primerRec->fecha_modificacion) ? $primerRec->fecha_modificacion : null,
                            'fecha_creacion' => isset($primerRec->created_at) ? $primerRec->created_at : null,
                            'fecha_consulta_api' => isset($primerRec->fecha_consulta_api) ? $primerRec->fecha_consulta_api : null,
                            'es_ancla' => true,
                            'desactualizado' => false,
                            'historial' => $this->historialDePunto($historial, $archivo, ['cortefin', 'pvsi']),
                        ];
                    } else {
                        $fileRow['disparadores']['cortefin/pvsi'] = null;
                    }

                    continue;
                }
                $rec = $dispMap[$disp] ?? null;
                if ($rec) {
                    $recHash = strtolower($rec->md5);
                    $fileRow['disparadores'][$disp] = [
                        'hash' => $rec->md5,
                        'path' => $this->rutaDeDisparador($rutaBasePorDisparador, $disp, $archivo),
                        'fecha_modificacion' => isset($rec->fecha_modificacion) ? $rec->fecha_modificacion : null,
                        'fecha_creacion' => isset($rec->created_at) ? $rec->created_at : null,
                        'fecha_consulta_api' => isset($rec->fecha_consulta_api) ? $rec->fecha_consulta_api : null,
                        'es_ancla' => false,
                        'desactualizado' => $primerHash !== null && $recHash !== $primerHash,
                        'historial' => $this->historialDePunto($historial, $archivo, [$disp]),
                    ];
                } else {
                    $fileRow['disparadores'][$disp] = null;
                }
            }

            $files[] = $fileRow;
        }

        return [
            'tienda' => [
                'nombre' => $computer->nombre_instalacion ?? $shortKey,
                'short_key' => $shortKey,
                'plaza' => $computer->plaza ?? 'N/A',
                'grupo' => $computer->group?->name ?? 'N/A',
                'estado' => $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5 ? 'online' : ($computer->status ?? 'offline'),
            ],
            'columnas' => $colDisparadores,
            'files' => $files,
        ];
    }

    public function archivosDisponibles(Request $request)
    {
        try {
            $archivos = ConciliacionHashArchivo::select('archivo')
                ->distinct()
                ->pluck('archivo')
                ->map(fn ($a) => strtolower((string) $a))
                ->filter(fn ($a) => $this->estaArchivoPermitido($a))
                ->values()
                ->all();

            $agente = Computer::get()
                ->pluck('agent_config')
                ->map(fn ($cfg) => array_map(
                    fn ($f) => strtolower((string) ($f['name'] ?? '')),
                    $cfg['dbf_files'] ?? []
                ))
                ->flatten()
                ->filter(fn ($n) => $n !== '' && $this->estaArchivoPermitido($n))
                ->values()
                ->all();

            $todos = array_values(array_unique(array_merge($archivos, $agente)));
            sort($todos);

            return response()->json(['archivos' => $todos])
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('Trazabilidad archivosDisponibles error: '.$e->getMessage());

            return response()->json(['archivos' => []], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
            $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
            $typeInput = $request->query('type') ?? $request->input('type', []);
            $search = $request->query('search') ?? $request->input('search', '');
            $estadoInput = $request->query('estado') ?? $request->input('estado', '');
            $archivoInput = $request->query('archivo') ?? $request->input('archivo', []);

            $query = Computer::with('group');
            if (is_array($plazaInput) && count($plazaInput) > 0) {
                $query->whereIn('plaza', $plazaInput);
            }
            if (is_array($typeInput) && count($typeInput) > 0) {
                $typeGroupIds = Group::whereIn('type', $typeInput)->pluck('id');
                $query->whereIn('group_id', $typeGroupIds);
            }
            if (is_array($groupInput) && count($groupInput) > 0) {
                $query->whereIn('group_id', $groupInput);
            }
            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre_instalacion', 'ILIKE', '%'.$search.'%')
                        ->orWhere('short_key', 'ILIKE', '%'.$search.'%')
                        ->orWhere('ip_address', 'ILIKE', '%'.$search.'%');
                });
            }

            $computers = $query->orderBy('nombre_instalacion')->get();

            $estadoFiltro = strtolower(trim((string) $estadoInput));
            $archivosFiltro = array_values(array_filter(array_map(
                fn ($a) => strtolower(trim((string) $a)),
                is_array($archivoInput) ? $archivoInput : explode(',', (string) $archivoInput)
            ), fn ($a) => $a !== ''));

            $colecciones = [];
            foreach ($computers as $computer) {
                $estado = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5
                    ? 'online'
                    : ($computer->status ?? 'offline');

                if (! empty($estadoFiltro) && $estado !== $estadoFiltro) {
                    continue;
                }

                $colecciones[] = $this->construirTrazabilidadTienda($computer);
            }

            $colDisparadores = $this->calcularColumnasGlobales($colecciones);

            $filename = 'Trazabilidad_'.date('Ymd_His');

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ];

            $callback = function () use ($colecciones, $colDisparadores, $archivosFiltro) {
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                $heads = ['Tienda', 'Clave', 'Plaza', 'Estado', 'Archivo'];
                foreach ($colDisparadores as $disp) {
                    $label = $disp === 'quickbck' ? 'QuickBCK' : strtoupper($disp);
                    $heads[] = 'Hash '.$label;
                    $heads[] = 'Fecha '.$label;
                }
                fputcsv($output, $heads, ';');

                foreach ($colecciones as $col) {
                    $tienda = $col['tienda'];
                    foreach ($col['files'] as $file) {
                        $archivo = $file['archivo'];
                        if (! empty($archivosFiltro) && ! in_array($archivo, $archivosFiltro)) {
                            continue;
                        }

                        $row = [
                            $tienda['nombre'],
                            $tienda['short_key'],
                            $tienda['plaza'],
                            $tienda['estado'],
                            strtoupper($archivo),
                        ];

                        foreach ($colDisparadores as $disp) {
                            if ($disp === 'quickbck') {
                                $hashes = [];
                                $fechas = [];
                                foreach ($file['quickbck'] ?? [] as $qb) {
                                    $hashes[] = $qb['hash'] ?? '';
                                    $fechas[] = $qb['modified'] ?? '';
                                }
                                $row[] = implode(' | ', $hashes);
                                $row[] = implode(' | ', $fechas);

                                continue;
                            }
                            $cell = $file['disparadores'][$disp] ?? null;
                            $row[] = $cell && ! empty($cell['hash']) ? $cell['hash'] : 'no se encuentra archivo en ubicacion';
                            $row[] = $cell && ! empty($cell['fecha_modificacion']) ? $cell['fecha_modificacion'] : '';
                        }

                        fputcsv($output, $row, ';');
                    }
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Trazabilidad export error: '.$e->getMessage());

            return redirect()->route('reportes.trazabilidad')
                ->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }

    private function calcularColumnasGlobales(array $colecciones): array
    {
        $base = ['cortefin/pvsi', 'quickbck', 'rbf'];
        $resto = [];
        foreach ($colecciones as $col) {
            foreach ($col['columnas'] as $disp) {
                if (! in_array($disp, $base) && ! in_array($disp, $resto)) {
                    $resto[] = $disp;
                }
            }
        }

        return array_merge($base, $resto);
    }

    private function anclaCortefinPvsi(?object $cortefin, ?object $pvsi): ?object
    {
        if ($cortefin === null && $pvsi === null) {
            return null;
        }
        if ($cortefin === null) {
            return $pvsi;
        }
        if ($pvsi === null) {
            return $cortefin;
        }

        $cf = strtotime((string) $cortefin->fecha_modificacion);
        $pv = strtotime((string) $pvsi->fecha_modificacion);

        if ($pv !== false && ($cf === false || $pv > $cf)) {
            return $pvsi;
        }

        return $cortefin;
    }

    private function rutaDeDisparador(array $rutaBasePorDisparador, string $disparador, string $archivo): ?string
    {
        $rutaBase = $rutaBasePorDisparador[$disparador] ?? null;

        if ($rutaBase === null) {
            return null;
        }

        return $rutaBase.'\\'.strtoupper($archivo);
    }

    private function historialDeSucursal(string $shortKey, Computer $computer, array $archivos): array
    {
        $historial = [];

        $query = HashArchivoHistorial::whereRaw('lower(sucursal) = ?', [$shortKey])
            ->where(function ($q) use ($computer) {
                $q->where('ip', $computer->ip_address)
                    ->orWhereIn(DB::raw('lower(disparador)'), self::SIN_FILTRO_IP);
            });

        if (! empty($archivos)) {
            $query->whereIn(DB::raw('lower(archivo)'), array_map(fn ($a) => strtolower($a), $archivos));
        }

        foreach ($query->get(['archivo', 'disparador', 'md5_completo', 'fecha_modificacion', 'fecha_consulta_api']) as $r) {
            $arch = strtolower((string) $r->archivo);
            $disp = strtolower((string) $r->disparador);

            $historial[$arch][$disp][] = [
                'hash' => (string) $r->md5_completo,
                'fecha_consulta_api' => $r->fecha_consulta_api,
                'fecha_modificacion' => $r->fecha_modificacion,
                'instante' => $this->instanteDeFecha($r->fecha_consulta_api),
            ];
        }

        return $historial;
    }

    private function historialDePunto(array $historial, string $archivo, array $disparadores): array
    {
        $archivo = strtolower($archivo);
        $entries = [];

        foreach ($disparadores as $disp) {
            $disp = strtolower($disp);
            foreach ($historial[$archivo][$disp] ?? [] as $entry) {
                $hash = empty($entry['hash']) ? '' : substr($entry['hash'], -5);
                $entries[] = [
                    'hash' => $hash,
                    'md5_completo' => $entry['hash'] ?? '',
                    'fecha_consulta_api' => $entry['fecha_consulta_api'] ?? null,
                    'fecha_modificacion' => $entry['fecha_modificacion'] ?? null,
                    'instante' => $entry['instante'] ?? 0,
                    'disparador' => $disp,
                ];
            }
        }

        usort($entries, fn ($a, $b) => $b['instante'] <=> $a['instante']);

        $unicos = [];
        foreach ($entries as $entry) {
            $clave = (string) ($entry['md5_completo'] ?? '');
            if ($clave === '') {
                $clave = (string) $entry['hash'];
            }
            if (! isset($unicos[$clave])) {
                $unicos[$clave] = $entry;
            }
        }

        return array_slice(array_values($unicos), 0, 5);
    }

    private function instanteDeFecha(?string $fecha): int
    {
        if ($fecha === null || $fecha === '') {
            return 0;
        }

        $ts = strtotime($fecha);

        return $ts === false ? 0 : (int) $ts;
    }
}
