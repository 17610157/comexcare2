<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteArchivosStockController extends Controller
{
    private const DISPARADORES = ['rbf', 'rebsa'];

    private const DISPARADOR_REBSAMEN = 'rebsa';

    private const ARCHIVOS_STOCK = [
        'EYSIPAR.DBF', 'DD_CONTROL.DBF', 'CATPROD3.DBF', 'NOHAY.DBF', 'PEDIDO.DBF',
        'PEDIDO1.DBF', 'PROVPROD.DBF', 'PEDIDO2.DBF', 'DD_DATOS.DBF', 'STOCK.DBF',
        'CAT_PROD.DBF', 'MOVSINV.DBF', 'TABLACON.DBF', 'TABLA010.DBF',
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

        $groups = Group::orderBy('name')->get();

        $archivos = self::ARCHIVOS_STOCK;

        return response()
            ->view('reportes.archivos-stock.index', compact('plazas', 'groups', 'archivos'))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Lista los agentes disponibles para el buscador con checkboxes.
     * Se respeta el filtro de plaza y tipo de grupo ya seleccionados, para no
     * enviar agentes que el reporte descartaría, y limita el resultado para
     * no cargar los cientos de equipos de una sola vez.
     */
    public function agentes(Request $request)
    {
        $query = Computer::query();

        $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
        if (is_array($plazaInput) && count($plazaInput) > 0) {
            $query->whereIn('plaza', $plazaInput);
        }

        $typeInput = $request->query('type') ?? $request->input('type', []);
        if (is_array($typeInput) && count($typeInput) > 0) {
            $query->whereIn('group_id', Group::whereIn('type', $typeInput)->pluck('id'));
        }

        $term = trim((string) ($request->query('q') ?? $request->input('q', '')));
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(nombre_instalacion) LIKE LOWER(?)', [$like])
                    ->orWhereRaw('LOWER(short_key) LIKE LOWER(?)', [$like])
                    ->orWhereRaw('LOWER(ip_address) LIKE LOWER(?)', [$like]);
            });
        }

        $limite = min(max((int) ($request->query('limit') ?? 300), 1), 1000);

        $agentes = $query
            ->orderBy('plaza')
            ->orderBy('nombre_instalacion')
            ->limit($limite)
            ->get(['id', 'nombre_instalacion', 'short_key', 'ip_address', 'plaza']);

        return response()->json([
            'data' => $agentes->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => (string) $c->nombre_instalacion,
                'short_key' => (string) $c->short_key,
                'ip' => (string) $c->ip_address,
                'plaza' => (string) $c->plaza,
            ])->values(),
            'truncado' => $query->toBase()->getCountForPagination() > $limite,
        ])->header('Cache-Control', 'no-store');
    }

    public function data(Request $request)
    {
        $draw = (int) ($request->query('draw') ?? $request->input('draw', 1));
        $startIdx = (int) ($request->query('start') ?? $request->input('start', 0));
        $length = (int) ($request->query('length') ?? $request->input('length', 50));
        $sortColumn = $request->query('sort') ?? 'plaza';
        $sortDirection = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        try {
            [$computers, $archivosFiltro] = $this->aplicarFiltros($request);

            $rows = $this->construirFilas($computers, $archivosFiltro);

            $estadoInput = strtolower(trim((string) ($request->query('estado') ?? $request->input('estado', ''))));
            if (in_array($estadoInput, ['actualizado', 'desactualizado'], true)) {
                $rows = array_values(array_filter($rows, fn ($row) => $row['estado'] === $estadoInput));
            }

            $total = count($rows);
            $stats = $this->calcularEstadisticas($rows);

            $sortMap = [
                'plaza' => fn ($r) => strtolower($r['plaza']),
                'nombre_instalacion' => fn ($r) => strtolower($r['nombre_instalacion']),
                'archivo' => fn ($r) => strtolower($r['archivo']),
                'estado' => fn ($r) => ['actualizado' => 0, 'desactualizado' => 1][$r['estado']] ?? 1,
            ];
            $sortFn = $sortMap[$sortColumn] ?? $sortMap['plaza'];

            usort($rows, function ($a, $b) use ($sortFn, $sortDirection) {
                $cmp = strcmp($sortFn($a), $sortFn($b));
                if ($cmp === 0) {
                    $cmp = strcmp(strtolower($a['archivo']), strtolower($b['archivo']));
                }

                return $sortDirection === 'desc' ? -$cmp : $cmp;
            });

            $pagina = array_slice($rows, $startIdx, $length);

            $this->aplicarPesos($pagina);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $pagina,
                'stock_stats' => $stats,
            ])->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        } catch (\Exception $e) {
            Log::error('ArchivosStock data error: '.$e->getMessage());

            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function aplicarFiltros(Request $request): array
    {
        $query = Computer::with('group');

        $plazaInput = $request->query('plaza') ?? $request->input('plaza', []);
        if (is_array($plazaInput) && count($plazaInput) > 0) {
            $query->whereIn('plaza', $plazaInput);
        }

        $typeInput = $request->query('type') ?? $request->input('type', []);
        if (is_array($typeInput) && count($typeInput) > 0) {
            $query->whereIn('group_id', Group::whereIn('type', $typeInput)->pluck('id'));
        }

        $groupInput = $request->query('group_id') ?? $request->input('group_id', []);
        if (is_array($groupInput) && count($groupInput) > 0) {
            $query->whereIn('group_id', $groupInput);
        }

        $conexionInput = strtolower(trim((string) ($request->query('conexion') ?? $request->input('conexion', ''))));
        if (in_array($conexionInput, ['online', 'offline'], true)) {
            $query->where(function ($q) use ($conexionInput) {
                if ($conexionInput === 'online') {
                    $q->where('last_seen', '>=', now()->subMinutes(5));
                } else {
                    $q->where(function ($q2) {
                        $q2->whereNull('last_seen')->orWhere('last_seen', '<', now()->subMinutes(5));
                    });
                }
            });
        }

        $search = trim((string) ($request->query('search') ?? $request->input('search.value', '')));
        if ($search !== '') {
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(nombre_instalacion) LIKE LOWER(?)', [$term])
                    ->orWhereRaw('LOWER(short_key) LIKE LOWER(?)', [$term])
                    ->orWhereRaw('LOWER(ip_address) LIKE LOWER(?)', [$term]);
            });
        }

        // Agentes marcados en el buscador con checkboxes. Si no hay ninguno, no se filtra.
        $agenteInput = $request->query('agente') ?? $request->input('agente', []);
        $agenteIds = array_values(array_filter(array_map(
            'intval',
            is_array($agenteInput) ? $agenteInput : explode(',', (string) $agenteInput)
        )));
        if (count($agenteIds) > 0) {
            $query->whereIn('id', $agenteIds);
        }

        $archivoInput = $request->query('archivo') ?? $request->input('archivo', []);
        $stock = array_map('strtolower', self::ARCHIVOS_STOCK);
        $archivosFiltro = array_values(array_filter(array_map(
            fn ($a) => strtolower(trim((string) $a)),
            is_array($archivoInput) ? $archivoInput : explode(',', (string) $archivoInput)
        ), fn ($a) => in_array($a, $stock, true)));

        $computers = $query->orderBy('nombre_instalacion')->get();

        return [$computers, $archivosFiltro];
    }

    /**
     * Una fila por (agente, archivo de stock) registrado por RBF o Rebsamen.
     * No se filtra por IP: rbf y rebsa están en SIN_FILTRO_IP del reporte de trazabilidad,
     * por lo que sus registros aplican a todos los equipos de la sucursal.
     */
    private function construirFilas($computers, array $archivosFiltro): array
    {
        $shortKeys = $computers
            ->map(fn ($c) => strtolower(trim((string) $c->short_key)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($shortKeys)) {
            return [];
        }

        $permitidos = empty($archivosFiltro)
            ? array_map('strtolower', self::ARCHIVOS_STOCK)
            : $archivosFiltro;

        $registros = DB::table('conciliacion_hash_archivos')
            ->whereIn(DB::raw('lower(sucursal)'), $shortKeys)
            ->whereIn(DB::raw('lower(disparador)'), self::DISPARADORES)
            ->whereIn(DB::raw('lower(archivo)'), $permitidos)
            ->select(
                DB::raw('lower(sucursal) as sucursal'),
                DB::raw('lower(archivo) as archivo'),
                DB::raw('lower(disparador) as disparador'),
                DB::raw('coalesce(nullif(md5_completo, \'\'), md5) as md5'),
                'fecha_modificacion',
                'fecha_consulta_api'
            )
            ->get();

        // Se queda con el registro más reciente de cada (sucursal, archivo, disparador).
        // Compara por fecha_consulta_api y, en empate, por fecha_modificacion.
        $porPunto = [];
        foreach ($registros as $r) {
            $clave = $r->sucursal.'|'.$r->archivo.'|'.$r->disparador;
            $instante = $this->instanteDeFecha($r->fecha_consulta_api ?: $r->fecha_modificacion);
            if (! isset($porPunto[$clave]) || $instante >= $porPunto[$clave]['instante']) {
                $porPunto[$clave] = [
                    'instante' => $instante,
                    'md5' => strtolower((string) $r->md5),
                    'fecha_modificacion' => $r->fecha_modificacion,
                ];
            }
        }

        $porAgente = [];
        foreach ($porPunto as $clave => $punto) {
            [$sucursal, $archivo, $disparador] = explode('|', $clave);
            $porAgente[$sucursal][$archivo][$disparador] = $punto;
        }

        $filas = [];
        foreach ($computers as $computer) {
            $sucursal = strtolower(trim((string) $computer->short_key));
            $archivosDelAgente = $porAgente[$sucursal] ?? [];
            if (empty($archivosDelAgente)) {
                continue;
            }

            $estadoEquipo = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5
                ? 'online'
                : 'offline';

            foreach ($archivosDelAgente as $archivo => $disparadores) {
                $rbf = $disparadores['rbf'] ?? null;
                $rebsa = $disparadores[self::DISPARADOR_REBSAMEN] ?? null;

                $hashRbf = $rbf['md5'] ?? '';
                $hashRebsa = $rebsa['md5'] ?? '';

                // Si el hash difiere entre RBF y Rebsamen (o falta en alguno), está desactualizado.
                $estado = ($hashRbf !== '' && $hashRebsa !== '' && $hashRbf === $hashRebsa)
                    ? 'actualizado'
                    : 'desactualizado';

                $filas[] = [
                    'id' => $computer->id,
                    'plaza' => $computer->plaza ?? 'N/A',
                    'nombre_instalacion' => $computer->nombre_instalacion,
                    'short_key' => $computer->short_key,
                    'estado_equipo' => $estadoEquipo,
                    'archivo' => strtoupper($archivo),
                    'rbf' => [
                        'archivo' => $rbf ? strtoupper($archivo) : null,
                        'hash' => $hashRbf,
                        'hash_corto' => $hashRbf !== '' ? substr($hashRbf, -5) : null,
                        'fecha_modificacion' => $rbf['fecha_modificacion'] ?? null,
                        'peso' => null,
                    ],
                    'rebsamen' => [
                        'archivo' => $rebsa ? strtoupper($archivo) : null,
                        'hash' => $hashRebsa,
                        'hash_corto' => $hashRebsa !== '' ? substr($hashRebsa, -5) : null,
                        'fecha_modificacion' => $rebsa['fecha_modificacion'] ?? null,
                        'peso' => null,
                    ],
                    'estado' => $estado,
                ];
            }
        }

        return $filas;
    }

    /**
     * El peso de cada archivo solo existe en el payload del lote con el que el servidor
     * RBF/Rebsamen reportó sus archivos. Se resuelve con DISTINCT ON para quedarnos con
     * el último lote de cada (sucursal, disparador) y se acota a las sucursales visibles
     * en la página: parsear los ~124 mil lotes completos del histórico es inviable.
     */
    private function aplicarPesos(array &$filas): void
    {
        if (empty($filas)) {
            return;
        }

        $sucursales = array_values(array_unique(array_filter(array_map(
            fn ($row) => strtolower(trim((string) $row['short_key'])),
            $filas
        ))));

        if (empty($sucursales)) {
            return;
        }

        $pesos = $this->consultarPesos($sucursales);

        foreach ($filas as $i => $fila) {
            $sucursal = strtolower(trim((string) $fila['short_key']));
            $archivo = strtolower($fila['archivo']);

            $pesoRbf = $pesos['rbf'][$sucursal][$archivo] ?? null;
            $pesoRebsa = $pesos[self::DISPARADOR_REBSAMEN][$sucursal][$archivo] ?? null;

            $filas[$i]['rbf']['peso'] = $pesoRbf;
            $filas[$i]['rebsamen']['peso'] = $pesoRebsa;
        }
    }

    private function consultarPesos(array $sucursales): array
    {
        $archivos = array_map('strtolower', self::ARCHIVOS_STOCK);

        if (DB::connection()->getDriverName() === 'pgsql') {
            return $this->consultarPesosPgsql($sucursales, $archivos);
        }

        return $this->consultarPesosGenerico($sucursales, $archivos);
    }

    private function consultarPesosPgsql(array $sucursales, array $archivos): array
    {
        // MATERIALIZED es lo que hace viable la consulta: primero resuelve los ids del
        // último lote por (sucursal, disparador) usando el índice de lower(sucursal) y
        // solo después convierte a jsonb los ~50 payloadsGanadores, en lugar de los miles
        // que leería el planner si el CTE seFusionara.
        $sql = "WITH ids AS MATERIALIZED (
                    SELECT DISTINCT ON (lower(l.sucursal), lower(l.disparador))
                           lower(l.sucursal) AS s,
                           lower(l.disparador) AS d,
                           l.id
                    FROM hash_archivos_lotes l
                    WHERE lower(l.disparador) IN ('rbf', 'rebsa')
                      AND l.payload IS NOT NULL
                      AND l.payload <> ''
                      AND lower(l.sucursal) = ANY (?)
                    ORDER BY lower(l.sucursal), lower(l.disparador), l.id DESC
                ), latest AS (
                    SELECT ids.s, ids.d, l.payload::jsonb AS j
                    FROM ids
                    JOIN hash_archivos_lotes l ON l.id = ids.id
                )
                SELECT l.s,
                       l.d,
                       lower(a.elem->>'Nombre') AS a,
                       CASE WHEN (a.elem->>'Peso') ~ '^[0-9]+$' THEN (a.elem->>'Peso')::bigint END AS peso
                FROM latest l
                CROSS JOIN jsonb_array_elements(l.j->'Tiendas') AS t(elem)
                CROSS JOIN jsonb_array_elements(t.elem->'Archivos') AS a(elem)
                WHERE lower(coalesce(t.elem->>'Sucursal', l.s)) = l.s
                  AND lower(a.elem->>'Nombre') = ANY (?)";

        try {
            $registros = DB::select($sql, [
                $this->arrayPgsql($sucursales),
                $this->arrayPgsql($archivos),
            ]);
        } catch (\Exception $e) {
            Log::warning('ArchivosStock pesos pgsql: '.$e->getMessage());

            return [];
        }

        return $this->mapearPesos($registros);
    }

    private function consultarPesosGenerico(array $sucursales, array $archivos): array
    {
        $permitidos = array_flip($archivos);

        $lotes = DB::table('hash_archivos_lotes')
            ->whereIn(DB::raw('lower(disparador)'), self::DISPARADORES)
            ->whereNotNull('payload')
            ->where('payload', '!=', '')
            ->whereIn(DB::raw('lower(sucursal)'), $sucursales)
            ->orderByDesc('id')
            ->get(['sucursal', 'disparador', 'payload']);

        $registros = [];
        foreach ($lotes as $lote) {
            $sucursal = strtolower(trim((string) $lote->sucursal));
            $disparador = strtolower(trim((string) $lote->disparador));

            $payload = is_string($lote->payload) ? json_decode($lote->payload, true) : $lote->payload;
            if (! is_array($payload)) {
                continue;
            }

            foreach (($payload['Tiendas'] ?? []) as $tienda) {
                $sucursalTienda = strtolower(trim((string) ($tienda['Sucursal'] ?? $sucursal)));
                if ($sucursalTienda !== $sucursal) {
                    continue;
                }

                foreach (($tienda['Archivos'] ?? []) as $archivo) {
                    $nombre = strtolower(trim((string) ($archivo['Nombre'] ?? '')));
                    if ($nombre === '' || ! isset($permitidos[$nombre])) {
                        continue;
                    }

                    $registros[] = (object) [
                        's' => $sucursal,
                        'd' => $disparador,
                        'a' => $nombre,
                        'peso' => isset($archivo['Peso']) ? (int) $archivo['Peso'] : null,
                    ];
                }
            }
        }

        return $this->mapearPesos($registros);
    }

    private function mapearPesos(array $registros): array
    {
        $pesos = [];
        foreach ($registros as $r) {
            if ($r->peso === null) {
                continue;
            }
            $pesos[$r->d][$r->s][$r->a] = round(((int) $r->peso) / 1024, 1);
        }

        return $pesos;
    }

    private function arrayPgsql(array $values): string
    {
        return '{'.implode(',', array_map(fn ($v) => '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $v).'"', $values)).'}';
    }

    private function calcularEstadisticas(array $filas): array
    {
        $total = count($filas);
        $actualizados = 0;
        $porPlaza = [];

        foreach ($filas as $fila) {
            if ($fila['estado'] === 'actualizado') {
                $actualizados++;
            }

            $plaza = $fila['plaza'];
            if (! isset($porPlaza[$plaza])) {
                $porPlaza[$plaza] = ['plaza' => $plaza, 'total' => 0, 'matched' => 0];
            }
            $porPlaza[$plaza]['total']++;
            if ($fila['estado'] === 'actualizado') {
                $porPlaza[$plaza]['matched']++;
            }
        }

        $desactualizados = $total - $actualizados;

        $perPlaza = array_map(function ($stats) {
            $desactualizadosPlaza = $stats['total'] - $stats['matched'];

            return [
                'plaza' => $stats['plaza'],
                'total' => $stats['total'],
                'matched' => $stats['matched'],
                'unmatched' => $desactualizadosPlaza,
                'percent' => $stats['total'] > 0 ? round(($stats['matched'] / $stats['total']) * 100, 1) : 0,
            ];
        }, $porPlaza);

        usort($perPlaza, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'total_archivos' => $total,
            'total_matched' => $actualizados,
            'total_unmatched' => $desactualizados,
            'percent' => $total > 0 ? round(($actualizados / $total) * 100, 1) : 0,
            'per_plaza' => $perPlaza,
        ];
    }

    public function export(Request $request)
    {
        try {
            [$computers, $archivosFiltro] = $this->aplicarFiltros($request);

            $filas = $this->construirFilas($computers, $archivosFiltro);

            $estadoInput = strtolower(trim((string) ($request->query('estado') ?? $request->input('estado', ''))));
            if (in_array($estadoInput, ['actualizado', 'desactualizado'], true)) {
                $filas = array_values(array_filter($filas, fn ($row) => $row['estado'] === $estadoInput));
            }

            usort($filas, function ($a, $b) {
                $cmp = strcmp(strtolower($a['plaza']), strtolower($b['plaza']));
                if ($cmp === 0) {
                    $cmp = strcmp(strtolower($a['nombre_instalacion']), strtolower($b['nombre_instalacion']));
                }

                return $cmp !== 0 ? $cmp : strcmp(strtolower($a['archivo']), strtolower($b['archivo']));
            });

            $this->aplicarPesos($filas);

            $filename = 'Reporte_Archivos_Stock_'.date('Ymd_His');

            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'.csv"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ];

            $callback = function () use ($filas) {
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($output, [
                    'Plaza', 'Agente', 'Estado Equipo',
                    'Archivo RBF', 'Hash RBF', 'Fecha Mod RBF', 'Peso RBF (KB)',
                    'Archivo Rebsamen', 'Hash Rebsamen', 'Fecha Mod Rebsamen', 'Peso Rebsamen (KB)',
                    'Estado',
                ], ';');

                foreach ($filas as $fila) {
                    fputcsv($output, [
                        $fila['plaza'],
                        $fila['nombre_instalacion'],
                        $fila['estado_equipo'],
                        $fila['rbf']['archivo'] ?? '',
                        $fila['rbf']['hash'] ?? '',
                        $this->formatearFecha($fila['rbf']['fecha_modificacion'] ?? null),
                        $fila['rbf']['peso'] ?? '',
                        $fila['rebsamen']['archivo'] ?? '',
                        $fila['rebsamen']['hash'] ?? '',
                        $this->formatearFecha($fila['rebsamen']['fecha_modificacion'] ?? null),
                        $fila['rebsamen']['peso'] ?? '',
                        $fila['estado'] === 'actualizado' ? 'Actualizado' : 'Desactualizado',
                    ], ';');
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            Log::error('ArchivosStock export error: '.$e->getMessage());

            return redirect()->route('reportes.archivos-stock')
                ->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }

    private function formatearFecha(?string $fecha): string
    {
        $fecha = trim((string) $fecha);
        if ($fecha === '') {
            return '';
        }

        $instante = $this->instanteDeFecha($fecha);

        return $instante > 0 ? date('Y-m-d H:i:s', $instante) : $fecha;
    }

    private function instanteDeFecha(?string $fecha): int
    {
        if ($fecha === null || $fecha === '') {
            return 0;
        }

        $timestamp = strtotime($fecha);

        return $timestamp === false ? 0 : (int) $timestamp;
    }
}
