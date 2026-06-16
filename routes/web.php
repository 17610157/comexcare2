<?php

use App\Http\Controllers\AgentVersionsController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ComputersController;
use App\Http\Controllers\DistributionsController;
use App\Http\Controllers\FileReceptionController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetasMensualController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReporteComprasDirectoController;
use App\Http\Controllers\ReporteDbfFilesController;
use App\Http\Controllers\ReporteDesgloseController;
use App\Http\Controllers\ReporteMetasMatricialController;
use App\Http\Controllers\ReporteMetasVentasController;
use App\Http\Controllers\Reportes\CarteraAbonosController;
use App\Http\Controllers\Reportes\ClubComexController;
use App\Http\Controllers\Reportes\NotasCompletasController;
use App\Http\Controllers\Reportes\ReporteRedencionesClubController;
use App\Http\Controllers\ReporteValesController;
use App\Http\Controllers\ReporteVendedoresB2bController;
use App\Http\Controllers\ReporteVendedoresController;
use App\Http\Controllers\ReporteVendedoresMatricialController;
use App\Http\Controllers\ResurtidoAgentVersionsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TiendasController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPlazaTiendaController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('home') : redirect()->route('login');
});

Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rutas de usuarios (protegidas por auth)
Route::middleware(['auth'])->prefix('admin/usuarios')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/data', [UserController::class, 'data'])->name('usuarios.data');
    Route::post('/', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/{user}', [UserController::class, 'show'])->name('usuarios.show');
    Route::put('/{user}', [UserController::class, 'update'])->name('usuarios.update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('usuarios.destroy');
});

// Rutas de roles (protegidas por auth)
Route::middleware(['auth'])->prefix('admin/roles')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/data', [RoleController::class, 'data'])->name('roles.data');
    Route::post('/', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/permissions', [RoleController::class, 'allPermissions'])->name('roles.permissions');
    Route::get('/{role}', [RoleController::class, 'show'])->name('roles.show');
    Route::put('/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
});

// Rutas de permisos (protegidas por auth)
Route::middleware(['auth'])->prefix('admin/permissions')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('permissions.index')->middleware('can:admin.permissions.ver');
    Route::get('/data', [PermissionController::class, 'data'])->name('permissions.data')->middleware('can:admin.permissions.ver');
    Route::post('/', [PermissionController::class, 'store'])->name('permissions.store')->middleware('can:admin.permissions.crear');
    Route::put('/{permission}', [PermissionController::class, 'update'])->name('permissions.update')->middleware('can:admin.permissions.editar');
    Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('permissions.destroy')->middleware('can:admin.permissions.eliminar');
    Route::post('/sync', [PermissionController::class, 'sync'])->name('permissions.sync')->middleware('can:admin.permissions.ver');
});

