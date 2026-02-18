<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reportes\CarteraAbonosRealtimeController;

// Rutas para Cartera Abonos Tiempo Real - Tablas Materializadas
Route::prefix('reportes')->name('reportes.')->group(function() {
    
    // Ruta principal tiempo real
    Route::get('/cartera-abonos-realtime', [CarteraAbonosRealtimeController::class, 'index'])
         ->name('cartera-abonos-realtime.index');
    
    // Endpoint de datos tiempo real
    Route::get('/cartera-abonos-realtime/data', [CarteraAbonosRealtimeController::class, 'data'])
         ->name('cartera-abonos-realtime.data');
    
    // Endpoint de streaming (Server-Sent Events)
    Route::get('/cartera-abonos-realtime/stream', [CarteraAbonosRealtimeController::class, 'stream'])
         ->name('cartera-abonos-realtime.stream');
    
    // Endpoint de estadísticas tiempo real
    Route::get('/cartera-abonos-realtime/stats', [CarteraAbonosRealtimeController::class, 'stats'])
         ->name('cartera-abonos-realtime.stats');
    
    // Health check endpoint
    Route::get('/cartera-abonos-realtime/health', [CarteraAbonosRealtimeController::class, 'health'])
         ->name('cartera-abonos-realtime.health');
    
    // Forzar sincronización
    Route::post('/cartera-abonos-realtime/force-sync', [CarteraAbonosRealtimeController::class, 'forceSync'])
         ->name('cartera-abonos-realtime.force-sync');
    
    // Exportaciones (reutilizar controladores existentes)
    Route::get('/cartera-abonos-realtime/pdf', [CarteraAbonosRealtimeController::class, 'pdf'])
         ->name('cartera-abonos-realtime.pdf');
    
    Route::post('/cartera-abonos-realtime/export-excel', [CarteraAbonosRealtimeController::class, 'exportExcel'])
         ->name('cartera-abonos-realtime.export-excel');
    
    Route::post('/cartera-abonos-realtime/export-csv', [CarteraAbonosRealtimeController::class, 'exportCsv'])
         ->name('cartera-abonos-realtime.export-csv');
});

// Middleware de caché para endpoints de solo lectura
Route::middleware(['throttle:60,1'])->group(function() {
    // Aplicar rate limiting a endpoints públicos
    Route::get('/reportes/cartera-abonos-realtime/data', [CarteraAbonosRealtimeController::class, 'data']);
    Route::get('/reportes/cartera-abonos-realtime/stats', [CarteraAbonosRealtimeController::class, 'stats']);
    Route::get('/reportes/cartera-abonos-realtime/health', [CarteraAbonosRealtimeController::class, 'health']);
});

// Middleware de autenticación para endpoints sensibles
Route::middleware(['auth'])->group(function() {
    Route::post('/reportes/cartera-abonos-realtime/force-sync', [CarteraAbonosRealtimeController::class, 'forceSync']);
});