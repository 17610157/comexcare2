<?php

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReporteComprasDirectoController;
use App\Http\Controllers\ReporteMetasMatricialController;
use App\Http\Controllers\ReporteMetasVentasController;
use App\Http\Controllers\Reportes\CarteraAbonosController;
use App\Http\Controllers\Reportes\NotasCompletasController;
use App\Http\Controllers\Reportes\ReporteRedencionesClubController;
use App\Http\Controllers\ReporteVendedoresController;
use App\Http\Controllers\ReporteVendedoresMatricialController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('home') : redirect()->route('login');
});

Route::get('/home', function () {
    return view('home');
})->middleware('auth');

Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Rutas de usuarios (protegidas por auth)
Route::middleware(['auth', 'web'])->prefix('admin/usuarios')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/data', [UserController::class, 'data'])->name('usuarios.data');
    Route::post('/', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/{user}', [UserController::class, 'show'])->name('usuarios.show');
    Route::put('/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');
});

// Rutas de roles (protegidas por auth)
Route::middleware(['auth', 'web'])->prefix('admin/roles')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/data', [RoleController::class, 'data'])->name('roles.data');
    Route::post('/', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/permissions', [RoleController::class, 'allPermissions'])->name('roles.permissions');
    Route::get('/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::put('/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
});

// Rutas de permisos (protegidas por auth)
Route::middleware(['auth', 'web'])->prefix('admin/permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('permissions.index')->middleware('can:admin.permissions.ver');
    Route::get('/data', [PermissionController::class, 'data'])->name('permissions.data')->middleware('can:admin.permissions.ver');
    Route::post('/', [PermissionController::class, 'store'])->name('permissions.store')->middleware('can:admin.permissions.crear');
    Route::put('/{permission}', [PermissionController::class, 'update'])->name('permissions.update')->middleware('can:admin.permissions.editar');
    Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy')->middleware('can:admin.permissions.eliminar');
    Route::post('/sync', [PermissionController::class, 'sync'])->name('permissions.sync')->middleware('can:admin.permissions.ver');
});

