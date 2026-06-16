<?php

/**
 * SCRIPT DE EJECUCIÓN DIRECTA PARA LARAGON
 * Probar todos los reportes optimizados
 *
 * Ejecutar: php run_reports_test.php
 */

require_once __DIR__.'/vendor/autoload.php';

use App\Services\ReportService;
use Illuminate\Contracts\Http\Kernel;

echo "========================================\n";
echo "EJECUCIÓN DIRECTA DE REPORTES OPTIMIZADOS\n";
echo "========================================\n\n";

try {
    // Inicializar Laravel
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    // Configurar límites
    ReportService::optimizarConfiguracion();

    echo "✓ Sistema inicializado y configurado\n\n";

    // MENÚ DE PRUEBAS
    echo "REPORTES DISPONIBLES PARA PRUEBA:\n";
    echo "1. Reporte de Vendedores\n";
    echo "2. Reporte Matricial de Vendedores\n";
    echo "3. Reporte de Metas de Ventas\n";
    echo "4. Venta Acumulada (API)\n";
    echo "5. Ejecutar todas las pruebas\n\n";

    $opcion = isset($argv[1]) ? $argv[1] : '5'; // Por defecto ejecutar todas

    if ($opcion === '5' || $opcion === 'all') {
        echo "EJECUTANDO TODAS LAS PRUEBAS...\n\n";

        // Prueba 1: Vendedores
        ejecutarPruebaVendedores();

        // Prueba 2: Matricial
        ejecutarPruebaMatricial();

        // Prueba 3: Metas
        ejecutarPruebaMetas();

        // Prueba 4: Venta Acumulada
        ejecutarPruebaVentaAcumulada();

    } else {
        switch ($opcion) {
            case '1':
                ejecutarPruebaVendedores();
                break;
            case '2':
                ejecutarPruebaMatricial();
                break;
            case '3':
                ejecutarPruebaMetas();
                break;
            case '4':
                ejecutarPruebaVentaAcumulada();
                break;
            default:
                echo "❌ Opción no válida. Use: 1, 2, 3, 4, o 5\n";
        }
    }

} catch (Exception $e) {
    echo "\n❌ ERROR GENERAL:\n";
    echo $e->getMessage()."\n";
}

echo "\n========================================\n";

function ejecutarPruebaVendedores()
{
    echo "🔍 PROBANDO: Reporte de Vendedores\n";
    echo "-----------------------------------\n";

    $filtros = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-31',
        'plaza' => '',
        'tienda' => '',
        'vendedor' => '',
    ];

    $inicio = microtime(true);
    $resultados = ReportService::getVendedoresReport($filtros);
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Ejecutado en {$tiempo}ms\n";
    echo '✓ Registros: '.$resultados->count()."\n\n";
}

function ejecutarPruebaMatricial()
{
    echo "🔍 PROBANDO: Reporte Matricial\n";
    echo "-----------------------------\n";

    $filtros = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-15',
        'plaza' => '',
        'tienda' => '',
        'vendedor' => '',
    ];

    $inicio = microtime(true);
    $datos = ReportService::getVendedoresMatricialReport($filtros);
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Ejecutado en {$tiempo}ms\n";
    echo '✓ Días: '.count($datos['dias'])."\n";
    echo '✓ Vendedores: '.count($datos['vendedores_info'])."\n\n";
}

function ejecutarPruebaMetas()
{
    echo "🔍 PROBANDO: Reporte de Metas de Ventas\n";
    echo "--------------------------------------\n";

    $filtros = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-31',
        'plaza' => '',
        'tienda' => '',
        'zona' => '',
    ];

    $inicio = microtime(true);
    $datos = ReportService::getMetasVentasReport($filtros);
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Ejecutado en {$tiempo}ms\n";
    echo '✓ Registros: '.count($datos['resultados'])."\n\n";
}

function ejecutarPruebaVentaAcumulada()
{
    echo "🔍 PROBANDO: Venta Acumulada (API)\n";
    echo "-----------------------------------\n";

    $inicio = microtime(true);
    $resultados = ReportService::getVentaAcumulada('2024-01-31');
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Ejecutado en {$tiempo}ms\n";
    echo '✓ Registros: '.count($resultados['data'])."\n";
    echo '✓ Total acumulado: $'.number_format($resultados['total_acumulado'], 2)."\n\n";
}
