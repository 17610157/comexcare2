<?php

namespace App\Services;

use App\Models\Computer;
use App\Models\DashboardAlertEvent;
use App\Models\DashboardAlertRule;
use App\Models\RbfConfigStatus;
use App\Models\RbfFileHash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AlertService
{
    private const SPECIFIC_FILES = [
        'ARCERO.DBF', 'CABLISTA.DBF', 'CLIECATP.DBF', 'LISTA.DBF',
        'OFERTAS.DBF', 'PCOMB.DBF', 'PDCOMB.DBF', 'PROMARTS.DBF',
    ];

    private const FILE_TO_SERVICE = [
        'LISTA.DBF' => ['servicio' => 'lista', 'config_col' => 'li'],
        'CABLISTA.DBF' => ['servicio' => 'lista', 'config_col' => 'li'],
        'OFERTAS.DBF' => ['servicio' => 'oferta', 'config_col' => 'of'],
        'PROMARTS.DBF' => ['servicio' => 'promo', 'config_col' => 'pr'],
        'ARCERO.DBF' => ['servicio' => 'promo', 'config_col' => 'pr'],
        'PCOMB.DBF' => ['servicio' => 'combo', 'config_col' => 'co'],
        'PDCOMB.DBF' => ['servicio' => 'combo', 'config_col' => 'co'],
        'CLIECATP.DBF' => ['servicio' => 'dbf', 'config_col' => 'db'],
    ];

    /**
     * Nivel de servicio global del reporte de precios (% de archivos actualizados).
     */
    public function dbfServiceLevel(): ?float
    {
        return Cache::remember('alerts.dbf_service_level', 60, function () {
            $total = 0;
            $matched = 0;

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

                foreach (self::FILE_TO_SERVICE as $fileName => $info) {
                    $zona = strtolower($arr[$info['config_col']] ?? '');
                    if ($zona === '' || $zona === 'vacio') {
                        continue;
                    }
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

            Computer::whereNotNull('plaza')
                ->where('plaza', '!=', '')
                ->whereNull('deleted_at')
                ->select('id', 'plaza', 'short_key', 'agent_config', 'last_seen')
                ->chunk(500, function ($computers) use (&$total, &$matched, $rbfLookup, $configHashLookup) {
                    foreach ($computers as $computer) {
                        $dbfFiles = $computer->agent_config['dbf_files'] ?? [];
                        $dbfFiles = array_values(array_filter(
                            $dbfFiles,
                            fn ($f) => in_array(strtoupper($f['name'] ?? ''), self::SPECIFIC_FILES)
                        ));
                        if (empty($dbfFiles)) {
                            continue;
                        }

                        $configKey = strtolower($computer->plaza ?? '').'|'.strtolower($computer->short_key ?? '');

                        foreach ($dbfFiles as $file) {
                            $total++;
                            $fileName = $file['name'] ?? '';

                            $rbfRecord = $configHashLookup[$configKey.'|'.strtolower($fileName)] ?? null;
                            if (! $rbfRecord) {
                                $hashKey = strtolower($computer->plaza ?? '').'|'.strtoupper(substr($file['hash_md5'] ?? '', -5)).'|'.strtolower($fileName);
                                $rbfRecord = $rbfLookup[$hashKey] ?? null;
                            }

                            if (! $rbfRecord) {
                                continue;
                            }

                            $localHash = strtoupper(substr($file['hash_md5'] ?? '', -5));
                            if ($localHash !== '' && $localHash === strtoupper($rbfRecord->hash ?? '')) {
                                $matched++;
                            } else {
                                $localModified = strtotime($file['modified'] ?? '');
                                $rbfModified = $rbfRecord->last_modified ? strtotime($rbfRecord->last_modified) : 0;
                                if ($localModified > 0 && $rbfModified > 0 && $localModified > $rbfModified) {
                                    $matched++;
                                }
                            }
                        }
                    }
                });

            if ($total === 0) {
                return null;
            }

            return round(($matched / $total) * 100, 2);
        });
    }

    /**
     * Valores actuales de todas las métricas porcentuales.
     */
    public function snapshot(): array
    {
        $srv = ServerMetricsService::collect();
        if (($srv['cpu'] ?? null) === null) {
            usleep(200000);
            $srv = ServerMetricsService::collect();
        }
        $window = max(1, (int) config('dashboard.online_window_minutes', 5));
        $threshold = now()->subMinutes($window);

        $row = DB::table('computers')
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as online', [$threshold])
            ->first();

        $total = (int) ($row->total ?? 0);
        $online = (int) ($row->online ?? 0);

        return [
            'srv_cpu_pct' => $srv['cpu'] ?? null,
            'srv_ram_pct' => $srv['mem_pct'] ?? null,
            'srv_disk_pct' => $srv['disk']['pct'] ?? null,
            'fleet_online_pct' => $total > 0 ? round(($online / $total) * 100, 2) : null,
            'dbf_service_level_pct' => $this->dbfServiceLevel(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Evalúa las reglas porcentuales contra el snapshot y registra eventos respetando cooldown.
     */
    public function evaluateAndRecord(): array
    {
        $snapshot = $this->snapshot();
        $keyMap = [
            'fleet_online' => 'fleet_online_pct',
            'dbf_service_level' => 'dbf_service_level_pct',
            'srv_cpu' => 'srv_cpu_pct',
            'srv_ram' => 'srv_ram_pct',
            'srv_disk' => 'srv_disk_pct',
        ];

        $triggered = [];

        foreach (DashboardAlertRule::whereRaw('enabled')->whereIn('comparator', ['gt', 'lt'])->get() as $rule) {
            $value = $snapshot[$keyMap[$rule->metric_key]] ?? null;
            if ($value === null || $rule->threshold_pct === null) {
                continue;
            }

            $breach = $rule->comparator === 'gt' ? $value > $rule->threshold_pct : $value < $rule->threshold_pct;
            if (! $breach) {
                continue;
            }

            if ($this->inCooldown($rule)) {
                continue;
            }

            $this->recordEvent($rule, (float) $value, self::describeBreach($rule, (float) $value));
            $triggered[] = $rule->metric_key;
        }

        return ['snapshot' => $snapshot, 'triggered' => $triggered];
    }

    protected function inCooldown(DashboardAlertRule $rule): bool
    {
        $last = DashboardAlertEvent::where('rule_id', $rule->id)->orderByDesc('triggered_at')->first();

        return $last && $last->triggered_at->diffInMinutes(now()) < $rule->cooldown_min;
    }

    public function recordEvent(DashboardAlertRule $rule, ?float $value = null, ?string $message = null, ?array $meta = null): DashboardAlertEvent
    {
        return DashboardAlertEvent::create([
            'rule_id' => $rule->id,
            'value_pct' => $value,
            'message' => $message,
            'meta' => $meta,
            'triggered_at' => now(),
        ]);
    }

    public static function describeBreach(DashboardAlertRule $rule, float $value): string
    {
        return sprintf(
            '%s al %s%% (umbral: %s %s%%)',
            $rule->label,
            rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.'),
            $rule->comparator === 'lt' ? 'mínimo' : 'máximo',
            $rule->threshold_pct
        );
    }
}
