<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExtraeErrorComando;
use App\Models\Command;
use App\Models\Computer;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReporteArchivosStockController extends Controller
{
    use ExtraeErrorComando;

    private const DISPARADORES = ['rbf', 'rebsa'];

    /**
     * Batch que sincroniza los archivos de stock del agente.
     */
    private const BAT_STOCK = 'DASTOCK.BAT';

    /**
     * Ventana de espera por equipo. Un equipo que ya recibio DASTOCK.BAT no vuelve a
     * recibirlo hasta que pasen estos minutos, por mucho que siga en amarillo o rojo.
     */
    private const COOLDOWN_MINUTOS = 5;

    private const DISPARADOR_REBSAMEN = 'rebsa';

    /**
     * Dias transcurridos desde la ultima modificacion del archivo. Se toma la fecha mas
     * antigua entre RBF y Rebsamen: si una de las dos fuentes arrastra el archivo sin
     * tocar desde hace semanas, esa es la antiguedad que interesa para priorizar.
     */
    private function calcularDias(?string $fechaRbf, ?string $fechaRebsa): ?int
    {
        $instantes = array_values(array_filter([
            $this->instanteDeFecha($fechaRbf),
            $this->instanteDeFecha($fechaRebsa),
        ]));

        if (empty($instantes)) {
            return null;
        }

        return (int) floor((now()->getTimestamp() - min($instantes)) / 86400);
    }

    /**
     * Aplica el rango de dias sobre las filas ya construidas. Sin rango activo no se
     * filtra nada; con rango activo las filas sin fecha conocida quedan fuera porque
     * no se puede afirmar que esten dentro del rango.
     */
    private function filtrarPorDias(array $filas, Request $request): array
    {
        $min = $this->intDelRequest($request, 'dias_min');
        $max = $this->intDelRequest($request, 'dias_max');

        if ($min === null && $max === null) {
            return $filas;
        }

        return array_values(array_filter($filas, function ($fila) use ($min, $max) {
            // Los estados que no participan (consulta / no aplica) no tienen una
            // antigüedad que comparar, así que quedan fuera del rango.
            if (in_array($fila['estado'], ['no_cuenta', 'no_aplica'], true)) {
                return false;
            }

            // Sin fecha conocida: solo se puede afirmar que está dentro del rango si
            // no hay mínimo (con un mínimo no se puede garantizar que lo supere).
            if ($fila['dias'] === null) {
                return $min === null;
            }

            if ($min !== null && $fila['dias'] < $min) {
                return false;
            }

            return ! ($max !== null && $fila['dias'] > $max);
        }));
    }

    /**
     * El filtro de archivo es de presentacion: quita filas de la tabla sin tocar el
     * porcentaje de instalaciones, que se calcula con los 14 archivos siempre.
     */
    private function filtrarPorArchivo(array $filas, array $archivosFiltro): array
    {
        if (empty($archivosFiltro)) {
            return $filas;
        }

        return array_values(array_filter(
            $filas,
            fn ($fila) => in_array(strtolower($fila['archivo']), $archivosFiltro, true)
        ));
    }

    private function intDelRequest(Request $request, string $clave): ?int
    {
        $valor = $request->query($clave) ?? $request->input($clave);

        if ($valor === null || is_array($valor) || trim((string) $valor) === '') {
            return null;
        }

        return max(0, (int) $valor);
    }

    /**
     * Archivos que se miden por fecha y sus dos umbrales [verde, amarillo]. Son los
     * unicos que deciden si una instalacion esta lista: hasta el primer valor verde,
     * hasta el segundo amarillo y mas alla rojo (o rojo de entrada si no existe).
     * PEDIDO y STOCK no se actualizan diario, por eso el rango de PEDIDO es mas amplio.
     */
    private const ARCHIVOS_CON_FECHA = [
        'CAT_PROD.DBF' => [1, 3],
        'DD_CONTROL.DBF' => [1, 3],
        'DD_DATOS.DBF' => [1, 3],
        'MOVSINV.DBF' => [1, 3],
        'PEDIDO.DBF' => [3, 7],
    ];

    /**
     * Solo se comprueba que existan. Si faltan aparecen como problema en el detalle,
     * pero no entran en el porcentaje de instalaciones listas.
     */
    private const ARCHIVOS_EXISTENCIA = [
        'STOCK.DBF', 'CATPROD3.DBF', 'NOHAY.DBF', 'PEDIDO1.DBF', 'PEDIDO2.DBF',
    ];

    /** Solo consulta: no cuenta ni como problema ni en el porcentaje. */
    private const ARCHIVOS_CONSULTA = ['TABLA010.DBF'];

    /** Lo genera la tienda; los almacenes no lo tienen. */
    private const ARCHIVO_SIN_ALMACENES = 'EYSIPAR.DBF';

    /** Basta con que exista uno de los dos para dar el punto por cubierto. */
    private const PAR_ALTERNATIVO = ['PROVPROD.DBF', 'TABLACON.DBF'];

    /** Solo estos tipos de grupo forman parte del denominador del porcentaje. */
    private const TIPOS_CONTEMPLADOS = ['tienda', 'almacen'];

    private const ARCHIVOS_STOCK = [
        'EYSIPAR.DBF', 'DD_CONTROL.DBF', 'CATPROD3.DBF', 'NOHAY.DBF', 'PEDIDO.DBF',
        'PEDIDO1.DBF', 'PROVPROD.DBF', 'PEDIDO2.DBF', 'DD_DATOS.DBF', 'STOCK.DBF',
        'CAT_PROD.DBF', 'MOVSINV.DBF', 'TABLACON.DBF', 'TABLA010.DBF',
    ];

    private const ESTADOS = ['verde', 'amarillo', 'rojo', 'no_aplica', 'no_cuenta'];

    /** Dias de respaldo que se leen para el historial de pesos (7 previos + el actual). */
    private const HISTORIAL_PESO_DIAS = 9;

    /** Por debajo de este peso una variacion es ruido de redimensionado, no anomalia. */
    private const PESO_MINIMO_KB = 100.0;

    /** Variacion minima en KB: por debajo no se senala, aunque sea un porcentaje grande. */
    private const PESO_DELTA_MINIMO_KB = 256.0;

    /** Variacion que dispara la anomalia y la que la vuelve critica, en ambas direcciones. */
    private const PESO_VARIACION = 0.20;

    private const PESO_VARIACION_CRITICA = 0.40;

    /** Dias de la mediana contra la que se confirma que el cambio es real. */
    private const PESO_VENTANA_DIAS = 7;

    /** Dias seguidos con el mismo peso para senalar que el archivo no avanza. */
    private const PESO_SIN_CAMBIOS_DIAS = 5;

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

            $rows = $this->construirFilas($computers);

            $this->aplicarPesos($rows);
            $this->aplicarAnomaliasDePeso($rows);

            // El porcentaje se calcula antes de filtrar la tabla: mide instalaciones,
            // no archivos, asi que un filtro de archivo o de estado no lo cambia.
            $stats = $this->evaluarInstalaciones($rows, $computers);

            $rows = $this->filtrarPorArchivo($rows, $archivosFiltro);

            $estadoInput = strtolower(trim((string) ($request->query('estado') ?? $request->input('estado', ''))));
            if (in_array($estadoInput, self::ESTADOS, true)) {
                $rows = array_values(array_filter($rows, fn ($row) => $row['estado'] === $estadoInput));
            }

            $rows = $this->filtrarPorDias($rows, $request);

            $total = count($rows);

            $ordenEstado = ['verde' => 0, 'amarillo' => 1, 'rojo' => 2, 'no_aplica' => 3, 'no_cuenta' => 4];
            $sortMap = [
                'plaza' => fn ($r) => strtolower($r['plaza']),
                'nombre_instalacion' => fn ($r) => strtolower($r['nombre_instalacion']),
                'archivo' => fn ($r) => strtolower($r['archivo']),
                'estado' => fn ($r) => $ordenEstado[$r['estado']] ?? 9,
                'dias' => fn ($r) => $r['dias'] ?? -1,
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

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $pagina,
                'instalaciones_stats' => $stats,
            ], 200, [], JSON_PRESERVE_ZERO_FRACTION)->header('Cache-Control', 'no-cache, no-store, must-revalidate');
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

    /**
     * Encola DASTOCK.BAT para los equipos seleccionados que tengan al menos un archivo
     * en amarillo o en rojo, que es cuando todavia hay algo que regenerar. Se manda un
     * solo comando por equipo aunque tenga varios archivos pendientes, y un equipo no
     * vuelve a recibir el BAT hasta que pasen los minutos de espera.
     */
    public function ejecutar(Request $request)
    {
        $computerIds = array_values(array_filter(array_map(
            'intval',
            (array) $request->input('computer_ids', [])
        )));

        if (empty($computerIds)) {
            return response()->json(['success' => false, 'message' => 'Selecciona al menos un equipo.'], 400);
        }

        try {
            $computers = Computer::with('group')->whereIn('id', $computerIds)->orderBy('nombre_instalacion')->get();

            // Se reconstruyen las filas de los equipos elegidos con el mismo criterio del
            // reporte para no enviar el BAT a quien ya esta todo en verde.
            $filas = $this->construirFilas($computers);

            // Clave por equipo, no por archivo: un equipo entra una sola vez
            // aunque tenga cinco archivos en amarillo o rojo.
            $pendientesPorEquipo = [];
            foreach ($filas as $fila) {
                // Solo los archivos con fecha deciden si hay algo que regenerar; los de
                // solo existencia se muestran pero no disparan el BAT.
                if ($fila['tipo_archivo'] === 'fecha'
                    && ($fila['estado'] === 'amarillo' || $fila['estado'] === 'rojo')) {
                    $pendientesPorEquipo[$fila['id']][] = $fila['archivo'];
                }
            }

            $isPreview = $request->boolean('preview', false);
            $resumen = $this->candidatosParaStock($computers, $pendientesPorEquipo);
            $candidatos = $resumen['candidatos'];

            if ($isPreview) {
                return response()->json([
                    'success' => true,
                    'computers' => array_values($candidatos),
                    'count' => count($candidatos),
                    'bat' => self::BAT_STOCK,
                ]);
            }

            // La espera se revisa otra vez aqui: entre la vista previa y la confirmacion
            // el mismo equipo pudo recibir ya el BAT desde otra pestana u operador.
            $idsCandidatos = array_map(fn ($c) => $c['id'], array_values($candidatos));
            $finales = array_diff($idsCandidatos, $this->equiposEnCooldown($idsCandidatos));

            $commands = [];
            foreach ($candidatos as $candidato) {
                if (! in_array($candidato['id'], $finales, true)) {
                    continue;
                }

                $commands[] = [
                    'computer_id' => $candidato['id'],
                    'type' => 'execute',
                    'data' => json_encode([
                        'command' => self::BAT_STOCK,
                        'command_args' => '',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($commands)) {
                Command::insert($commands);
                Log::info('ejecutar stock: created '.count($commands).' commands for '.self::BAT_STOCK);
            }

            return response()->json([
                'success' => true,
                'count' => count($commands),
                'computer_ids' => array_values($finales),
                'computers' => array_values($candidatos),
                'en_espera' => $resumen['en_espera'] + (count($idsCandidatos) - count($finales)),
                'bat' => self::BAT_STOCK,
            ]);
        } catch (\Exception $e) {
            Log::error('ArchivosStock ejecutar error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Traduce el mapa de archivos en amarillo o rojo por equipo a la lista de equipos a
     * los que se puede enviar el BAT, descartando los que siguen dentro de la ventana de
     * espera. La clave del mapa es el id del equipo. Devuelve tambien cuantos quedaron
     * en espera para poder avisarlo al operador.
     */
    private function candidatosParaStock($computers, array $pendientesPorEquipo): array
    {
        if (empty($pendientesPorEquipo)) {
            return ['candidatos' => [], 'en_espera' => 0];
        }

        $enEspera = $this->equiposEnCooldown(array_map('intval', array_keys($pendientesPorEquipo)));

        $candidatos = [];
        $omitidos = 0;

        foreach ($computers as $computer) {
            $id = (int) $computer->id;

            if (! isset($pendientesPorEquipo[$id])) {
                continue;
            }

            if (in_array($id, $enEspera, true)) {
                $omitidos++;

                continue;
            }

            $candidatos[$id] = [
                'id' => $id,
                'nombre_instalacion' => $computer->nombre_instalacion,
                'plaza' => $computer->plaza ?? 'N/A',
                'archivos' => count($pendientesPorEquipo[$id]),
            ];
        }

        return ['candidatos' => $candidatos, 'en_espera' => $omitidos];
    }

    /**
     * Equipos que ya recibieron DASTOCK.BAT dentro de la ventana de espera. Se consulta
     * por comando y no por ejecuciones del reporte porque el BAT puede lanzarse desde
     * otras pantallas y el control debe ser el mismo.
     */
    private function equiposEnCooldown(array $computerIds): array
    {
        $computerIds = array_values(array_filter(array_map('intval', $computerIds)));

        if (empty($computerIds)) {
            return [];
        }

        return Command::where('type', 'execute')
            ->where('data->command', self::BAT_STOCK)
            ->whereIn('computer_id', $computerIds)
            ->where('created_at', '>=', now()->subMinutes(self::COOLDOWN_MINUTOS))
            ->pluck('computer_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Bitacora de las ejecuciones de DASTOCK.BAT, agrupadas por el instante en que se
     * encolaron para poder ver de un vistazo todos los equipos enviados en una tanda.
     */
    public function bitacora(Request $request)
    {
        $limit = min((int) $request->query('limit', 50), 200);

        $commands = Command::where('type', 'execute')
            ->where('data->command', self::BAT_STOCK)
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get(['id', 'computer_id', 'data', 'status', 'response', 'created_at']);

        $groups = [];
        foreach ($commands as $cmd) {
            $computer = Computer::find($cmd->computer_id);

            $groups[$cmd->created_at->format('Y-m-d H:i:s')][] = [
                'id' => $cmd->id,
                'computer' => $computer?->nombre_instalacion ?? 'N/A',
                'plaza' => $computer?->plaza ?? 'N/A',
                'bat' => self::BAT_STOCK,
                'label' => 'STOCK',
                'status' => $cmd->status,
                'error' => $this->extraeErrorComando($cmd->response, $cmd->status),
            ];
        }

        $result = [];
        foreach ($groups as $ts => $items) {
            $result[] = [
                'created_at' => $ts,
                'total' => count($items),
                'counts' => collect($items)->groupBy('status')->map->count(),
                'items' => $items,
            ];
        }

        return response()->json([
            'success' => true,
            'groups' => array_slice($result, 0, $limit),
        ]);
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
     * Una fila por (agente, archivo de stock), para los 14 archivos aunque el equipo no
     * tenga ningun registro: un archivo ausente se muestra en rojo y no se omite.
     * No se filtra por IP: rbf y rebsa están en SIN_FILTRO_IP del reporte de trazabilidad,
     * por lo que sus registros aplican a todos los equipos de la sucursal.
     */
    private function construirFilas($computers): array
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

        $permitidos = array_map('strtolower', self::ARCHIVOS_STOCK);

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

        // Se queda con el registro MÁS ANTIGUO de cada (sucursal, archivo, disparador):
        // el reporte mide antigüedad, así que si una fuente arrastra el archivo sin
        // actualizar desde hace días, esa es la fecha que interesa priorizar.
        $porPunto = [];
        foreach ($registros as $r) {
            $clave = $r->sucursal.'|'.$r->archivo.'|'.$r->disparador;
            $instante = $this->instanteDeFecha($r->fecha_consulta_api ?: $r->fecha_modificacion);
            if (! isset($porPunto[$clave]) || $instante <= $porPunto[$clave]['instante']) {
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

        // La existencia no depende de quien reporta: PROVPROD, por ejemplo, solo aparece
        // en qbck/cortefin y jamas en rbf ni rebsa.
        $existencias = $this->consultarExistencia($shortKeys, $permitidos);

        // Sucursales que alguna vez reportaron una carpeta (lote). Sirve para no marcar
        // como rojo un archivo de solo existencia que simplemente no venia en el lote.
        $carpetas = $this->sucursalesConCarpeta($shortKeys);

        $filas = [];
        foreach ($computers as $computer) {
            $sucursal = strtolower(trim((string) $computer->short_key));
            if ($sucursal === '') {
                continue;
            }

            $tipo = $this->tipoGrupo($computer);
            $esAlmacen = $tipo === 'almacen';
            $estadoEquipo = $computer->last_seen && $computer->last_seen->diffInMinutes(now()) <= 5
                ? 'online'
                : 'offline';
            $delAgente = $porAgente[$sucursal] ?? [];
            $hayCarpeta = ! empty($carpetas[$sucursal]);

            foreach ($permitidos as $archivoLower) {
                $archivo = strtoupper($archivoLower);
                $disparadores = $delAgente[$archivoLower] ?? [];
                $rbf = $disparadores['rbf'] ?? null;
                $rebsa = $disparadores[self::DISPARADOR_REBSAMEN] ?? null;

                $hashRbf = $rbf['md5'] ?? '';
                $hashRebsa = $rebsa['md5'] ?? '';
                $existe = $this->existeArchivo($archivo, $existencias[$sucursal] ?? []);
                $dias = $this->calcularDias($rbf['fecha_modificacion'] ?? null, $rebsa['fecha_modificacion'] ?? null);

                $filas[] = [
                    'id' => $computer->id,
                    'plaza' => $computer->plaza ?? 'N/A',
                    'nombre_instalacion' => $computer->nombre_instalacion,
                    'short_key' => $computer->short_key,
                    'estado_equipo' => $estadoEquipo,
                    'archivo' => $archivo,
                    'tipo_archivo' => $this->tipoDeArchivo($archivo),
                    'existe' => $existe,
                    'es_almacen' => $esAlmacen,
                    'hay_carpeta' => $hayCarpeta,
                    'rbf' => [
                        'archivo' => $rbf ? $archivo : null,
                        'hash' => $hashRbf,
                        'hash_corto' => $hashRbf !== '' ? substr($hashRbf, -5) : null,
                        'fecha_modificacion' => $rbf['fecha_modificacion'] ?? null,
                        'peso' => null,
                    ],
                    'rebsamen' => [
                        'archivo' => $rebsa ? $archivo : null,
                        'hash' => $hashRebsa,
                        'hash_corto' => $hashRebsa !== '' ? substr($hashRebsa, -5) : null,
                        'fecha_modificacion' => $rebsa['fecha_modificacion'] ?? null,
                        'peso' => null,
                    ],
                    'estado' => $this->calcularSemaforo($archivo, $dias, $existe, $esAlmacen, $hayCarpeta),
                    'dias' => $dias,
                    'peso_senal' => null,
                ];
            }
        }

        return $filas;
    }

    private function tipoDeArchivo(string $archivo): string
    {
        if (in_array($archivo, self::ARCHIVOS_CONSULTA, true)) {
            return 'consulta';
        }

        return isset(self::ARCHIVOS_CON_FECHA[$archivo]) ? 'fecha' : 'existencia';
    }

    private function tipoGrupo($computer): ?string
    {
        $type = $computer->group->type ?? null;

        return $type === null ? null : strtolower(trim((string) $type));
    }

    /**
     * Pares (sucursal, archivo) con algun registro, de cualquier disparador. Es lo que
     * decide si el archivo existe: el hash y la fecha siguen viniendo solo de RBF y
     * Rebsamen, pero para saber si esta o no en la carpeta basta con que lo haya visto
     * alguien.
     */
    private function consultarExistencia(array $shortKeys, array $archivos): array
    {
        $registros = DB::table('conciliacion_hash_archivos')
            ->whereIn(DB::raw('lower(sucursal)'), $shortKeys)
            ->whereIn(DB::raw('lower(archivo)'), $archivos)
            ->select(DB::raw('lower(sucursal) as sucursal'), DB::raw('lower(archivo) as archivo'))
            ->distinct()
            ->get();

        $map = [];
        foreach ($registros as $r) {
            $map[$r->sucursal][$r->archivo] = true;
        }

        return $map;
    }

    /**
     * Sucursales cuya carpeta ha reportado algun lote. Sustituye a "la carpeta existe
     * en W:", de la que el sistema no tiene ningun registro: si alguna vez envio un
     * lote, la carpeta estuvo ahi.
     */
    private function sucursalesConCarpeta(array $shortKeys): array
    {
        if (empty($shortKeys)) {
            return [];
        }

        $registros = DB::table('hash_archivos_lotes')
            ->whereIn(DB::raw('lower(sucursal)'), $shortKeys)
            ->select(DB::raw('lower(sucursal) as sucursal'))
            ->distinct()
            ->get();

        $map = [];
        foreach ($registros as $r) {
            $map[$r->sucursal] = true;
        }

        return $map;
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
            $filas[$i]['rbf']['peso_texto'] = $this->formatearPeso($pesoRbf);
            $filas[$i]['rebsamen']['peso_texto'] = $this->formatearPeso($pesoRebsa);
        }
    }

    /**
     * Compara el peso de hoy contra el ultimo respaldo de la misma tienda y contra la
     * mediana de la semana anterior. El primer dia de uso solo deja linea base: la
     * comparacion empieza cuando hay dos respaldos. Solo los archivos con fecha pueden
     * bloquear la instalacion; el resto de senales se muestran pero no cuentan.
     */
    private function aplicarAnomaliasDePeso(array &$filas): void
    {
        if (empty($filas)) {
            return;
        }

        $senales = $this->senalesDePeso();

        foreach ($filas as $i => $fila) {
            // Si la fila no participa en el calculo (TABLA010 o EYSIPAR fuera de
            // almacenes), tampoco tiene sentido senalar su peso.
            if ($fila['estado'] === 'no_cuenta' || $fila['estado'] === 'no_aplica') {
                $filas[$i]['peso_senal'] = null;

                continue;
            }

            $senal = $senales[strtolower(trim((string) $fila['short_key']))][strtolower($fila['archivo'])] ?? null;

            if ($senal !== null && ! isset(self::ARCHIVOS_CON_FECHA[$fila['archivo']])) {
                $senal['bloquea'] = false;
            }

            // "Sin cambios" solo cuenta si el archivo sigue actualizandose: es que su
            // fecha de modificacion ya no es reciente, lo que pasa es que nadie lo toca,
            // que es cosa distinta de lo que la regla quiere avisar.
            if ($senal !== null
                && $senal['tipo'] === 'sin_cambios'
                && ($fila['dias'] === null || $fila['dias'] >= self::PESO_SIN_CAMBIOS_DIAS)) {
                $senal = null;
            }

            $filas[$i]['peso_senal'] = $senal;
        }
    }

    /**
     * Senal de peso de cada (sucursal, archivo), sin depender de los filtros del
     * reporte. Abrir los respaldos de los ultimos dias obliga a descomponer miles de
     * payloads jsonb, cosa que cuesta mas de un segundo, y el resultado solo cambia
     * cuando entran lotes nuevos: por eso se guarda en cache durante una hora para que
     * refrescar la tabla o mover un filtro no vuelva a pagarlo.
     */
    private function senalesDePeso(): array
    {
        $sucursales = Computer::pluck('short_key')
            ->filter()
            ->map(fn ($s) => strtolower(trim((string) $s)))
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($sucursales)) {
            return [];
        }

        $clave = 'reporte.archivos-stock.senales-peso.'.md5(implode('|', $sucursales)).'.'.date('Y-m-d-H');

        return Cache::remember($clave, now()->addHour(), function () use ($sucursales) {
            $historial = $this->consultarHistorialPesos($sucursales);

            $senales = [];

            foreach ($historial as $sucursal => $porArchivo) {
                $diasReportados = array_keys($porArchivo['_dias'] ?? []);
                sort($diasReportados);

                foreach ($porArchivo as $archivo => $serie) {
                    if ($archivo === '_dias') {
                        continue;
                    }

                    $senal = $this->evaluarSeriePeso($serie, $diasReportados);
                    if ($senal !== null) {
                        $senales[$sucursal][$archivo] = $senal;
                    }
                }
            }

            return $senales;
        });
    }

    /**
     * Devuelve la senal que aplica al ultimo respaldo: desaparecido, nuevo, anomalia,
     * critica, sin cambios o nada. 'bloquea' es lo que deja la instalacion pendiente.
     */
    private function evaluarSeriePeso(array $serie, array $diasReportados): ?array
    {
        if (empty($serie)) {
            return null;
        }

        $ultimo = $serie[count($serie) - 1];
        $anterior = count($serie) >= 2 ? $serie[count($serie) - 2] : null;
        $ultimoReporte = end($diasReportados) ?: null;
        $reportePrevio = count($diasReportados) >= 2 ? $diasReportados[count($diasReportados) - 2] : null;

        // Existia en un respaldo anterior y en el de hoy ya no esta.
        if ($ultimoReporte !== null && $ultimo['dia'] < $ultimoReporte) {
            return [
                'tipo' => 'desaparecido',
                'etiqueta' => 'desaparecido',
                'detalle' => 'Existia en el respaldo del '.$anterior['dia'].' y no esta en el del '.$ultimoReporte.'.',
                'bloquea' => true,
            ];
        }

        // Aparece ahora y en el respaldo previo no estaba: no es un error, solo se anota.
        if ($reportePrevio !== null && ($anterior === null || $anterior['dia'] < $reportePrevio)) {
            return [
                'tipo' => 'nuevo',
                'etiqueta' => 'peso nuevo',
                'detalle' => 'No estaba en el respaldo del '.$reportePrevio.'.',
                'bloquea' => false,
            ];
        }

        // Solo la linea base: todavia no hay con que comparar.
        if ($anterior === null) {
            return null;
        }

        $variacion = $this->variacionDePeso($anterior['peso'], $ultimo['peso']);

        // La mediana se calcula con los dias anteriores al actual: si el archivo vuelve
        // a su peso normal tras un dia raro, la mediana lo confirma y no se senala.
        $ventana = array_slice($serie, max(0, count($serie) - 1 - self::PESO_VENTANA_DIAS), min(self::PESO_VENTANA_DIAS, count($serie) - 1));
        $mediana = $this->mediana(array_map(fn ($p) => $p['peso'], $ventana));
        $vsMediana = $this->variacionDePeso($mediana, $ultimo['peso']);

        $cumplePeso = $ultimo['peso'] >= self::PESO_MINIMO_KB;
        $cumpleDelta = abs($ultimo['peso'] - $anterior['peso']) >= self::PESO_DELTA_MINIMO_KB;
        $cumpleMediana = $vsMediana['delta'] >= self::PESO_DELTA_MINIMO_KB
            && $vsMediana['porcentaje'] >= self::PESO_VARIACION;

        if ($cumplePeso && $cumpleDelta && $variacion['porcentaje'] >= self::PESO_VARIACION && $cumpleMediana) {
            $critica = $variacion['porcentaje'] >= self::PESO_VARIACION_CRITICA
                || $vsMediana['porcentaje'] >= self::PESO_VARIACION_CRITICA;

            return [
                'tipo' => $critica ? 'critica' : 'anomalia',
                'etiqueta' => ($critica ? 'peso critico ' : 'peso ').$this->firmaDeVariacion($variacion),
                'detalle' => $this->formatearPeso($anterior['peso']).' KB -> '.$this->formatearPeso($ultimo['peso'])
                    .' KB ('.$this->firmaDeVariacion($variacion).'), mediana '
                    .$this->formatearPeso($mediana).' KB ('.$this->firmaDeVariacion($vsMediana).').',
                'bloquea' => true,
            ];
        }

        if (count($serie) >= self::PESO_SIN_CAMBIOS_DIAS) {
            $ultimos = array_slice($serie, -self::PESO_SIN_CAMBIOS_DIAS);
            if (count(array_unique(array_map(fn ($p) => $p['peso'], $ultimos))) === 1) {
                return [
                    'tipo' => 'sin_cambios',
                    'etiqueta' => 'sin cambios '.self::PESO_SIN_CAMBIOS_DIAS.' dias',
                    'detalle' => 'Peso identico ('.$this->formatearPeso($ultimo['peso']).' KB) en los ultimos '
                        .self::PESO_SIN_CAMBIOS_DIAS.' respaldos.',
                    'bloquea' => false,
                ];
            }
        }

        return null;
    }

    /**
     * Variacion entre dos pesos, en las dos unidades de la regla: porcentaje y KB.
     * El porcentaje es simetrico respecto al peso de referencia, asi que subir y bajar
     * miden igual.
     */
    private function variacionDePeso(float $referencia, float $actual): array
    {
        $delta = abs($actual - $referencia);

        return [
            'delta' => $delta,
            'signo' => $actual >= $referencia ? '+' : '-',
            'porcentaje' => $referencia > 0 ? $delta / $referencia : ($actual > 0 ? INF : 0.0),
        ];
    }

    private function firmaDeVariacion(array $variacion): string
    {
        // Sin peso de referencia el porcentaje no existe: se anota el cambio en KB,
        // que si dice algo de lo que paso.
        if (is_infinite($variacion['porcentaje'])) {
            return $variacion['signo'].$this->formatearPeso($variacion['delta']).' KB';
        }

        return $variacion['signo'].number_format($variacion['porcentaje'] * 100, 1).'%';
    }

    private function mediana(array $valores): float
    {
        if (empty($valores)) {
            return 0.0;
        }

        sort($valores);
        $total = count($valores);
        $mitad = intdiv($total, 2);

        return $total % 2 === 1
            ? (float) $valores[$mitad]
            : ((float) $valores[$mitad - 1] + (float) $valores[$mitad]) / 2;
    }

    /**
     * Ultimo peso por dia de cada archivo. Solo se leen los ultimos dias de respaldo:
     * parsear los ~175 mil lotes completos del historico es inviable. El dia se queda con
     * el lote mas reciente, que es el que manda.
     */
    private function consultarHistorialPesos(array $sucursales): array
    {
        $archivos = array_map('strtolower', self::ARCHIVOS_STOCK);
        $desde = now()->subDays(self::HISTORIAL_PESO_DIAS);

        if (DB::connection()->getDriverName() === 'pgsql') {
            $registros = $this->consultarHistorialPesosPgsql($sucursales, $archivos, $desde);
        } else {
            $registros = $this->consultarHistorialPesosGenerico($sucursales, $archivos, $desde);
        }

        $historial = [];
        foreach ($registros as $r) {
            $sucursal = strtolower($r->s);
            $archivo = strtolower($r->a);
            $dia = $r->dia;
            $historial[$sucursal]['_dias'][$dia] = true;

            if ($r->peso === null) {
                continue;
            }

            $historial[$sucursal][$archivo][$dia] = [
                'dia' => $dia,
                'peso' => round(((int) $r->peso) / 1024, 1),
            ];
        }

        foreach ($historial as $sucursal => $porArchivo) {
            foreach ($porArchivo as $clave => $serie) {
                if ($clave === '_dias') {
                    continue;
                }

                ksort($historial[$sucursal][$clave]);
                $historial[$sucursal][$clave] = array_values($historial[$sucursal][$clave]);
            }

            ksort($historial[$sucursal]['_dias']);
        }

        return $historial;
    }

    private function consultarHistorialPesosPgsql(array $sucursales, array $archivos, $desde): array
    {
        $sql = "WITH ultimos AS MATERIALIZED (
                    SELECT DISTINCT ON (lower(l.sucursal), (l.created_at)::date)
                           lower(l.sucursal) AS s,
                           to_char((l.created_at)::date, 'YYYY-MM-DD') AS dia,
                           l.id
                    FROM hash_archivos_lotes l
                    WHERE l.payload IS NOT NULL
                      AND l.payload <> ''
                      AND lower(l.sucursal) = ANY (?)
                      AND l.created_at >= ?
                    ORDER BY lower(l.sucursal), (l.created_at)::date, l.id DESC
                )
                SELECT u.s,
                       u.dia,
                       lower(a.elem->>'Nombre') AS a,
                       CASE WHEN (a.elem->>'Peso') ~ '^[0-9]+$' THEN (a.elem->>'Peso')::bigint END AS peso
                FROM ultimos u
                JOIN hash_archivos_lotes l ON l.id = u.id
                CROSS JOIN jsonb_array_elements(l.payload::jsonb->'Tiendas') AS t(elem)
                CROSS JOIN jsonb_array_elements(t.elem->'Archivos') AS a(elem)
                WHERE lower(coalesce(t.elem->>'Sucursal', u.s)) = u.s
                  AND lower(a.elem->>'Nombre') = ANY (?)";

        try {
            return DB::select($sql, [
                $this->arrayPgsql($sucursales),
                $desde,
                $this->arrayPgsql($archivos),
            ]);
        } catch (\Exception $e) {
            Log::warning('ArchivosStock historial pesos pgsql: '.$e->getMessage());

            return [];
        }
    }

    private function consultarHistorialPesosGenerico(array $sucursales, array $archivos, $desde): array
    {
        $permitidos = array_flip($archivos);

        // De cada dia se gana el lote mas reciente, asi que se recorre de mayor a menor
        // y la primera aparicion de un (sucursal, dia, archivo) ya es la buena.
        $lotes = DB::table('hash_archivos_lotes')
            ->whereNotNull('payload')
            ->where('payload', '!=', '')
            ->whereIn(DB::raw('lower(sucursal)'), $sucursales)
            ->where('created_at', '>=', $desde)
            ->orderByDesc('id')
            ->get(['sucursal', 'payload', 'created_at']);

        $registros = [];
        $vistos = [];

        foreach ($lotes as $lote) {
            $sucursal = strtolower(trim((string) $lote->sucursal));
            $dia = date('Y-m-d', strtotime((string) $lote->created_at));

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

                    $clave = $sucursal.'|'.$dia.'|'.$nombre;
                    if (isset($vistos[$clave])) {
                        continue;
                    }

                    $vistos[$clave] = true;
                    $registros[] = (object) [
                        's' => $sucursal,
                        'dia' => $dia,
                        'a' => $nombre,
                        'peso' => isset($archivo['Peso']) ? (int) $archivo['Peso'] : null,
                    ];
                }
            }
        }

        return $registros;
    }

    /**
     * Semaforo del archivo. El color lo define la antiguedad y la existencia, no el hash:
     * un hash distinto solo se muestra como nota informativa. Los archivos de solo
     * existencia quedan en verde o rojo, los de consulta no cuentan y EYSIPAR no aplica
     * a los almacenes.
     */
    private function calcularSemaforo(string $archivo, ?int $dias, bool $existe, bool $esAlmacen, bool $hayCarpeta = false): string
    {
        if (in_array($archivo, self::ARCHIVOS_CONSULTA, true)) {
            return 'no_cuenta';
        }

        if ($archivo === self::ARCHIVO_SIN_ALMACENES && $esAlmacen) {
            return 'no_aplica';
        }

        // Archivos de solo existencia: basta con que el archivo se haya visto o con que
        // la sucursal haya reportado una carpeta (lote); que no venga en un lote concreto
        // no prueba que falte.
        if (in_array($archivo, self::ARCHIVOS_EXISTENCIA, true)) {
            return ($existe || $hayCarpeta) ? 'verde' : 'rojo';
        }

        if (! $existe) {
            return 'rojo';
        }

        if (! isset(self::ARCHIVOS_CON_FECHA[$archivo])) {
            return 'verde';
        }

        if ($dias === null) {
            return 'rojo';
        }

        [$verde, $amarillo] = self::ARCHIVOS_CON_FECHA[$archivo];

        if ($dias <= $verde) {
            return 'verde';
        }

        return $dias <= $amarillo ? 'amarillo' : 'rojo';
    }

    /**
     * PROVPROD y TABLACON son intercambiables: con que exista uno de los dos el punto
     * queda cubierto, y asi se refleja tambien en el detalle de las dos filas.
     */
    private function existeArchivo(string $archivo, array $existencias): bool
    {
        if (in_array($archivo, self::PAR_ALTERNATIVO, true)) {
            foreach (self::PAR_ALTERNATIVO as $alternativa) {
                if (! empty($existencias[strtolower($alternativa)])) {
                    return true;
                }
            }

            return false;
        }

        return ! empty($existencias[strtolower($archivo)]);
    }

    /**
     * Peso en KB con separador de miles. Devuelve cadena porque el formato es
     * de presentacion y el valor numerico se conserva en 'peso'.
     */
    private function formatearPeso(?float $peso): ?string
    {
        if ($peso === null) {
            return null;
        }

        return number_format($peso, 1, '.', ',');
    }

    /**
     * El CSV usa ';' como delimitador, asi que un peso con coma de miles
     * ('4,020.0') queda sin comillas y Excel, con la region en espanol, lo
     * interpreta como dos campos y desplaza el resto de las columnas. En la
     * exportacion el peso va como numero plano; el formato con separador de
     * miles se queda solo para la pantalla.
     */
    private function formatearPesoCsv(?float $peso): string
    {
        if ($peso === null) {
            return '';
        }

        return number_format($peso, 1, '.', '');
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
        // último lote por (sucursal, disparador) y solo después convierte a jsonb los
        // ~800 payloads ganadores, en lugar de los miles que leería el planner si el
        // CTE se fusionara. max(id) grouped por sucursal/disparador es más rápido que
        // DISTINCT ON con ORDER BY (331 -> 143 ms) y usa el mismo criterio de "último".
        $sql = "WITH ids AS MATERIALIZED (
                    SELECT s, d, max(l.id) AS id
                    FROM (
                        SELECT lower(l.sucursal) AS s,
                               lower(l.disparador) AS d,
                               l.id
                        FROM hash_archivos_lotes l
                        WHERE lower(l.disparador) IN ('rbf', 'rebsa')
                          AND l.payload IS NOT NULL
                          AND l.payload <> ''
                          AND lower(l.sucursal) = ANY (?)
                    ) l
                    GROUP BY s, d
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

    /**
     * El porcentaje se mide por instalacion, no por archivo: instalaciones listas sobre
     * instalaciones contempladas. Una instalacion esta lista cuando su carpeta ha
     * reportado, sus cinco archivos con fecha estan en verde y ninguno trae anomalia de
     * peso; si le falla uno sola, toda la instalacion queda pendiente. Los archivos de
     * solo existencia se anotan en el detalle pero no entran en esta cuenta.
     */
    private function evaluarInstalaciones(array $filas, $computers): array
    {
        $porEquipo = [];
        foreach ($filas as $fila) {
            $porEquipo[$fila['id']][] = $fila;
        }

        $shortKeys = $computers
            ->map(fn ($c) => strtolower(trim((string) $c->short_key)))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $conCarpeta = $this->sucursalesConCarpeta($shortKeys);

        $totalAgentes = 0;
        $contempladas = 0;
        $listas = 0;
        $causas = [];
        $porPlaza = [];

        foreach ($computers as $computer) {
            $totalAgentes++;

            if (! in_array($this->tipoGrupo($computer), self::TIPOS_CONTEMPLADOS, true)) {
                continue;
            }

            $sucursal = strtolower(trim((string) $computer->short_key));
            if ($sucursal === '' || empty($conCarpeta[$sucursal])) {
                continue;
            }

            $contempladas++;
            $lista = true;
            $motivos = [];

            foreach ($porEquipo[$computer->id] ?? [] as $fila) {
                if ($fila['tipo_archivo'] !== 'fecha') {
                    continue;
                }

                if ($fila['estado'] !== 'verde') {
                    $lista = false;
                    $motivos[$fila['archivo']] = true;
                }

                if (! empty($fila['peso_senal']['bloquea'])) {
                    $lista = false;
                    $motivos['PESO'] = true;
                }
            }

            if ($lista) {
                $listas++;
            }

            foreach (array_keys($motivos) as $codigo) {
                $causas[$codigo] = ($causas[$codigo] ?? 0) + 1;
            }

            $plaza = $computer->plaza ?? 'N/A';
            if (! isset($porPlaza[$plaza])) {
                $porPlaza[$plaza] = ['plaza' => $plaza, 'total' => 0, 'listas' => 0];
            }
            $porPlaza[$plaza]['total']++;
            if ($lista) {
                $porPlaza[$plaza]['listas']++;
            }
        }

        $perPlaza = array_map(fn ($s) => [
            'plaza' => $s['plaza'],
            'total' => $s['total'],
            'listas' => $s['listas'],
            'pendientes' => $s['total'] - $s['listas'],
            'percent' => $s['total'] > 0 ? round(($s['listas'] / $s['total']) * 100, 1) : 0,
        ], $porPlaza);

        usort($perPlaza, fn ($a, $b) => $b['total'] <=> $a['total']);

        $etiquetasCausas = [];
        foreach ($causas as $codigo => $cantidad) {
            $etiquetasCausas[] = [
                'codigo' => $codigo,
                'etiqueta' => $codigo === 'PESO' ? 'Anomalía de peso' : $codigo,
                'cantidad' => $cantidad,
            ];
        }
        usort($etiquetasCausas, fn ($a, $b) => $b['cantidad'] <=> $a['cantidad']);

        return [
            'total_agentes' => $totalAgentes,
            'total_instalaciones' => $contempladas,
            'listas' => $listas,
            'pendientes' => $contempladas - $listas,
            'no_contempladas' => $totalAgentes - $contempladas,
            'percent' => $contempladas > 0 ? round(($listas / $contempladas) * 100, 1) : 0,
            'per_plaza' => $perPlaza,
            'causas' => $etiquetasCausas,
        ];
    }

    public function export(Request $request)
    {
        try {
            [$computers, $archivosFiltro] = $this->aplicarFiltros($request);

            $filas = $this->construirFilas($computers);

            $this->aplicarPesos($filas);
            $this->aplicarAnomaliasDePeso($filas);

            $filas = $this->filtrarPorArchivo($filas, $archivosFiltro);

            $estadoInput = strtolower(trim((string) ($request->query('estado') ?? $request->input('estado', ''))));
            if (in_array($estadoInput, self::ESTADOS, true)) {
                $filas = array_values(array_filter($filas, fn ($row) => $row['estado'] === $estadoInput));
            }

            $filas = $this->filtrarPorDias($filas, $request);

            usort($filas, function ($a, $b) {
                $cmp = strcmp(strtolower($a['plaza']), strtolower($b['plaza']));
                if ($cmp === 0) {
                    $cmp = strcmp(strtolower($a['nombre_instalacion']), strtolower($b['nombre_instalacion']));
                }

                return $cmp !== 0 ? $cmp : strcmp(strtolower($a['archivo']), strtolower($b['archivo']));
            });

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
                    'Estado', 'Días', 'Señal de peso',
                ], ';');

                $etiquetasEstado = [
                    'verde' => 'Verde',
                    'amarillo' => 'Amarillo',
                    'rojo' => 'Rojo',
                    'no_aplica' => 'No aplica',
                    'no_cuenta' => 'No cuenta',
                ];

                foreach ($filas as $fila) {
                    fputcsv($output, [
                        $fila['plaza'],
                        $fila['nombre_instalacion'],
                        $fila['estado_equipo'],
                        $fila['rbf']['archivo'] ?? '',
                        $fila['rbf']['hash'] ?? '',
                        $this->formatearFecha($fila['rbf']['fecha_modificacion'] ?? null),
                        $this->formatearPesoCsv($fila['rbf']['peso'] ?? null),
                        $fila['rebsamen']['archivo'] ?? '',
                        $fila['rebsamen']['hash'] ?? '',
                        $this->formatearFecha($fila['rebsamen']['fecha_modificacion'] ?? null),
                        $this->formatearPesoCsv($fila['rebsamen']['peso'] ?? null),
                        $etiquetasEstado[$fila['estado']] ?? $fila['estado'],
                        $fila['dias'] === null ? '' : $fila['dias'],
                        $fila['peso_senal']['etiqueta'] ?? '',
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
