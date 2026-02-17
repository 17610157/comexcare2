<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reportes\CarteraAbonosOptimizedController;

// Rutas para Cartera Abonos Optimizado - Tiempo Real
Route::prefix('reportes')->name('reportes.')->group(function() {
    
    // Ruta principal optimizada
    Route::get('/cartera-abonos-optimized', [CarteraAbonosOptimizedController::class, 'index'])
         ->name('cartera-abonos-optimized.index');
    
    // Endpoint de datos optimizado
    Route::get('/cartera-abonos-optimized/data', [CarteraAbonosOptimizedController::class, 'data'])
         ->name('cartera-abonos-optimized.data');
    
    // Endpoint de streaming para datasets grandes
    Route::get('/cartera-abonos-optimized/stream', [CarteraAbonosOptimizedController::class, 'dataStream'])
         ->name('cartera-abonos-optimized.stream');
    
    // Endpoint de estadísticas en tiempo real
    Route::get('/cartera-abonos-optimized/stats', [CarteraAbonosOptimizedController::class, 'stats'])
         ->name('cartera-abonos-optimized.stats');
    
    // Invalidación de caché
    Route::post('/cartera-abonos-optimized/invalidate-cache', [CarteraAbonosOptimizedController::class, 'invalidateCache'])
         ->name('cartera-abonos-optimized.invalidate-cache');
    
    // Exportaciones optimizadas
    Route::get('/cartera-abonos-optimized/pdf', [CarteraAbonosOptimizedController::class, 'pdf'])
         ->name('cartera-abonos-optimized.pdf');
    
    Route::post('/cartera-abonos-optimized/export-excel', [CarteraAbonosOptimizedController::class, 'exportExcel'])
         ->name('cartera-abonos-optimized.export-excel');
    
    Route::post('/cartera-abonos-optimized/export-csv', [CarteraAbonosOptimizedController::class, 'exportCsv'])
         ->name('cartera-abonos-optimized.export-csv');
    
    // Ruta de exportación unificada
    Route::get('/cartera-abonos-optimized/export/{format}', [CarteraAbonosOptimizedController::class, 'export'])
         ->name('cartera-abonos-optimized.export')
         ->where('format', 'excel|csv|pdf');
});

// Middleware de caché optimizado para reportes
Route::middleware(['ReporteCacheMiddleware:cartera_abonos'])->group(function() {
    // Aplicar caché a las rutas de datos
    Route::get('/reportes/cartera-abonos-optimized/data', [CarteraAbonosOptimizedController::class, 'data']);
    Route::get('/reportes/cartera-abonos-optimized/stats', [CarteraAbonosOptimizedController::class, 'stats']);
});