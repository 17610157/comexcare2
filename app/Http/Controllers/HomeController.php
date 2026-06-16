<?php

namespace App\Http\Controllers;

use App\Models\Computer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $totalComputers = Computer::count();
        $fiveMinutesAgo = now()->subMinutes(5);
        $onlineComputers = Computer::where('last_seen', '>=', $fiveMinutesAgo)->count();
        $offlineComputers = Computer::where(function ($q) use ($fiveMinutesAgo) {
            $q->where('last_seen', '<', $fiveMinutesAgo)->orWhereNull('last_seen');
        })->count();

        $plazaSummary = Computer::whereNotNull('plaza')
            ->select('plaza')
            ->selectRaw('count(*) as total')
            ->selectRaw('SUM(CASE WHEN last_seen >= ? THEN 1 ELSE 0 END) as online', [$fiveMinutesAgo])
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

        return view('home', compact(
            'totalComputers',
            'onlineComputers',
            'offlineComputers',
            'plazaSummary',
            'allAgentVersions',
            'allPvsiVersions',
            'agentLookup',
            'pvsiLookup'
        ));
    }
}
