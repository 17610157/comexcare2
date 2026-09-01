<?php

use App\Http\Controllers\AgentDefaultsController;
use App\Http\Controllers\AgentVersionsController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AuthorizableEmailsController;
use App\Http\Controllers\AuthorizationController;
use App\Http\Controllers\AuthorizationReportController;
use App\Http\Controllers\ComputersController;
use App\Http\Controllers\DashboardAlertController;
use App\Http\Controllers\DistributionsController;
use App\Http\Controllers\FileListsController;
use App\Http\Controllers\FileReceptionController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MetasMensualController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\MonitoredFilesController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RbfFileHashesController;
use App\Http\Controllers\RbfPlazaTimeConfigController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ReporteApiDemoController;
use App\Http\Controllers\ReporteComprasDirectoController;
use App\Http\Controllers\ReporteDbfFilesController;
use App\Http\Controllers\ReporteDbfFilesEspecificosController;
use App\Http\Controllers\ReporteDbfFilesQuickbckController;
use App\Http\Controllers\ReporteDesgloseController;
use App\Http\Controllers\ReporteDistribucionesController;
use App\Http\Controllers\ReporteMetasMatricialController;
use App\Http\Controllers\ReporteMetasVentasController;
use App\Http\Controllers\ReporteRbfConfigStatusController;
use App\Http\Controllers\Reportes\CarteraAbonosController;
use App\Http\Controllers\Reportes\ClubComexController;
use App\Http\Controllers\Reportes\NotasCompletasController;
use App\Http\Controllers\Reportes\ReporteRedencionesClubController;
use App\Http\Controllers\ReporteTrazabilidadController;
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
Route::get('/home/stats', [HomeController::class, 'stats'])->middleware('auth')->name('home.stats');
Route::get('/home/server-stats', [HomeController::class, 'serverStats'])->middleware('auth')->name('home.server-stats');
Route::get('/home/map-stats', [HomeController::class, 'mapStats'])->middleware('auth')->name('home.map-stats');
Route::get('/home/map-computers', [HomeController::class, 'mapComputers'])->middleware('auth')->name('home.map-computers');
Route::get('/home/activity', [HomeController::class, 'activity'])->middleware('auth')->name('home.activity');
Route::get('/home/fleet-health', [HomeController::class, 'fleetHealth'])->middleware('auth')->name('home.fleet-health');
Route::get('/home/dbf-overview', [HomeController::class, 'dbfOverview'])->middleware('auth')->name('home.dbf-overview');

