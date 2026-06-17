<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        return view('home', $this->buildDashboardData(now()->subMinutes(5)));
    }

    public function stats(): JsonResponse
    {
        $threshold = now()->subMinutes(5);

        $totalComputers = Computer::count();
        $onlineComputers = Computer::where('last_seen', '>=', $threshold)->count();
        $offlineComputers = Computer::where(function ($q) use ($threshold) {
            $q->where('last_seen', '<', $threshold)->orWhereNull('last_seen');
        })->count();

        $plazaSummary = Computer::whereNotNull('plaza')
            ->select('plaza')
            ->selectRaw('count(*) as total')
            ->selectRaw('SUM(CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as online', [$threshold])
            ->groupBy('plaza')
            ->orderByDesc('total')
            ->get()
            ->map(function ($p) {
                return [
                    'plaza' => $p->plaza,
                    'total' => $p->total,
                    'online' => $p->online,
                    'offline' => $p->total - $p->online,
                    'percentage' => $p->total > 0 ? round(($p->online / $p->total) * 100, 1) : 0,
                ];
            });

        return response()->json([
            'total_computers' => $totalComputers,
            'online_computers' => $onlineComputers,
            'offline_computers' => $offlineComputers,
            'plaza_summary' => $plazaSummary,
        ]);
    }

    private function buildDashboardData(\DateTimeImmutable|string $threshold): array
    {
        $totalComputers = Computer::count();
        $onlineComputers = Computer::where('last_seen', '>=', $threshold)->count();
        $offlineComputers = Computer::where(function ($q) use ($threshold) {
            $q->where('last_seen', '<', $threshold)->orWhereNull('last_seen');
        })->count();

        $plazaSummary = Computer::whereNotNull('plaza')
            ->select('plaza')
            ->selectRaw('count(*) as total')
            ->selectRaw('SUM(CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as online', [$threshold])
            ->groupBy('plaza')
            ->orderByDesc('total')
            ->get();

        $allAgentVersions = Computer::whereNotNull('agent_version')
            ->whereNotNull('plaza')
            ->distinct()
            ->orderBy('agent_version')
            ->pluck('agent_version');

        $allPvsiVersions = Computer::whereNotNull('pvsi_version')
            ->whereNotNull('plaza')
            ->distinct()
            ->orderBy('pvsi_version')
            ->pluck('pvsi_version');

        $agentVersionsData = Computer::whereNotNull('agent_version')
            ->whereNotNull('plaza')
            ->select('plaza', 'agent_version', DB::raw('count(*) as total'))
            ->groupBy('plaza', 'agent_version')
            ->get();

        $pvsiVersionsData = Computer::whereNotNull('pvsi_version')
            ->whereNotNull('plaza')
            ->select('plaza', 'pvsi_version', DB::raw('count(*) as total'))
            ->groupBy('plaza', 'pvsi_version')
            ->get();

        $agentLookup = [];
        foreach ($agentVersionsData as $row) {
            $agentLookup[$row->plaza][$row->agent_version] = $row->total;
        }

        $pvsiLookup = [];
        foreach ($pvsiVersionsData as $row) {
            $pvsiLookup[$row->plaza][$row->pvsi_version] = $row->total;
        }

        return compact(
            'totalComputers',
            'onlineComputers',
            'offlineComputers',
            'plazaSummary',
            'allAgentVersions',
            'allPvsiVersions',
            'agentLookup',
            'pvsiLookup'
        );
    }
}
