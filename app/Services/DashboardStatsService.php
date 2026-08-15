<?php

namespace App\Services;

use App\Events\DashboardStatsUpdated;
use App\Models\AgentVersion;
use App\Models\AuditLog;
use App\Models\AuthorizableEmail;
use App\Models\AuthorizationToken;
use App\Models\Computer;
use App\Models\Distribution;
use App\Models\DistributionTarget;
use App\Models\FileList;
use App\Models\MonitoredFile;
use App\Models\Reception;
use App\Models\ReceptionTarget;
use App\Models\RbfFileHash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardStatsService
{
    public const ONLINE_WINDOWS = [5, 15, 30, 60, 360, 1440];

    public function all(?string $plaza = null, int $windowMinutes = 5): array
    {
        $windowMinutes = in_array($windowMinutes, self::ONLINE_WINDOWS, true) ? $windowMinutes : 5;

        return [
            'generated_at' => now()->toIso8601String(),
            'online_window_minutes' => $windowMinutes,
            'computers' => $this->computersStats($plaza, $windowMinutes),
            'distributions' => $this->distributionStats($plaza),
            'receptions' => $this->receptionStats($plaza),
            'agent' => $this->agentStats($plaza),
            'authorizations' => $this->authorizationStats(),
            'monitoring' => $this->monitoringStats(),
            'system' => $this->systemStats(),
            'filters' => [
                'plazas' => Computer::whereNotNull('plaza')
                    ->where('plaza', '!=', '')
                    ->distinct()
                    ->orderBy('plaza')
                    ->pluck('plaza'),
            ],
        ];
    }

    protected function computersStats(?string $plaza, int $windowMinutes): array
    {
        $threshold = now()->subMinutes($windowMinutes);

        $query = function ($q = null) use ($plaza) {
            if ($plaza) {
                return ($q ?? Computer::query())->where('plaza', $plaza);
            }

            return $q ?? Computer::query();
        };

        $total = $query()->count();
        $online = $query()->where('last_seen', '>=', $threshold)->count();
        $offline = $query()->where(function ($q) use ($threshold) {
            $q->where('last_seen', '<', $threshold)->orWhereNull('last_seen');
        })->count();

        $byPlaza = $query()
            ->whereNotNull('plaza')
            ->where('plaza', '!=', '')
            ->select('plaza')
            ->selectRaw('count(*) as total')
            ->selectRaw('SUM(CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as online', [$threshold])
            ->groupBy('plaza')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($p) => [
                'plaza' => $p->plaza,
                'total' => (int) $p->total,
                'online' => (int) $p->online,
                'offline' => (int) $p->total - (int) $p->online,
                'percentage' => $p->total > 0 ? round(((int) $p->online / (int) $p->total) * 100, 1) : 0,
            ])
            ->values()
            ->all();

        $versions = function (string $column) use ($query) {
            return $query()
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->select($column)
                ->selectRaw('count(*) as total')
                ->groupBy($column)
                ->orderByDesc('total')
                ->get()
                ->map(fn ($v) => ['version' => $v->{$column}, 'total' => (int) $v->total])
                ->values()
                ->all();
        };

        return [
            'total' => $total,
            'online' => $online,
            'offline' => $offline,
            'online_percentage' => $total > 0 ? round(($online / $total) * 100, 1) : 0,
            'by_plaza' => $byPlaza,
            'agent_versions' => $versions('agent_version'),
            'pvsi_versions' => $versions('pvsi_version'),
        ];
    }

    protected function distributionStats(?string $plaza): array
    {
        $scope = function ($query) use ($plaza) {
            if (! $plaza) {
                return $query;
            }

            return $query->whereIn('id', DistributionTarget::query()
                ->whereHas('computer', fn ($q) => $q->where('plaza', $plaza))
                ->select('distribution_id'));
        };

        $byStatus = $scope(Distribution::query())
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->status => (int) $d->total])
            ->all();

        $targets = $this->targetSummary(DistributionTarget::query(), $plaza);

        $recent = $scope(Distribution::query())
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'name', 'status', 'updated_at']);

        $recentData = $this->attachTargetSummary($recent, DistributionTarget::query(), 'distribution_id', $plaza);

        return [
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'targets' => $targets,
            'recent' => $recentData,
        ];
    }

    protected function receptionStats(?string $plaza): array
    {
        $scope = function ($query) use ($plaza) {
            if (! $plaza) {
                return $query;
            }

            return $query->whereIn('id', ReceptionTarget::query()
                ->whereHas('computer', fn ($q) => $q->where('plaza', $plaza))
                ->select('reception_id'));
        };

        $byStatus = $scope(Reception::query())
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($d) => [$d->status => (int) $d->total])
            ->all();

        $targets = $this->targetSummary(ReceptionTarget::query(), $plaza);

        $recent = $scope(Reception::query())
            ->latest('updated_at')
            ->limit(8)
            ->get(['id', 'name', 'status', 'updated_at']);

        $recentData = $this->attachTargetSummary($recent, ReceptionTarget::query(), 'reception_id', $plaza);

        return [
            'total' => array_sum($byStatus),
            'by_status' => $byStatus,
            'targets' => $targets,
            'recent' => $recentData,
        ];
    }

    protected function targetSummary($query, ?string $plaza): array
    {
        $query->when($plaza, fn ($q) => $q->whereHas('computer', fn ($c) => $c->where('plaza', $plaza)));

        return $query
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(fn ($t) => [$t->status => (int) $t->total])
            ->all();
    }

    protected function attachTargetSummary(iterable $rows, $targetQuery, string $foreignKey, ?string $plaza): array
    {
        $ids = collect($rows)->pluck('id')->filter()->all();

        $summary = [];
        if (! empty($ids)) {
            $summary = $targetQuery
                ->when($plaza, fn ($q) => $q->whereHas('computer', fn ($c) => $c->where('plaza', $plaza)))
                ->whereIn($foreignKey, $ids)
                ->select($foreignKey)
                ->selectRaw('count(*) as total')
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', ['completed'])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as failed', ['failed'])
                ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as in_progress', ['in_progress'])
                ->groupBy($foreignKey)
                ->get()
                ->keyBy($foreignKey);
        }

        $result = [];

        foreach ($rows as $row) {
            $s = $summary[$row->id] ?? null;
            $total = (int) ($s->total ?? 0);
            $completed = (int) ($s->completed ?? 0);
            $failed = (int) ($s->failed ?? 0);
            $inProgress = (int) ($s->in_progress ?? 0);

            $result[] = [
                'id' => $row->id,
                'name' => $row->name,
                'status' => $row->status,
                'updated_at' => optional($row->updated_at)->toIso8601String(),
                'total_targets' => $total,
                'completed_targets' => $completed,
                'failed_targets' => $failed,
                'in_progress_targets' => $inProgress,
                'percent' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    protected function agentStats(?string $plaza): array
    {
        $base = Computer::query()->when($plaza, fn ($q) => $q->where('plaza', $plaza));

        $distribution = (clone $base)
            ->whereNotNull('agent_version')
            ->select('agent_version')
            ->selectRaw('count(*) as total')
            ->groupBy('agent_version')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($v) => ['version' => $v->agent_version, 'total' => (int) $v->total])
            ->values()
            ->all();

        $versions = AgentVersion::orderBy('version')->get(['id', 'version', 'channel', 'is_active']);

        $activeVersion = $versions->firstWhere('is_active', true);

        $computersWithAgent = (clone $base)->whereNotNull('agent_version')->count();
        $onActive = 0;
        if ($activeVersion) {
            $onActive = (clone $base)->whereNotNull('agent_version')->where('agent_version', $activeVersion->version)->count();
        }

        return [
            'distribution' => $distribution,
            'versions' => $versions->map(fn ($v) => [
                'id' => $v->id,
                'version' => $v->version,
                'channel' => $v->channel,
                'is_active' => (bool) $v->is_active,
            ])->values()->all(),
            'active_version' => $activeVersion?->version,
            'computers_with_agent' => $computersWithAgent,
            'on_active_version' => $onActive,
            'outdated' => max(0, $computersWithAgent - $onActive),
        ];
    }

    protected function authorizationStats(): array
    {
        $now = now();
        $tokens = AuthorizationToken::query();

        $pending = (clone $tokens)->whereNull('used_at')->where('expires_at', '>', $now)->count();
        $used = (clone $tokens)->whereNotNull('used_at')->count();
        $expired = (clone $tokens)->whereNull('used_at')->where(function ($q) use ($now) {
            $q->where('expires_at', '<=', $now)->orWhereNull('expires_at');
        })->count();

        $recent = AuthorizationToken::with(['fileList:id,file_name', 'authorizableEmail:id,email'])
            ->latest('id')
            ->limit(8)
            ->get(['id', 'file_list_id', 'authorizable_email_id', 'expires_at', 'used_at', 'created_at'])
            ->map(fn ($t) => [
                'id' => $t->id,
                'file_name' => $t->fileList?->file_name,
                'email' => $t->authorizableEmail?->email,
                'expires_at' => optional($t->expires_at)->toIso8601String(),
                'used_at' => optional($t->used_at)->toIso8601String(),
                'created_at' => optional($t->created_at)->toIso8601String(),
            ])
            ->all();

        return [
            'pending' => $pending,
            'used' => $used,
            'expired' => $expired,
            'total' => (clone $tokens)->count(),
            'recent' => $recent,
            'file_lists' => FileList::where('status', 'active')->count(),
            'emails' => AuthorizableEmail::whereRaw('is_active = true')->count(),
        ];
    }

    protected function monitoringStats(): array
    {
        return [
            'monitored_files' => MonitoredFile::count(),
            'monitored_general' => MonitoredFile::whereRaw('general = 1')->count(),
            'file_lists' => FileList::count(),
            'rbf_hashes' => RbfFileHash::count(),
        ];
    }

    protected function systemStats(): array
    {
        $failedCommands24h = DB::table('commands')
            ->where('status', 'failed')
            ->where('updated_at', '>=', now()->subHours(24))
            ->count();

        $pendingCommands = DB::table('commands')->where('status', 'pending')->count();

        $audit = AuditLog::with('user:id,name')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'action', 'endpoint', 'user_id', 'created_at'])
            ->map(fn ($a) => [
                'action' => $a->action,
                'endpoint' => $a->endpoint,
                'user' => $a->user?->name ?? 'Sistema',
                'created_at' => optional($a->created_at)->toIso8601String(),
            ])
            ->all();

        $reportSyncs = [];
        foreach ([
            'vendedores_cache' => 'Vendedores',
            'metas_cache' => 'Metas',
            'cartera_abonos_cache' => 'Cartera Abonos',
        ] as $table => $label) {
            try {
                $count = DB::table($table)->count();
                $last = null;
                foreach (['updated_at', 'created_at'] as $col) {
                    try {
                        $last = DB::table($table)->max($col);
                        if ($last !== null) {
                            break;
                        }
                    } catch (\Throwable $e) {
                        continue;
                    }
                }
                $reportSyncs[] = [
                    'name' => $label,
                    'rows' => $count,
                    'updated_at' => $last ? \Illuminate\Support\Carbon::parse($last)->toIso8601String() : null,
                ];
            } catch (\Throwable $e) {
                Log::warning("DashboardStatsService: no se pudo consultar $table: ".$e->getMessage());
            }
        }

        return [
            'jobs_queued' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
            'commands_pending' => $pendingCommands,
            'commands_failed_24h' => $failedCommands24h,
            'audit' => $audit,
            'report_syncs' => $reportSyncs,
        ];
    }

    public static function touch(int $debounceSeconds = 5): void
    {
        if (! in_array(config('broadcasting.default'), ['redis', 'pusher', 'ably', 'reverb'], true)) {
            return;
        }

        try {
            $lockKey = 'dashboard:ws:last_broadcast';

            if (Cache::has($lockKey)) {
                return;
            }

            Cache::put($lockKey, true, $debounceSeconds);

            broadcast(new DashboardStatsUpdated);
        } catch (\Throwable $e) {
            Log::warning('DashboardStatsService::touch falló: '.$e->getMessage());
        }
    }
}
