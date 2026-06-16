<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ResurtidoAgentController;
use App\Http\Controllers\Api\ValeController;
use App\Http\Controllers\MetricsController;
use App\Models\Computer;
use Carbon\Carbon;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Agent API routes (without CSRF)
Route::middleware('api')->group(function () {
    Route::post('/register', [AgentController::class, 'register']);
    Route::post('/heartbeat', [AgentController::class, 'heartbeat']);
    Route::get('/commands/{id}', [AgentController::class, 'getCommands']);
    Route::post('/report', [AgentController::class, 'report']);
    Route::post('/agent/report', [AgentController::class, 'report']);
    Route::get('/download/{fileId}', [AgentController::class, 'download']);
    Route::get('/update/{version}', [AgentController::class, 'checkUpdate']);
    Route::get('/check-update/{version}', [AgentController::class, 'checkUpdate']);
    Route::post('/inventory', [AgentController::class, 'inventory']);
    Route::post('/logs', [AgentController::class, 'logs']);
    Route::patch('/pvsi-update', [AgentController::class, 'pvsiUpdate']);
    Route::get('/computer/{computer_id}/config', [AgentController::class, 'getComputerConfig']);

    // Resurtido Agent API
    Route::post('/resurtido/register', [ResurtidoAgentController::class, 'register']);
    Route::post('/resurtido/heartbeat', [ResurtidoAgentController::class, 'heartbeat']);
    Route::get('/resurtido/check-update', [ResurtidoAgentController::class, 'checkUpdate']);
    Route::get('/resurtido/commands/{computerId}', [ResurtidoAgentController::class, 'getCommands']);
    Route::post('/resurtido/report', [ResurtidoAgentController::class, 'report']);

    Route::post('/getComputerId', function (Request $request) {
        $mac = $request->input('mac_address');
        $computer = Computer::where('mac_address', $mac)->first();
        if ($computer) {
            return response()->json(['computer_id' => $computer->id]);
        }

        return response()->json(['error' => 'Not found'], 404);
    });

    Route::get('/metrics', [MetricsController::class, 'index']);
    Route::get('/health', [MetricsController::class, 'health']);

    Route::get('/vales', [ValeController::class, 'index']);
    Route::get('/vales/{id}', [ValeController::class, 'show']);
    Route::post('/vales', [ValeController::class, 'store']);
    Route::post('/vales/batch', [ValeController::class, 'storeBatch']);
    Route::put('/vales/{id}', [ValeController::class, 'update']);
    Route::patch('/vales/{id}', [ValeController::class, 'update']);
    Route::delete('/vales/{id}', [ValeController::class, 'destroy']);
    Route::post('/vales/reset-sync', [ValeController::class, 'resetSync'])->withoutMiddleware([VerifyCsrfToken::class]);

    // Endpoint para verificar actividad real de equipos (usando logs)
    Route::get('/computers/online-status', function () {
        $logPath = storage_path('logs/laravel.log');
        if (! file_exists($logPath)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        $logContent = file_get_contents($logPath);
        $today = now()->format('Y-m-d');

        // Buscar TODAS las líneas con [YYYY-MM-DD HH:MM:SS] y computer_id
        preg_match_all('/\[('.$today.' \d{2}:\d{2}:\d{2})\].*?"computer_id":"(\d+)"/', $logContent, $matches, PREG_SET_ORDER);

        $computerLastSeen = [];
        foreach ($matches as $match) {
            $timestamp = $match[1];
            $computerId = $match[2];
            if (! isset($computerLastSeen[$computerId]) || $timestamp > $computerLastSeen[$computerId]) {
                $computerLastSeen[$computerId] = $timestamp;
            }
        }

        $online = [];
        $now = now();
        foreach ($computerLastSeen as $id => $ts) {
            $computer = Computer::find($id);
            $last = Carbon::createFromTimeString($ts);
            $minutesAgo = $now->diffInMinutes($last);
            $online[] = [
                'computer_id' => (int) $id,
                'name' => $computer ? $computer->computer_name : 'SIN_NOMBRE',
                'last_seen' => $ts,
                'minutes_ago' => $minutesAgo,
                'is_online' => $minutesAgo < 10,
                'is_active_today' => $minutesAgo < 1440, // 24 horas
            ];
        }

        // Ordenar por más reciente
        usort($online, function ($a, $b) {
            return $a['minutes_ago'] <=> $b['minutes_ago'];
        });

        return response()->json([
            'total_active_today' => count($computerLastSeen),
            'online_now' => collect($online)->where('is_online', true)->count(),
            'active_today' => collect($online)->where('is_active_today', true)->count(),
            'computers' => $online,
        ]);
    });
});