// Rutas de reportes
Route::middleware(['auth', 'web'])->prefix('reportes')->group(function () {
    Route::get('/', function () {
        return redirect()->route('reportes.vendedores');
    })->name('reportes.index')->middleware('can:reportes.ver');
    
    Route::get('vendedores', [ReporteVendedoresController::class, 'index'])
        ->name('reportes.vendedores')->middleware('can:reportes.vendedores.ver');

    Route::get('vendedores/data', [ReporteVendedoresController::class, 'data'])
        ->name('reportes.vendedores.data')->middleware('can:reportes.vendedores.ver');

    Route::post('vendedores/export', [ReporteVendedoresController::class, 'export'])
        ->name('reportes.vendedores.export')->middleware('can:reportes.vendedores.editar');

    Route::post('vendedores/export-csv', [ReporteVendedoresController::class, 'exportCsv'])
        ->name('reportes.vendedores.export.csv')->middleware('can:reportes.vendedores.editar');

    Route::post('vendedores/export-pdf', [ReporteVendedoresController::class, 'exportPdf'])
        ->name('reportes.vendedores.export.pdf')->middleware('can:reportes.vendedores.editar');

    Route::post('vendedores/sync', [ReporteVendedoresController::class, 'sync'])
        ->name('reportes.vendedores.sync')->middleware('can:reportes.vendedores.editar');

    Route::get('vendedores-matricial', [ReporteVendedoresMatricialController::class, 'index'])
        ->name('reportes.vendedores.matricial')->middleware('can:reportes.vendedores.matricial.ver');

    // Exportar Excel
    Route::post('vendedores-matricial/export-excel', [ReporteVendedoresMatricialController::class, 'exportExcel'])
        ->name('reportes.vendedores.matricial.export.excel')->middleware('can:reportes.vendedores.matricial.editar');

    // Exportar PDF
    Route::post('vendedores-matricial/export-pdf', [ReporteVendedoresMatricialController::class, 'exportPdf'])
        ->name('reportes.vendedores.matricial.export.pdf')->middleware('can:reportes.vendedores.matricial.editar');

    // Exportar CSV
    Route::post('vendedores-matricial/export-csv', [ReporteVendedoresMatricialController::class, 'exportCsv'])
        ->name('reportes.vendedores.matricial.export.csv')->middleware('can:reportes.vendedores.matricial.editar');

    Route::get('metas-ventas', [ReporteMetasVentasController::class, 'index'])->name('reportes.metas-ventas')->middleware('can:reportes.metas-ventas.ver');
    Route::post('metas-ventas/export', [ReporteMetasVentasController::class, 'export'])->name('reportes.metas-ventas.export')->middleware('can:reportes.metas-ventas.editar');
    Route::post('metas-ventas/export/pdf', [ReporteMetasVentasController::class, 'exportPdf'])->name('reportes.metas-ventas.export.pdf')->middleware('can:reportes.metas-ventas.editar');
    Route::post('metas-ventas/export/csv', [ReporteMetasVentasController::class, 'exportCsv'])->name('reportes.metas-ventas.export.csv')->middleware('can:reportes.metas-ventas.editar');

    // NUEVO REPORTE: Metas Matricial
    Route::get('metas-matricial', [ReporteMetasMatricialController::class, 'index'])
        ->name('reportes.metas-matricial.index')->middleware('can:reportes.metas-matricial.ver');

    // API REST para consulta personalizada de metas
    Route::post('metas/consultar-datos', [ReporteMetasVentasController::class, 'consultarDatosPersonalizados'])
        ->name('reportes.metas.consultar_datos')->middleware('can:reportes.metas-ventas.ver');

    Route::post('metas-matricial/export', [ReporteMetasMatricialController::class, 'exportExcel'])
        ->name('reportes.metas-matricial.export')->middleware('can:reportes.metas-matricial.editar');

    Route::post('metas-matricial/export-pdf', [ReporteMetasMatricialController::class, 'exportPdf'])
        ->name('reportes.metas-matricial.export.pdf')->middleware('can:reportes.metas-matricial.editar');

    // Cartera Abonos - Reporte (Mes Anterior)
    Route::get('cartera-abonos', [CarteraAbonosController::class, 'index'])
        ->name('reportes.cartera-abonos.index')->middleware('can:reportes.cartera-abonos.ver');
    Route::get('cartera-abonos/data', [CarteraAbonosController::class, 'data'])
        ->name('reportes.cartera-abonos.data')->middleware('can:reportes.cartera-abonos.ver');
    // Export PDF for Cartera Abonos with filters
    Route::get('cartera-abonos/pdf', [CarteraAbonosController::class, 'pdf'])
        ->name('reportes.cartera-abonos.pdf')->middleware('can:reportes.cartera-abonos.editar');
    // Export Excel for Cartera Abonos with filters
    Route::post('cartera-abonos/export-excel', [CarteraAbonosController::class, 'exportExcel'])
        ->name('reportes.cartera-abonos.export.excel')->middleware('can:reportes.cartera-abonos.editar');
    // Export CSV for Cartera Abonos with filters
    Route::post('cartera-abonos/export-csv', [CarteraAbonosController::class, 'exportCsv'])
        ->name('reportes.cartera-abonos.export.csv')->middleware('can:reportes.cartera-abonos.editar');
    // Sync Cartera Abonos Cache
    Route::post('cartera-abonos/sync', [CarteraAbonosController::class, 'sync'])
        ->middleware('can:reportes.cartera-abonos.sincronizar')
        ->name('reportes.cartera-abonos.sync');

    // Notas Completas - Reporte
    Route::get('notas-completas', [NotasCompletasController::class, 'index'])
        ->name('reportes.notas-completas.index')->middleware('can:reportes.notas-completas.ver');
    Route::get('notas-completas/data', [NotasCompletasController::class, 'data'])
        ->name('reportes.notas-completas.data')->middleware('can:reportes.notas-completas.ver');
    Route::post('notas-completas/export-excel', [NotasCompletasController::class, 'exportExcel'])
        ->name('reportes.notas-completas.export.excel')->middleware('can:reportes.notas-completas.editar');
    Route::post('notas-completas/export-csv', [NotasCompletasController::class, 'exportCsv'])
        ->name('reportes.notas-completas.export.csv')->middleware('can:reportes.notas-completas.editar');
    Route::post('notas-completas/sync', [NotasCompletasController::class, 'sync'])
        ->middleware('can:reportes.notas-completas.sincronizar')
        ->name('reportes.notas-completas.sync');

    // Listas dinámicas para filtros (removidas: no se usan patrones de listas externas)

    // REPORTE: Compras Directo
    Route::get('compras-directo', [ReporteComprasDirectoController::class, 'index'])
        ->name('reportes.compras-directo')->middleware('can:reportes.compras-directo.ver');
    Route::get('compras-directo/data', [ReporteComprasDirectoController::class, 'data'])
        ->name('reportes.compras-directo.data')->middleware('can:reportes.compras-directo.ver');
    Route::post('compras-directo/export', [ReporteComprasDirectoController::class, 'export'])
        ->name('reportes.compras-directo.export')->middleware('can:reportes.compras-directo.editar');
    Route::post('compras-directo/export-excel', [ReporteComprasDirectoController::class, 'exportExcel'])
        ->name('reportes.compras-directo.export.excel')->middleware('can:reportes.compras-directo.editar');
    Route::post('compras-directo/export-csv', [ReporteComprasDirectoController::class, 'exportCsv'])
        ->name('reportes.compras-directo.export.csv')->middleware('can:reportes.compras-directo.editar');
    Route::post('compras-directo/export-pdf', [ReporteComprasDirectoController::class, 'exportPdf'])
        ->name('reportes.compras-directo.export.pdf')->middleware('can:reportes.compras-directo.editar');
    Route::post('compras-directo/sync', [ReporteComprasDirectoController::class, 'sync'])
        ->middleware('can:reportes.compras-directo.sincronizar')
        ->name('reportes.compras-directo.sync');

    // Redenciones Club Comex
    Route::get('redenciones-club', [ReporteRedencionesClubController::class, 'index'])
        ->name('reportes.redenciones_club.index')->middleware('can:reportes.redenciones_club.ver');
    Route::post('redenciones-club/data', [ReporteRedencionesClubController::class, 'data'])
        ->name('reportes.redenciones_club.data')->middleware('can:reportes.redenciones_club.ver');
    Route::post('redenciones-club/export-excel', [ReporteRedencionesClubController::class, 'exportExcel'])
        ->name('reportes.redenciones_club.export.excel')->middleware('can:reportes.redenciones_club.editar');
    Route::post('redenciones-club/export-csv', [ReporteRedencionesClubController::class, 'exportCsv'])
        ->name('reportes.redenciones_club.export.csv')->middleware('can:reportes.redenciones_club.editar');
    Route::post('redenciones-club/sync', [ReporteRedencionesClubController::class, 'sync'])
        ->middleware('can:reportes.redenciones_club.sincronizar')
        ->name('reportes.redenciones_club.sync');

});

