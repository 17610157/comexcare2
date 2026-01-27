<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ReporteVendedoresController;
use App\Http\Controllers\ReporteVendedoresMatricialController;
use App\Http\Controllers\ReporteMetasVentasController;
use App\Http\Controllers\ReporteMetasMatricialController;
use App\Http\Controllers\ReporteComprasDirectoController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/home', function () {
    return view('home');
})->middleware('auth');

Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

Route::middleware('web')->prefix('reportes')->group(function () {
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

    // NUEVO REPORTE: Metas Matricial (sin permisos)
    Route::get('metas-matricial', [ReporteMetasMatricialController::class, 'index'])
        ->name('reportes.metas-matricial.index');

    Route::post('metas-matricial/export', [ReporteMetasMatricialController::class, 'exportExcel'])
        ->name('reportes.metas-matricial.export');

    Route::post('metas-matricial/export-pdf', [ReporteMetasMatricialController::class, 'exportPdf'])
        ->name('reportes.metas-matricial.export.pdf');

    // REPORTE: Compras Directo
    Route::get('compras-directo', [ReporteComprasDirectoController::class, 'index'])
        ->name('reportes.compras-directo');

    Route::post('compras-directo/export', [ReporteComprasDirectoController::class, 'export'])
        ->name('reportes.compras-directo.export');

    Route::post('compras-directo/export-pdf', [ReporteComprasDirectoController::class, 'exportPdf'])
        ->name('reportes.compras-directo.export.pdf');



});



Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('distributions', \App\Http\Controllers\DistributionsController::class);
    Route::resource('computers', \App\Http\Controllers\ComputersController::class)->only(['index', 'show', 'edit', 'update']);
    Route::resource('groups', \App\Http\Controllers\GroupsController::class);
    Route::resource('agent-versions', \App\Http\Controllers\AgentVersionsController::class);
});

// Agent API routes (no auth, no CSRF for agents)
Route::any('/api/register', [App\Http\Controllers\Api\AgentController::class, 'register']);
Route::any('/api/heartbeat', [App\Http\Controllers\Api\AgentController::class, 'heartbeat']);
Route::any('/api/commands/{id}', [App\Http\Controllers\Api\AgentController::class, 'getCommands']);
Route::any('/api/report', [App\Http\Controllers\Api\AgentController::class, 'report']);
Route::any('/api/download/{fileId}', [App\Http\Controllers\Api\AgentController::class, 'download']);
Route::any('/api/update/{version}', [App\Http\Controllers\Api\AgentController::class, 'checkUpdate']);
Route::any('/api/inventory', [App\Http\Controllers\Api\AgentController::class, 'inventory']);
Route::post('/api/heartbeat', [App\Http\Controllers\Api\AgentController::class, 'heartbeat'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/commands/{id}', [App\Http\Controllers\Api\AgentController::class, 'getCommands'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/report', [App\Http\Controllers\Api\AgentController::class, 'report'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/download/{fileId}', [App\Http\Controllers\Api\AgentController::class, 'download'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/update/{version}', [App\Http\Controllers\Api\AgentController::class, 'checkUpdate'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/inventory', [App\Http\Controllers\Api\AgentController::class, 'inventory'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Agent API routes (no auth, no CSRF for agents)
Route::get('/api/register', [App\Http\Controllers\Api\AgentController::class, 'register'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/heartbeat', [App\Http\Controllers\Api\AgentController::class, 'heartbeat'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/commands/{id}', [App\Http\Controllers\Api\AgentController::class, 'getCommands'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/report', [App\Http\Controllers\Api\AgentController::class, 'report'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/download/{fileId}', [App\Http\Controllers\Api\AgentController::class, 'download'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/update/{version}', [App\Http\Controllers\Api\AgentController::class, 'checkUpdate'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/inventory', [App\Http\Controllers\Api\AgentController::class, 'inventory'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);