// Rutas de reportes
Route::middleware(['auth'])->prefix('reportes')->group(function () {
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

    // Reporte Vendedores B2B/VDT
    Route::get('vendedores-b2b', [ReporteVendedoresB2bController::class, 'index'])
        ->name('reportes.vendedores.b2b')->middleware('can:reportes.vendedores.ver');

    Route::get('vendedores-b2b/data', [ReporteVendedoresB2bController::class, 'data'])
        ->name('reportes.vendedores.b2b.data')->middleware('can:reportes.vendedores.ver');

    Route::post('vendedores-b2b/export', [ReporteVendedoresB2bController::class, 'export'])
        ->name('reportes.vendedores.b2b.export')->middleware('can:reportes.vendedores.editar');

    Route::post('vendedores-b2b/export-csv', [ReporteVendedoresB2bController::class, 'exportCsv'])
        ->name('reportes.vendedores.b2b.export.csv')->middleware('can:reportes.vendedores.editar');

    Route::post('vendedores-b2b/export-pdf', [ReporteVendedoresB2bController::class, 'exportPdf'])
        ->name('reportes.vendedores.b2b.export.pdf')->middleware('can:reportes.vendedores.editar');

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

    // NUEVO REPORTE: Desglose
    Route::get('desglose', [ReporteDesgloseController::class, 'index'])
        ->name('reportes.desglose.index')->middleware('can:reportes.metas-matricial.ver');

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

    // Club Comex - Sincronización
    Route::get('club-comex', [ClubComexController::class, 'index'])
        ->name('reportes.club-comex.index')->middleware('can:reportes.club-comex.ver');
    Route::post('club-comex/sync', [ClubComexController::class, 'sync'])
        ->middleware('can:reportes.club-comex.sincronizar')
        ->name('reportes.club-comex.sync');
    Route::post('club-comex/search', [ClubComexController::class, 'search'])
        ->name('reportes.club-comex.search')->middleware('can:reportes.club-comex.ver');
    Route::post('club-comex/export-csv', [ClubComexController::class, 'exportCsv'])
        ->name('reportes.club-comex.export.csv')->middleware('can:reportes.club-comex.ver');

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

    // REPORTE: DBF Files (Computadoras)
    Route::get('dbf-files', [ReporteDbfFilesController::class, 'index'])
        ->name('reportes.dbf-files')->middleware('can:dbf-files.ver');
    Route::get('dbf-files/data', [ReporteDbfFilesController::class, 'data'])
        ->name('reportes.dbf-files.data')->middleware('can:dbf-files.ver');
    Route::get('dbf-files/export', [ReporteDbfFilesController::class, 'export'])
        ->name('reportes.dbf-files.export')->middleware('can:dbf-files.ver');

    // REPORTE: Vales
    Route::get('vales', [ReporteValesController::class, 'index'])
        ->name('reportes.vales')->middleware('can:reportes.vales.ver');
    Route::get('vales/data', [ReporteValesController::class, 'data'])
        ->name('reportes.vales.data')->middleware('can:reportes.vales.ver');
    Route::get('vales/export', [ReporteValesController::class, 'export'])
        ->name('reportes.vales.export')->middleware('can:reportes.vales.ver');

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
    Route::resource('distributions', DistributionsController::class);
    Route::post('distributions/{distribution}/stop', [DistributionsController::class, 'stop'])->name('distributions.stop');
    Route::post('distributions/{distribution}/start', [DistributionsController::class, 'start'])->name('distributions.start');
    Route::post('distributions/{distribution}/restart', [DistributionsController::class, 'restart'])->name('distributions.restart');
    Route::post('distributions/target/{target}/retry', [DistributionsController::class, 'retryTarget'])->name('distributions.retry-target');
    Route::get('distributions/{distribution}/progress', [DistributionsController::class, 'progress'])->name('distributions.progress');

    Route::resource('computers', ComputersController::class)->only(['index', 'show', 'edit', 'update', 'destroy']);
    Route::get('computers/{computer}/logs', [ComputersController::class, 'logs'])->name('computers.logs');
    Route::get('computers/{computer}/status', [ComputersController::class, 'status'])->name('computers.status');
    Route::get('computers-exportar', [ComputersController::class, 'export'])->name('computers.export');
    Route::get('groups/export', [GroupsController::class, 'export'])->name('groups.export');
    Route::post('groups/import-excel', [GroupsController::class, 'importExcel'])->name('groups.import-excel');
    Route::resource('groups', GroupsController::class);
    Route::resource('agent-versions', AgentVersionsController::class);
    Route::resource('resurtido-agent-versions', ResurtidoAgentVersionsController::class);
    Route::post('resurtido-agent-versions/{resurtido_agent_version}/deploy', [ResurtidoAgentVersionsController::class, 'deploy'])->name('resurtido-agent-versions.deploy');

    Route::post('reception/{reception}/stop', [ReceptionController::class, 'stop'])->name('reception.stop');
    Route::post('reception/{reception}/start', [ReceptionController::class, 'start'])->name('reception.start');
    Route::post('reception/target/{target}/retry', [ReceptionController::class, 'retryTarget'])->name('reception.retry-target');
    Route::get('reception/computer/{computer}', [ReceptionController::class, 'showComputer'])->name('reception.computer');
    Route::resource('reception', ReceptionController::class);

    // File Reception (Subida de archivos)
    Route::resource('file-receptions', FileReceptionController::class);

    // User Plaza Tienda - Solo super_admin
    Route::middleware('can:admin.usuarios.ver')->group(function () {
        Route::get('user-plaza-tienda', [UserPlazaTiendaController::class, 'index'])->name('user-plaza-tienda.index');
        Route::get('user-plaza-tienda/{user}/edit', [UserPlazaTiendaController::class, 'edit'])->name('user-plaza-tienda.edit');
        Route::get('user-plaza-tienda/tiendas', [UserPlazaTiendaController::class, 'getTiendas'])->name('user-plaza-tienda.tiendas');
    });

    Route::middleware('can:admin.usuarios.editar')->group(function () {
        Route::put('user-plaza-tienda/{user}', [UserPlazaTiendaController::class, 'update'])->name('user-plaza-tienda.update');
    });

    // Tiendas - Solo super_admin
    Route::middleware('can:tiendas.ver')->group(function () {
        Route::get('tiendas', [TiendasController::class, 'index'])->name('tiendas.index');
        Route::get('tiendas/data', [TiendasController::class, 'data'])->name('tiendas.data');
        Route::get('tiendas/{tienda}', [TiendasController::class, 'show'])->name('tiendas.show');
    });

    Route::middleware('can:tiendas.crear')->group(function () {
        Route::post('tiendas', [TiendasController::class, 'store'])->name('tiendas.store');
    });

    Route::middleware('can:tiendas.editar')->group(function () {
        Route::put('tiendas/{tienda}', [TiendasController::class, 'update'])->name('tiendas.update');
    });

    Route::middleware('can:tiendas.eliminar')->group(function () {
        Route::delete('tiendas/{tienda}', [TiendasController::class, 'destroy'])->name('tiendas.destroy');
    });

    Route::middleware('can:tiendas.crear')->group(function () {
        Route::post('tiendas', [TiendasController::class, 'store'])->name('tiendas.store');
    });

    Route::middleware('can:tiendas.editar')->group(function () {
        Route::put('tiendas/{tienda}', [TiendasController::class, 'update'])->name('tiendas.update');
    });

    Route::middleware('can:tiendas.eliminar')->group(function () {
        Route::delete('tiendas/{tienda}', [TiendasController::class, 'destroy'])->name('tiendas.destroy');
    });
});

