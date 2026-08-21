<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use App\Services\ServerMetricsService;
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
}
