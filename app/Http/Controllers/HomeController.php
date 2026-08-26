<?php

namespace App\Http\Controllers;

use App\Models\AgentVersion;
use App\Services\DashboardStatsService;
use App\Services\ServerMetricsService;
use App\Services\AlertService;
use App\Models\Computer;
use App\Models\RbfFileHash;
use App\Models\RbfConfigStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $plaza = $request->string('plaza')->toString() ?: null;
        $window = (int) $request->input('window', 5);

        $stats = app(DashboardStatsService::class)->all($plaza, $window);

        return view('home', array_merge($stats, [
            'selectedPlaza' => $plaza,
            'onlineWindows' => DashboardStatsService::ONLINE_WINDOWS,
        ]));
    }

    public function stats(Request $request): JsonResponse
    {
        $plaza = $request->string('plaza')->toString() ?: null;
        $window = (int) $request->input('window', 5);

        return response()->json(app(DashboardStatsService::class)->all($plaza, $window));
    }

    public function serverStats(): JsonResponse
    {
        return response()->json(ServerMetricsService::collect());
    }

    public function mapStats(Request $request): JsonResponse
    {
        $window = max(1, (int) $request->input('window', config('dashboard.online_window_minutes', 5)));
        $threshold = now()->subMinutes($window);

        $regions = [];

        foreach (config('dashboard.regions') as $region) {
            $row = DB::table('computers')
                ->whereIn('plaza', $region['plazas'])
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as online', [$threshold])
                ->first();

            $total = (int) ($row->total ?? 0);
            $online = (int) ($row->online ?? 0);

            $regions[] = [
                'id' => $region['id'],
                'name' => $region['name'],
                'country' => $region['country'],
                'geo_names' => $region['geo_names'],
                'online' => $online,
                'offline' => $total - $online,
                'total' => $total,
            ];
        }

        $un = DB::table('computers')
            ->where(function ($q) {
                $q->whereNull('plaza')->orWhere('plaza', '');
            })
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as online', [$threshold])
            ->first();

        $unTotal = (int) ($un->total ?? 0);
        $unOnline = (int) ($un->online ?? 0);

        return response()->json([
            'regions' => $regions,
            'unassigned' => [
                'id' => 'sin_ubicacion',
                'name' => 'Sin ubicación',
                'online' => $unOnline,
                'offline' => $unTotal - $unOnline,
                'total' => $unTotal,
            ],
        ]);
    }

    public function mapComputers(Request $request): JsonResponse
    {
        $data = $request->validate(['region' => ['required', 'string']]);
        $window = max(1, (int) $request->input('window', config('dashboard.online_window_minutes', 5)));
        $threshold = now()->subMinutes($window);

        if ($data['region'] === 'sin_ubicacion') {
            $query = DB::table('computers')->where(function ($q) {
                $q->whereNull('plaza')->orWhere('plaza', '');
            });
            $name = 'Sin ubicación';
        } else {
            $region = collect(config('dashboard.regions'))->firstWhere('id', $data['region']);
            abort_unless($region, 404);
            $query = DB::table('computers')->whereIn('plaza', $region['plazas']);
            $name = $region['name'];
        }

        $computers = $query
            ->orderByRaw('CASE WHEN last_seen >= ? THEN 0 ELSE 1 END', [$threshold])
            ->orderBy('nombre_instalacion')
            ->selectRaw('id, computer_name, nombre_instalacion, plaza, last_seen, (CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as is_online', [$threshold])
            ->get();

        return response()->json([
            'region' => $name,
            'computers' => $computers->map(function ($c) {
                return [
                    'id' => $c->id,
                    'name' => $c->nombre_instalacion ?: $c->computer_name,
                    'plaza' => $c->plaza,
                    'online' => (bool) $c->is_online,
                ];
            })->values(),
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $limit = min(50, max(10, (int) $request->input('limit', 30)));

        $rows = DB::table('computer_logs as cl')
            ->leftJoin('computers as c', 'c.id', '=', 'cl.computer_id')
            ->orderByDesc('cl.id')
            ->limit($limit)
            ->get(['cl.id', 'cl.level', 'cl.message', 'cl.created_at', 'c.nombre_instalacion', 'c.computer_name', 'c.plaza']);

        return response()->json([
            'events' => $rows->map(function ($r) {
                $msg = (string) $r->message;
                $pc = null;
                if (preg_match('/PC:([^\]\s]+)/', $msg, $m)) {
                    $pc = $m[1];
                }
                return [
                    'id' => $r->id,
                    'kind' => self::classifyLog($msg),
                    'level' => $r->level,
                    'pc' => $pc ?: ($r->nombre_instalacion ?: $r->computer_name),
                    'plaza' => $r->plaza,
                    'message' => mb_substr($msg, 0, 1200),
                    'age_s' => $r->created_at ? max(0, time() - strtotime($r->created_at)) : null,
                    'at' => $r->created_at ? date('d/m/Y H:i:s', strtotime($r->created_at)) : null,
                ];
            })->values(),
        ]);
    }

    public function fleetHealth(): JsonResponse
    {
        $activeAgentRow = AgentVersion::query()->get(['version', 'is_active'])->firstWhere('is_active', true);
        $activeAgent = $activeAgentRow ? $activeAgentRow->version : null;

        $pvsiStandard = DB::table('computers')
            ->whereNotNull('pvsi_version')
            ->selectRaw('pvsi_version::text as v, count(*) as c')
            ->groupBy('pvsi_version')
            ->orderByDesc('c')
            ->value('v');

        $threshold = now()->subMinutes(max(1, (int) config('dashboard.online_window_minutes', 5)));

        $rows = DB::table('computers')
            ->whereNull('deleted_at')
            ->orderBy('plaza')
            ->orderBy('nombre_instalacion')
            ->selectRaw(
                'id, computer_name, nombre_instalacion, plaza, last_seen, agent_version, bitlocker_status, total_ram, pvsi_version, (CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as is_online',
                [$threshold]
            )
            ->get();

        $computers = $rows->map(function ($c) use ($activeAgent, $pvsiStandard) {
            $online = (bool) $c->is_online;
            $agentOk = $c->agent_version !== null && $activeAgent !== null && $c->agent_version === $activeAgent;
            $bl = json_decode((string) $c->bitlocker_status, true);
            $bitlockerOk = is_array($bl) && in_array('Enabled', array_map('strval', array_values($bl)), true);
            $ramGb = $c->total_ram !== null ? round(((int) $c->total_ram) / 1073741824, 1) : null;
            $ramOk = $ramGb !== null && $ramGb >= 4;
            $pvsiOk = $c->pvsi_version !== null && $pvsiStandard !== null && $c->pvsi_version === $pvsiStandard;

            $score = ($online ? 40 : 0) + ($agentOk ? 20 : 0) + ($bitlockerOk ? 15 : 0) + ($pvsiOk ? 15 : 0) + ($ramOk ? 10 : 0);

            return [
                'id' => $c->id,
                'name' => $c->nombre_instalacion ?: $c->computer_name,
                'plaza' => $c->plaza,
                'online' => $online,
                'score' => $score,
                'state' => !$online ? 'off' : ($score >= 80 ? 'ok' : ($score >= 50 ? 'warn' : 'crit')),
                'details' => [
                    'agente' => $c->agent_version,
                    'agente_ok' => $agentOk,
                    'bitlocker' => $bitlockerOk ? 'ON' : 'OFF',
                    'ram_gb' => $ramGb,
                    'pvsi' => $c->pvsi_version,
                    'last_seen' => $c->last_seen,
                ],
            ];
        })->values();

        $counts = ['ok' => 0, 'warn' => 0, 'crit' => 0, 'off' => 0];
        foreach ($computers as $pc) {
            $counts[$pc['state']]++;
        }

        return response()->json(['counts' => $counts, 'computers' => $computers]);
    }

    protected static function classifyLog(string $msg): string
    {
        if (preg_match('/vale/i', $msg)) {
            return 'vales';
        }
        if (preg_match('/rbf|hash/i', $msg)) {
            return 'rbf';
        }
        if (preg_match('/download|descarga/i', $msg)) {
            return 'descargas';
        }
        if (preg_match('/command|comando|ejecut|\.bat/i', $msg)) {
            return 'comandos';
        }
        return 'sistema';
    }

    public function dbfOverview(): JsonResponse
    {
        $specificFiles = [
            'ARCERO.DBF', 'CABLISTA.DBF', 'CLIECATP.DBF', 'LISTA.DBF',
            'OFERTAS.DBF', 'PCOMB.DBF', 'PDCOMB.DBF', 'PROMARTS.DBF',
        ];
        $fileToService = [
            'LISTA.DBF' => ['servicio' => 'lista', 'config_col' => 'li'],
            'CABLISTA.DBF' => ['servicio' => 'lista', 'config_col' => 'li'],
            'OFERTAS.DBF' => ['servicio' => 'oferta', 'config_col' => 'of'],
            'PROMARTS.DBF' => ['servicio' => 'promo', 'config_col' => 'pr'],
            'ARCERO.DBF' => ['servicio' => 'promo', 'config_col' => 'pr'],
            'PCOMB.DBF' => ['servicio' => 'combo', 'config_col' => 'co'],
            'PDCOMB.DBF' => ['servicio' => 'combo', 'config_col' => 'co'],
            'CLIECATP.DBF' => ['servicio' => 'dbf', 'config_col' => 'db'],
        ];

        $rbfLookup = [];
        foreach (RbfFileHash::all() as $r) {
            $rbfLookup[strtolower($r->plaza ?? '').'|'.strtoupper($r->hash ?? '').'|'.strtolower($r->name ?? '')] = $r;
        }

        $hashesByPlaza = RbfFileHash::all()->groupBy(fn ($r) => strtolower($r->plaza ?? ''));
        $configHashLookup = [];
        foreach (RbfConfigStatus::all()->keyBy(fn ($r) => strtolower($r->pl).'|'.strtolower($r->ca)) as $configKey => $config) {
            $plaza = strtolower($config->pl);
            $plazaHashes = $hashesByPlaza[$plaza] ?? collect();
            $arr = $config->toArray();
            foreach ($fileToService as $fileName => $info) {
                $zona = strtolower($arr[$info['config_col']] ?? '');
                if ($zona === '' || $zona === 'vacio') continue;
                $hashRecord = $plazaHashes->first(
                    fn ($h) => strtolower($h->servicio ?? '') === $info['servicio']
                        && strtolower($h->zona ?? '') === $zona
                        && strtolower($h->name ?? '') === strtolower($fileName)
                );
                if ($hashRecord) {
                    $configHashLookup[$configKey.'|'.strtolower($fileName)] = $hashRecord;
                }
            }
        }

        $globalTotal = 0;
        $globalMatched = 0;
        $globalCambioManual = 0;
        $globalDesactualizado = 0;
        $plazaStats = [];

        Computer::whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->whereNull('deleted_at')
            ->select('id', 'plaza', 'short_key', 'agent_config')
            ->chunk(500, function ($computers) use (
                &$globalTotal, &$globalMatched, &$globalCambioManual, &$globalDesactualizado,
                &$plazaStats, $specificFiles, $fileToService, $rbfLookup, $configHashLookup
            ) {
                foreach ($computers as $computer) {
                    $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
                    $dbfFiles = array_values(array_filter(
                        $dbfFiles,
                        fn ($f) => in_array(strtoupper($f['name'] ?? ''), $specificFiles)
                    ));
                    if (empty($dbfFiles)) continue;

                    $configKey = strtolower($computer->plaza ?? '').'|'.strtolower($computer->short_key ?? '');
                    $plaza = $computer->plaza ?? 'N/A';

                    foreach ($dbfFiles as $file) {
                        $fileName = $file['name'] ?? '';
                        $globalTotal++;

                        $rbfRecord = $configHashLookup[$configKey.'|'.strtolower($fileName)] ?? null;
                        if (! $rbfRecord) {
                            $hashKey = strtolower($computer->plaza ?? '').'|'.strtoupper(substr($file['hash_md5'] ?? '', -5)).'|'.strtolower($fileName);
                            $rbfRecord = $rbfLookup[$hashKey] ?? null;
                        }

                        $localHash = strtoupper(substr($file['hash_md5'] ?? '', -5));
                        $rbfHash = strtoupper($rbfRecord->hash ?? '');
                        $hashMatch = $rbfRecord !== null && $localHash === $rbfHash;

                        if (! $rbfRecord) {
                            $rbfStatus = 'cambio_manual';
                        } elseif ($hashMatch) {
                            $rbfStatus = 'actualizado';
                        } else {
                            $localModified = strtotime($file['modified'] ?? '');
                            $rbfModified = $rbfRecord->last_modified ? strtotime($rbfRecord->last_modified) : 0;
                            $rbfStatus = ($localModified > 0 && $rbfModified > 0 && $localModified > $rbfModified)
                                ? 'cambio_manual'
                                : 'desactualizado';
                        }

                        if ($rbfStatus === 'actualizado') $globalMatched++;
                        elseif ($rbfStatus === 'cambio_manual') $globalCambioManual++;
                        else $globalDesactualizado++;

                        if (! isset($plazaStats[$plaza])) {
                            $plazaStats[$plaza] = ['total' => 0, 'actualizado' => 0, 'cambio_manual' => 0, 'desactualizado' => 0];
                        }
                        $plazaStats[$plaza]['total']++;
                        $plazaStats[$plaza][$rbfStatus]++;
                    }
                }
            });

        $breakdown = collect($plazaStats)
            ->map(fn ($s, $plaza) => [
                'plaza' => $plaza,
                'total' => $s['total'],
                'actualizado' => $s['actualizado'],
                'cambio_manual' => $s['cambio_manual'],
                'desactualizado' => $s['desactualizado'],
                'cumplimiento_pct' => $s['total'] > 0 ? round(($s['actualizado'] / $s['total']) * 100, 1) : 0,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return response()->json([
            'total' => $globalTotal,
            'actualizado' => $globalMatched,
            'cambio_manual' => $globalCambioManual,
            'desactualizado' => $globalDesactualizado,
            'cumplimiento_pct' => $globalTotal > 0 ? round(($globalMatched / $globalTotal) * 100, 1) : 0,
            'breakdown' => $breakdown,
        ]);
    }
}