// Metas Mensual Import routes (protected by auth)
Route::middleware(['auth'])->group(function () {
    Route::get('/metas-mensual', [MetasMensualController::class, 'index'])->name('metas.index')->middleware('can:metas.ver');
    Route::post('/metas-mensual/import', [MetasMensualController::class, 'import'])->name('metas.import')->middleware('can:metas.importar');
    // CRUD for metas_mensual
    Route::post('/metas-mensual/store', [MetasMensualController::class, 'store'])->name('metas.store')->middleware('can:metas.crear');
    Route::post('/metas-mensual/update', [MetasMensualController::class, 'update'])->name('metas.update')->middleware('can:metas.editar');
    Route::post('/metas-mensual/delete', [MetasMensualController::class, 'destroy'])->name('metas.destroy')->middleware('can:metas.eliminar');
    Route::post('/metas-mensual/generar', [MetasMensualController::class, 'generarMetas'])->name('metas.generar')->middleware('can:metas.crear');
});

// Agent API routes (no auth, no CSRF for agents)
Route::get('/api/register', [AgentController::class, 'register'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/api/heartbeat', [AgentController::class, 'heartbeat'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/commands/{id}', [AgentController::class, 'getCommands'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/api/report', [AgentController::class, 'report'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/download/{fileId}', [AgentController::class, 'download'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/update/{version}', [AgentController::class, 'checkUpdate'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/check-update/{version}', [AgentController::class, 'checkUpdate'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/computer/{computer_id}/update', [AgentController::class, 'checkUpdateByComputerId'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/api/inventory', [AgentController::class, 'inventory'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/api/upload-reception', [AgentController::class, 'uploadReception'])->withoutMiddleware([VerifyCsrfToken::class]);

// Serve agent updates directly without middleware
Route::get('/agent-updates/{path}', function (string $path) {
    $path = str_replace('agent_updates/', '', $path);
    $fullPath = storage_path('app/public/agent_updates/'.$path);

    if (! file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/dias-periodo', [MetasMensualController::class, 'getDiasPeriodo'])->middleware('can:metas.ver');
Route::post('/metas-dias/generate', [MetasMensualController::class, 'generateDias'])->name('metas_dias.generate')->middleware('can:metas.crear');
Route::get('/metas-mensual/performance-test', [MetasMensualController::class, 'performanceTest'])
    ->name('metas.performance.test')->middleware('can:metas.ver');