Route::middleware('auth')->prefix('alerts')->group(function () {
    Route::get('/page', [DashboardAlertController::class, 'page'])->name('alerts.page');
    Route::get('/state', [DashboardAlertController::class, 'state'])->name('alerts.state');
    Route::post('/ack', [DashboardAlertController::class, 'ack'])->name('alerts.ack');
    Route::post('/simulate', [DashboardAlertController::class, 'simulate'])->name('alerts.simulate');
    Route::patch('/rules/{rule}', [DashboardAlertController::class, 'updateRule'])->name('alerts.rules.update');
    Route::get('/sounds', [DashboardAlertController::class, 'sounds'])->name('alerts.sounds');
    Route::post('/sounds', [DashboardAlertController::class, 'uploadSound'])->name('alerts.sounds.upload');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Authorization routes (public - no auth required)
Route::get('/authorization/{token}', [AuthorizationController::class, 'show'])->name('authorization.show');
Route::post('/authorization/{token}', [AuthorizationController::class, 'process'])->name('authorization.process');

// API pública de reportes DBF
Route::get('/api/dbf-report', [ReporteDbfFilesController::class, 'api']);

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
        ->name('reportes.vendedores.b2b')->middleware('can:reportes.vendedores_b2b.ver');

    Route::get('vendedores-b2b/data', [ReporteVendedoresB2bController::class, 'data'])
        ->name('reportes.vendedores.b2b.data')->middleware('can:reportes.vendedores_b2b.ver');

    Route::post('vendedores-b2b/export', [ReporteVendedoresB2bController::class, 'export'])
        ->name('reportes.vendedores.b2b.export')->middleware('can:reportes.vendedores_b2b.editar');

    Route::post('vendedores-b2b/export-csv', [ReporteVendedoresB2bController::class, 'exportCsv'])
        ->name('reportes.vendedores.b2b.export.csv')->middleware('can:reportes.vendedores_b2b.editar');

    Route::post('vendedores-b2b/export-pdf', [ReporteVendedoresB2bController::class, 'exportPdf'])
        ->name('reportes.vendedores.b2b.export.pdf')->middleware('can:reportes.vendedores_b2b.editar');

    Route::get('vendedores-matricial', [ReporteVendedoresMatricialController::class, 'index'])
        ->name('reportes.vendedores.matricial')->middleware('can:reportes.vendedores_matricial.ver');

    // Exportar Excel
    Route::post('vendedores-matricial/export-excel', [ReporteVendedoresMatricialController::class, 'exportExcel'])
        ->name('reportes.vendedores.matricial.export.excel')->middleware('can:reportes.vendedores_matricial.editar');

    // Exportar PDF
    Route::post('vendedores-matricial/export-pdf', [ReporteVendedoresMatricialController::class, 'exportPdf'])
        ->name('reportes.vendedores.matricial.export.pdf')->middleware('can:reportes.vendedores_matricial.editar');

    // Exportar CSV
    Route::post('vendedores-matricial/export-csv', [ReporteVendedoresMatricialController::class, 'exportCsv'])
        ->name('reportes.vendedores.matricial.export.csv')->middleware('can:reportes.vendedores_matricial.editar');

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

    // REPORTE: DBF Files Especificos
    Route::get('dbf-files-especificos', [ReporteDbfFilesEspecificosController::class, 'index'])
        ->name('reportes.dbf-files-especificos')->middleware('can:dbf-files-especificos.ver');
    Route::get('dbf-files-especificos/data', [ReporteDbfFilesEspecificosController::class, 'data'])
        ->name('reportes.dbf-files-especificos.data')->middleware('can:dbf-files-especificos.ver');
    Route::get('dbf-files-especificos/export', [ReporteDbfFilesEspecificosController::class, 'export'])
        ->name('reportes.dbf-files-especificos.export')->middleware('can:dbf-files-especificos.ver');
    Route::post('dbf-files-especificos/ejecutar/{tipo}', [ReporteDbfFilesEspecificosController::class, 'ejecutar'])
        ->name('reportes.dbf-files-especificos.ejecutar')->middleware('can:dbf-files-especificos.ejecutar');
    Route::get('dbf-files-especificos/bitacora', [ReporteDbfFilesEspecificosController::class, 'bitacora'])
        ->name('reportes.dbf-files-especificos.bitacora')->middleware('can:dbf-files-especificos.ver');
    Route::get('dbf-files-especificos/ids', [ReporteDbfFilesEspecificosController::class, 'ids'])
        ->name('reportes.dbf-files-especificos.ids')->middleware('can:dbf-files-especificos.ver');
    Route::get('dbf-files-especificos/historial', [ReporteDbfFilesEspecificosController::class, 'historial'])
        ->name('reportes.dbf-files-especificos.historial')->middleware('can:dbf-files-especificos.ver');

    // REPORTE: DBF Files QuickBCK Conciliación
    Route::get('dbf-files-quickbck', [ReporteDbfFilesQuickbckController::class, 'index'])
        ->name('reportes.dbf-files-quickbck')->middleware('can:dbf-files-quickbck.ver');
    Route::get('dbf-files-quickbck/data', [ReporteDbfFilesQuickbckController::class, 'data'])
        ->name('reportes.dbf-files-quickbck.data')->middleware('can:dbf-files-quickbck.ver');
    Route::get('dbf-files-quickbck/export', [ReporteDbfFilesQuickbckController::class, 'export'])
        ->name('reportes.dbf-files-quickbck.export')->middleware('can:dbf-files-quickbck.ver');

    // REPORTE: Trazabilidad (basado en disparador RBF)
    Route::get('trazabilidad', [ReporteTrazabilidadController::class, 'index'])
        ->name('reportes.trazabilidad')->middleware('can:reportes.trazabilidad.ver');
    Route::get('trazabilidad/data', [ReporteTrazabilidadController::class, 'data'])
        ->name('reportes.trazabilidad.data')->middleware('can:reportes.trazabilidad.ver');
    Route::get('trazabilidad/archivos', [ReporteTrazabilidadController::class, 'archivos'])
        ->name('reportes.trazabilidad.archivos')->middleware('can:reportes.trazabilidad.ver');
    Route::get('trazabilidad/archivos-disponibles', [ReporteTrazabilidadController::class, 'archivosDisponibles'])
        ->name('reportes.trazabilidad.archivos-disponibles')->middleware('can:reportes.trazabilidad.ver');
    Route::get('trazabilidad/export', [ReporteTrazabilidadController::class, 'export'])
        ->name('reportes.trazabilidad.export')->middleware('can:reportes.trazabilidad.ver');

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

    Route::get('distribuciones', [ReporteDistribucionesController::class, 'index'])
        ->name('reportes.distribuciones.index')->middleware('can:reportes.distribuciones.ver');
    Route::get('distribuciones/data', [ReporteDistribucionesController::class, 'data'])
        ->name('reportes.distribuciones.data')->middleware('can:reportes.distribuciones.ver');
    Route::get('distribuciones/resumen', [ReporteDistribucionesController::class, 'resumen'])
        ->name('reportes.distribuciones.resumen')->middleware('can:reportes.distribuciones.ver');
    Route::get('distribuciones/por-usuario', [ReporteDistribucionesController::class, 'porUsuario'])
        ->name('reportes.distribuciones.por-usuario')->middleware('can:reportes.distribuciones.ver');

    // Reporte: API Demo
    Route::get('api-demo', [ReporteApiDemoController::class, 'index'])
        ->name('reportes.api-demo')->middleware('can:reportes.api-demo.ver');
    Route::get('api-demo/data', [ReporteApiDemoController::class, 'data'])
        ->name('reportes.api-demo.data')->middleware('can:reportes.api-demo.ver');

    // Reporte de Autorizaciones
    Route::get('authorization-report', [AuthorizationReportController::class, 'index'])
        ->name('reportes.authorization-report.index')->middleware('can:reportes.ver');
    Route::get('authorization-report/data', [AuthorizationReportController::class, 'data'])
        ->name('reportes.authorization-report.data')->middleware('can:reportes.ver');
    Route::get('authorization-report/export', [AuthorizationReportController::class, 'export'])
        ->name('reportes.authorization-report.export')->middleware('can:reportes.ver');

    // Reporte de Estado RBF
    Route::get('rbf-config-status', [ReporteRbfConfigStatusController::class, 'index'])
        ->name('reportes.rbf-config-status')->middleware('can:reportes.rbf-config-status.ver');
    Route::get('rbf-config-status/data', [ReporteRbfConfigStatusController::class, 'data'])
        ->name('reportes.rbf-config-status.data')->middleware('can:reportes.rbf-config-status.ver');

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
    Route::post('computers/fix-duplicates', [ComputersController::class, 'fixDuplicates'])->name('computers.fix-duplicates');
    Route::get('groups/export', [GroupsController::class, 'export'])->name('groups.export');
    Route::post('groups/import-excel', [GroupsController::class, 'importExcel'])->name('groups.import-excel');
    Route::resource('groups', GroupsController::class);
    Route::resource('agent-versions', AgentVersionsController::class);
    Route::post('agent-versions/{agentVersion}/deploy', [AgentVersionsController::class, 'deploy'])->name('agent-versions.deploy');
    Route::post('agent-versions/{agentVersion}/activate', [AgentVersionsController::class, 'activate'])->name('agent-versions.activate');
    Route::delete('agent-versions/{agentVersion}/force-delete', [AgentVersionsController::class, 'forceDelete'])->name('agent-versions.force-delete');
    Route::resource('resurtido-agent-versions', ResurtidoAgentVersionsController::class);
    Route::post('resurtido-agent-versions/{resurtido_agent_version}/deploy', [ResurtidoAgentVersionsController::class, 'deploy'])->name('resurtido-agent-versions.deploy');

    Route::post('reception/{reception}/stop', [ReceptionController::class, 'stop'])->name('reception.stop');
    Route::post('reception/{reception}/start', [ReceptionController::class, 'start'])->name('reception.start');
    Route::post('reception/target/{target}/retry', [ReceptionController::class, 'retryTarget'])->name('reception.retry-target');
    Route::get('reception/computer/{computer}', [ReceptionController::class, 'showComputer'])->name('reception.computer');
    Route::resource('reception', ReceptionController::class);

    // File Reception (Subida de archivos)
    Route::resource('file-receptions', FileReceptionController::class);

    Route::resource('file-lists', FileListsController::class)->except(['create', 'show', 'edit']);
    Route::post('file-lists/validate', [FileListsController::class, 'validateFiles'])->name('file-lists.validate');

    // Modules
    Route::resource('modules', ModulesController::class)->except(['create', 'show', 'edit']);

    // Authorizable Emails
    Route::resource('authorizable-emails', AuthorizableEmailsController::class)->except(['create', 'show', 'edit']);

    // Agent Defaults - Archivos Predeterminados
    Route::post('agent-defaults/{category}/toggle-auto-sync', [AgentDefaultsController::class, 'toggleAutoSync'])->name('agent-defaults.toggle-auto-sync');
    Route::post('agent-defaults/{category}/toggle-auto-validation', [AgentDefaultsController::class, 'toggleAutoValidation'])->name('agent-defaults.toggle-auto-validation');
    Route::resource('agent-defaults', AgentDefaultsController::class)->parameters(['agent-defaults' => 'category']);
    Route::post('agent-defaults/{category}/routes', [AgentDefaultsController::class, 'storeRoute'])->name('agent-defaults.routes.store');
    Route::put('agent-defaults/routes/{route}', [AgentDefaultsController::class, 'updateRoute'])->name('agent-defaults.routes.update');
    Route::delete('agent-defaults/routes/{route}', [AgentDefaultsController::class, 'destroyRoute'])->name('agent-defaults.routes.destroy');
    Route::get('agent-defaults/routes/{route}/assignments', [AgentDefaultsController::class, 'listAssignments'])->name('agent-defaults.assignments.list');
    Route::post('agent-defaults/routes/{route}/assignments', [AgentDefaultsController::class, 'storeAssignment'])->name('agent-defaults.assignments.store');
    Route::delete('agent-defaults/assignments/{assignment}', [AgentDefaultsController::class, 'destroyAssignment'])->name('agent-defaults.assignments.destroy');
    Route::post('agent-defaults/routes/{route}/files', [AgentDefaultsController::class, 'storeFile'])->name('agent-defaults.files.store');
    Route::get('agent-defaults/routes/{route}/files', [AgentDefaultsController::class, 'listFiles'])->name('agent-defaults.files.list');
    Route::delete('agent-defaults/routes/{route}/files/{file}', [AgentDefaultsController::class, 'destroyFile'])->name('agent-defaults.files.destroy');
    Route::get('agent-defaults/routes/{route}/files/{file}/download', [AgentDefaultsController::class, 'downloadFile'])->name('agent-defaults.files.download');
    Route::post('agent-defaults/routes/{route}/sync-files', [AgentDefaultsController::class, 'syncFiles'])->name('agent-defaults.files.sync');

    // Monitored Files - Archivos Monitoreados
    Route::get('monitored-files', [MonitoredFilesController::class, 'index'])->name('monitored-files.index')->middleware('can:monitored-files.ver');
    Route::post('monitored-files/seed-defaults', [MonitoredFilesController::class, 'seedDefaults'])->name('monitored-files.seed-defaults')->middleware('can:monitored-files.crear');
    Route::post('monitored-files', [MonitoredFilesController::class, 'store'])->name('monitored-files.store')->middleware('can:monitored-files.crear');
    Route::put('monitored-files/{monitored_file}', [MonitoredFilesController::class, 'update'])->name('monitored-files.update')->middleware('can:monitored-files.editar');
    Route::delete('monitored-files/{monitored_file}', [MonitoredFilesController::class, 'destroy'])->name('monitored-files.destroy')->middleware('can:monitored-files.eliminar');

    // RBF File Hashes - Subida de archivos y cálculo de MD5
    Route::get('rbf-file-hashes', [RbfFileHashesController::class, 'index'])->name('rbf-file-hashes.index')->middleware('can:rbf-file-hashes.ver');
    Route::get('rbf-file-hashes/data', [RbfFileHashesController::class, 'data'])->name('rbf-file-hashes.data')->middleware('can:rbf-file-hashes.ver');
    Route::post('rbf-file-hashes', [RbfFileHashesController::class, 'store'])->name('rbf-file-hashes.store')->middleware('can:rbf-file-hashes.crear');
    Route::delete('rbf-file-hashes/{rbf_file_hash}', [RbfFileHashesController::class, 'destroy'])->name('rbf-file-hashes.destroy')->middleware('can:rbf-file-hashes.eliminar');

    // RBF Plaza Time Configs - Ajuste de last_modified por plaza
    Route::get('rbf-plaza-time-configs', [RbfPlazaTimeConfigController::class, 'index'])->name('rbf-plaza-time-configs.index')->middleware('can:rbf-plaza-time.ver');
    Route::get('rbf-plaza-time-configs/data', [RbfPlazaTimeConfigController::class, 'data'])->name('rbf-plaza-time-configs.data')->middleware('can:rbf-plaza-time.ver');
    Route::post('rbf-plaza-time-configs', [RbfPlazaTimeConfigController::class, 'store'])->name('rbf-plaza-time-configs.store')->middleware('can:rbf-plaza-time.crear');
    Route::post('rbf-plaza-time-configs/sincronizar', [RbfPlazaTimeConfigController::class, 'sincronizar'])->name('rbf-plaza-time-configs.sincronizar')->middleware('can:rbf-plaza-time.sincronizar');
    Route::put('rbf-plaza-time-configs/{rbf_plaza_time_config}', [RbfPlazaTimeConfigController::class, 'update'])->name('rbf-plaza-time-configs.update')->middleware('can:rbf-plaza-time.editar');
    Route::delete('rbf-plaza-time-configs/{rbf_plaza_time_config}', [RbfPlazaTimeConfigController::class, 'destroy'])->name('rbf-plaza-time-configs.destroy')->middleware('can:rbf-plaza-time.eliminar');

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
Route::get('/api/register', [AgentController::class, 'register'])->middleware('api.rate_limit:api_agents')->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/api/heartbeat', [AgentController::class, 'heartbeat'])->middleware('api.rate_limit:api_heartbeat')->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/commands/{id}', [AgentController::class, 'getCommands'])->middleware('api.rate_limit:api_commands')->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/api/report', [AgentController::class, 'report'])->middleware('api.rate_limit:api_report')->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/download/{fileId}', [AgentController::class, 'download'])->middleware('api.rate_limit:api_download')->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/update/{version}', [AgentController::class, 'checkUpdate'])->middleware('api.rate_limit:api_agents')->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/check-update/{version}', [AgentController::class, 'checkUpdate'])->middleware('api.rate_limit:api_agents')->withoutMiddleware([VerifyCsrfToken::class]);
Route::get('/api/computer/{computer_id}/update', [AgentController::class, 'checkUpdateByComputerId'])->middleware('api.rate_limit:api_agents')->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/api/inventory', [AgentController::class, 'inventory'])->middleware('api.rate_limit:api_agents')->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/api/upload-reception', [AgentController::class, 'uploadReception'])->middleware('api.rate_limit:api_agents')->withoutMiddleware([VerifyCsrfToken::class]);

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
