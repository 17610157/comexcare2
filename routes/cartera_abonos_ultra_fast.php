<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reportes\CarteraAbonosUltraFastController;

// Rutas para Cartera Abonos Ultra-Fast (500 usuarios)
Route::prefix('reportes')->name('reportes.')->group(function() {
    
    // Ruta principal Ultra-Fast
    Route::get('/cartera-abonos-ultra-fast', [CarteraAbonosUltraFastController::class, 'index'])
         ->name('cartera-abonos-ultra-fast.index');
    
    // Endpoint de pre-carga (única llamada)
    Route::get('/cartera-abonos-ultra-fast/preload', [CarteraAbonosUltraFastController::class, 'preload'])
         ->name('cartera-abonos-ultra-fast.preload')
         ->middleware('throttle:100,1'); // 100 requests por minuto
    
    // Forzar pre-carga (admin)
    Route::post('/cartera-abonos-ultra-fast/force-preload', [CarteraAbonosUltraFastController::class, 'forcePreload'])
         ->name('cartera-abonos-ultra-fast.force-preload')
         ->middleware(['auth', 'throttle:10,1']); // 10 requests por minuto para admin
    
    // Estado del sistema
    Route::get('/cartera-abonos-ultra-fast/status', [CarteraAbonosUltraFastController::class, 'status'])
         ->name('cartera-abonos-ultra-fast.status')
         ->middleware('throttle:200,1'); // 200 requests por minuto
    
    // Health check
    Route::get('/cartera-abonos-ultra-fast/health', [CarteraAbonosUltraFastController::class, 'health'])
         ->name('cartera-abonos-ultra-fast.health')
         ->middleware('throttle:300,1'); // 300 requests por minuto
    
    // Actualización incremental (background)
    Route::get('/cartera-abonos-ultra-fast/incremental-update', [CarteraAbonosUltraFastController::class, 'incrementalUpdate'])
         ->name('cartera-abonos-ultra-fast.incremental-update')
         ->middleware(['auth', 'throttle:5,1']); // 5 requests por minuto
    
    // Exportación de datos (usando pre-carga)
    Route::post('/cartera-abonos-ultra-fast/export-data', [CarteraAbonosUltraFastController::class, 'exportData'])
         ->name('cartera-abonos-ultra-fast.export-data')
         ->middleware(['auth', 'throttle:20,1']); // 20 requests por minuto
    
    // Endpoint de búsqueda (compatibilidad - no se usa realmente)
    Route::get('/cartera-abonos-ultra-fast/search', [CarteraAbonosUltraFastController::class, 'search'])
         ->name('cartera-abonos-ultra-fast.search')
         ->middleware('throttle:500,1'); // 500 requests por minuto (máximo)
});

// API endpoints para integración externa
Route::prefix('api/v1')->name('api.')->middleware(['throttle:1000,1'])->group(function() {
    
    // API Cartera Abonos Ultra-Fast
    Route::prefix('cartera-abonos-ultra-fast')->group(function() {
        
        // Pre-carga API
        Route::get('/preload', [CarteraAbonosUltraFastController::class, 'apiPreload'])
             ->name('cartera-abonos-ultra-fast.api.preload');
        
        // Estadísticas API
        Route::get('/stats', [CarteraAbonosUltraFastController::class, 'apiStats'])
             ->name('cartera-abonos-ultra-fast.api.stats');
        
        // Health check API
        Route::get('/health', [CarteraAbonosUltraFastController::class, 'apiHealth'])
             ->name('cartera-abonos-ultra-fast.api.health');
        
        // Exportación API
        Route::post('/export', [CarteraAbonosUltraFastController::class, 'apiExport'])
             ->name('cartera-abonos-ultra-fast.api.export')
             ->middleware('auth');
    });
});

// Middleware de rate limiting específico para 500 usuarios
Route::middleware(['throttle:1000,1'])->group(function() {
    // Endpoints que pueden recibir alto tráfico
    Route::get('/reportes/cartera-abonos-ultra-fast/preload', [CarteraAbonosUltraFastController::class, 'preload']);
    Route::get('/reportes/cartera-abonos-ultra-fast/health', [CarteraAbonosUltraFastController::class, 'health']);
});

// Middleware de autenticación para endpoints sensibles
Route::middleware(['auth'])->group(function() {
    Route::post('/reportes/cartera-abonos-ultra-fast/force-preload', [CarteraAbonosUltraFastController::class, 'forcePreload']);
    Route::get('/reportes/cartera-abonos-ultra-fast/incremental-update', [CarteraAbonosUltraFastController::class, 'incrementalUpdate']);
    Route::post('/reportes/cartera-abonos-ultra-fast/export-data', [CarteraAbonosUltraFastController::class, 'exportData']);
});