Route::middleware(['auth'])->prefix('admin')->name('admin.')->middleware('can:admin.ver')->group(function () {
    Route::resource('distributions', \App\Http\Controllers\DistributionsController::class);
    Route::resource('computers', \App\Http\Controllers\ComputersController::class)->only(['index', 'show', 'edit', 'update']);
    Route::resource('groups', \App\Http\Controllers\GroupsController::class);
    Route::resource('agent-versions', \App\Http\Controllers\AgentVersionsController::class);

    // User Plaza Tienda - Solo super_admin
    Route::middleware('can:admin.usuarios.ver')->group(function () {
        Route::get('user-plaza-tienda', [\App\Http\Controllers\UserPlazaTiendaController::class, 'index'])->name('user-plaza-tienda.index');
        Route::get('user-plaza-tienda/{user}/edit', [\App\Http\Controllers\UserPlazaTiendaController::class, 'edit'])->name('user-plaza-tienda.edit');
        Route::get('user-plaza-tienda/tiendas', [\App\Http\Controllers\UserPlazaTiendaController::class, 'getTiendas'])->name('user-plaza-tienda.tiendas');
    });

    Route::middleware('can:admin.usuarios.editar')->group(function () {
        Route::put('user-plaza-tienda/{user}', [\App\Http\Controllers\UserPlazaTiendaController::class, 'update'])->name('user-plaza-tienda.update');
    });

    // Tiendas - Solo super_admin
    Route::middleware('can:tiendas.ver')->group(function () {
        Route::get('tiendas', [\App\Http\Controllers\TiendasController::class, 'index'])->name('tiendas.index');
        Route::get('tiendas/data', [\App\Http\Controllers\TiendasController::class, 'data'])->name('tiendas.data');
        Route::get('tiendas/{tienda}', [\App\Http\Controllers\TiendasController::class, 'show'])->name('tiendas.show');
    });

    Route::middleware('can:tiendas.crear')->group(function () {
        Route::post('tiendas', [\App\Http\Controllers\TiendasController::class, 'store'])->name('tiendas.store');
    });

    Route::middleware('can:tiendas.editar')->group(function () {
        Route::put('tiendas/{tienda}', [\App\Http\Controllers\TiendasController::class, 'update'])->name('tiendas.update');
    });

    Route::middleware('can:tiendas.eliminar')->group(function () {
        Route::delete('tiendas/{tienda}', [\App\Http\Controllers\TiendasController::class, 'destroy'])->name('tiendas.destroy');
    });

    Route::middleware('can:tiendas.crear')->group(function () {
        Route::post('tiendas', [\App\Http\Controllers\TiendasController::class, 'store'])->name('tiendas.store');
    });

    Route::middleware('can:tiendas.editar')->group(function () {
        Route::put('tiendas/{tienda}', [\App\Http\Controllers\TiendasController::class, 'update'])->name('tiendas.update');
    });

    Route::middleware('can:tiendas.eliminar')->group(function () {
        Route::delete('tiendas/{tienda}', [\App\Http\Controllers\TiendasController::class, 'destroy'])->name('tiendas.destroy');
    });
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

// Metas Mensual Import routes (protected by auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/metas-mensual', [App\Http\Controllers\MetasMensualController::class, 'index'])->name('metas.index')->middleware('can:metas.ver');
    Route::post('/metas-mensual/import', [App\Http\Controllers\MetasMensualController::class, 'import'])->name('metas.import')->middleware('can:metas.importar');
    // CRUD for metas_mensual
    Route::post('/metas-mensual/store', [App\Http\Controllers\MetasMensualController::class, 'store'])->name('metas.store')->middleware('can:metas.crear');
    Route::post('/metas-mensual/update', [App\Http\Controllers\MetasMensualController::class, 'update'])->name('metas.update')->middleware('can:metas.editar');
    Route::post('/metas-mensual/delete', [App\Http\Controllers\MetasMensualController::class, 'destroy'])->name('metas.destroy')->middleware('can:metas.eliminar');
    Route::post('/metas-mensual/generar', [App\Http\Controllers\MetasMensualController::class, 'generarMetas'])->name('metas.generar')->middleware('can:metas.crear');
});

// Agent API routes (no auth, no CSRF for agents)
Route::get('/api/register', [App\Http\Controllers\Api\AgentController::class, 'register'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/heartbeat', [App\Http\Controllers\Api\AgentController::class, 'heartbeat'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/commands/{id}', [App\Http\Controllers\Api\AgentController::class, 'getCommands'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/report', [App\Http\Controllers\Api\AgentController::class, 'report'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/download/{fileId}', [App\Http\Controllers\Api\AgentController::class, 'download'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/update/{version}', [App\Http\Controllers\Api\AgentController::class, 'checkUpdate'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/api/inventory', [App\Http\Controllers\Api\AgentController::class, 'inventory'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/api/dias-periodo', [App\Http\Controllers\MetasMensualController::class, 'getDiasPeriodo'])->middleware('can:metas.ver');
Route::post('/metas-dias/generate', [App\Http\Controllers\MetasMensualController::class, 'generateDias'])->name('metas_dias.generate')->middleware('can:metas.crear');
Route::get('/metas-mensual/performance-test', [App\Http\Controllers\MetasMensualController::class, 'performanceTest'])
    ->name('metas.performance.test')->middleware('can:metas.ver');
