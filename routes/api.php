<?php

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
    Route::post('/register', [App\Http\Controllers\Api\AgentController::class, 'register']);
    Route::post('/heartbeat', [App\Http\Controllers\Api\AgentController::class, 'heartbeat']);
    Route::get('/commands/{id}', [App\Http\Controllers\Api\AgentController::class, 'getCommands']);
    Route::post('/report', [App\Http\Controllers\Api\AgentController::class, 'report']);
    Route::post('/agent/report', [App\Http\Controllers\Api\AgentController::class, 'report']);
    Route::get('/download/{fileId}', [App\Http\Controllers\Api\AgentController::class, 'download']);
    Route::get('/update/{version}', [App\Http\Controllers\Api\AgentController::class, 'checkUpdate']);
    Route::get('/check-update/{version}', [App\Http\Controllers\Api\AgentController::class, 'checkUpdate']);
    Route::post('/inventory', [App\Http\Controllers\Api\AgentController::class, 'inventory']);
    Route::post('/logs', [App\Http\Controllers\Api\AgentController::class, 'logs']);
    Route::patch('/pvsi-update', [App\Http\Controllers\Api\AgentController::class, 'pvsiUpdate']);
    Route::get('/computer/{computer_id}/config', [App\Http\Controllers\Api\AgentController::class, 'getComputerConfig']);
    Route::post('/getComputerId', function (Request $request) {
        $mac = $request->input('mac_address');
        $computer = \App\Models\Computer::where('mac_address', $mac)->first();
        if ($computer) {
            return response()->json(['computer_id' => $computer->id]);
        }

        return response()->json(['error' => 'Not found'], 404);
    });

    Route::get('/metrics', [App\Http\Controllers\MetricsController::class, 'index']);
    Route::get('/health', [App\Http\Controllers\MetricsController::class, 'health']);

    Route::get('/vales', [App\Http\Controllers\Api\ValeController::class, 'index']);
    Route::get('/vales/{id}', [App\Http\Controllers\Api\ValeController::class, 'show']);
    Route::post('/vales', [App\Http\Controllers\Api\ValeController::class, 'store']);
    Route::post('/vales/batch', [App\Http\Controllers\Api\ValeController::class, 'storeBatch']);
    Route::put('/vales/{id}', [App\Http\Controllers\Api\ValeController::class, 'update']);
    Route::patch('/vales/{id}', [App\Http\Controllers\Api\ValeController::class, 'update']);
    Route::delete('/vales/{id}', [App\Http\Controllers\Api\ValeController::class, 'destroy']);
});
