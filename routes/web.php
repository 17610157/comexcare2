<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteVendedoresController;
use App\HttP\Controllers\ReporteVendedoresMatricialController;
use App\Http\Controllers\ReporteMetasVentasController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return view('home');
});

Route::prefix('reportes')->group(function () {
    Route::get('vendedores', [ReporteVendedoresController::class, 'index'])
        ->name('reportes.vendedores');
    
    Route::post('vendedores/export', [ReporteVendedoresController::class, 'export'])
        ->name('reportes.vendedores.export');
    
    Route::post('vendedores/export-csv', [ReporteVendedoresController::class, 'exportCsv'])
        ->name('reportes.vendedores.export.csv');
    
    Route::post('vendedores/export-pdf', [ReporteVendedoresController::class, 'exportPdf'])
        ->name('reportes.vendedores.export.pdf');
    Route::get('vendedores-matricial', [ReporteVendedoresMatricialController::class, 'index'])
        ->name('reportes.vendedores.matricial');
    
    // Exportar Excel
    Route::post('vendedores-matricial/export-excel', [ReporteVendedoresMatricialController::class, 'exportExcel'])
        ->name('reportes.vendedores.matricial.export.excel');
    
    // Exportar PDF
    Route::post('vendedores-matricial/export-pdf', [ReporteVendedoresMatricialController::class, 'exportPdf'])
        ->name('reportes.vendedores.matricial.export.pdf');
    
    // Exportar CSV
    Route::post('vendedores-matricial/export-csv', [ReporteVendedoresMatricialController::class, 'exportCsv'])
        ->name('reportes.vendedores.matricial.export.csv');
     
    Route::get('metas-ventas', [ReporteMetasVentasController::class, 'index'])->name('reportes.metas-ventas');
    Route::post('metas-ventas/export', [ReporteMetasVentasController::class, 'export'])->name('reportes.metas-ventas.export');
    Route::post('metas-ventas/export/pdf', [ReporteMetasVentasController::class, 'exportPdf'])->name('reportes.metas-ventas.export.pdf');
    Route::post('metas-ventas/export/csv', [ReporteMetasVentasController::class, 'exportCsv'])->name('reportes.metas-ventas.export.csv');
});