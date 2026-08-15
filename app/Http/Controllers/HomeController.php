<?php

namespace App\Http\Controllers;

use App\Services\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
}
