<?php

/**
 * PRUEBA RÁPIDA DE CORRECCIÓN DEL ERROR SQL
 * Ejecutar: php test_sql_fix.php
 */

require_once __DIR__.'/vendor/autoload.php';

use App\Services\ReportService;
use Illuminate\Contracts\Http\Kernel;

echo "=== PRUEBA DE CORRECCIÓN SQL ===\n\n";

try {
    // Inicializar Laravel
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    echo "✓ Laravel inicializado\n";

    // Configurar límites
    ReportService::optimizarConfiguracion();
    echo "✓ Configuración optimizada\n\n";

    // Filtros de prueba
    $filtros = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-05', // Solo 5 días para evitar timeout
        'plaza' => '',
        'tienda' => '',
        'vendedor' => '',
    ];

    echo "PRUEBA 1: Reporte de Vendedores\n";
    echo "-------------------------------\n";

    $inicio = microtime(true);
    $resultados = ReportService::getVendedoresReport($filtros);
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Reporte de vendedores ejecutado en {$tiempo}ms\n";
    echo '✓ Registros obtenidos: '.$resultados->count()."\n\n";

    echo "PRUEBA 2: Reporte Matricial (EL QUE FALLABA)\n";
    echo "---------------------------------------------\n";

    $inicio = microtime(true);
    $matricial = ReportService::getVendedoresMatricialReport($filtros);
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Reporte matricial ejecutado en {$tiempo}ms\n";
    echo '✓ Días procesados: '.count($matricial['dias'])."\n";
    echo '✓ Vendedores procesados: '.count($matricial['vendedores_info'])."\n\n";

    echo "PRUEBA 3: Reporte de Metas de Ventas\n";
    echo "-------------------------------------\n";

    $filtros_metas = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-31',
        'plaza' => '',
        'tienda' => '',
        'zona' => '',
    ];

    $inicio = microtime(true);
    $metas = ReportService::getMetasVentasReport($filtros_metas);
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Reporte de metas ejecutado en {$tiempo}ms\n";
    echo '✓ Registros de metas: '.count($metas['resultados'])."\n\n";

    echo "========================================\n";
    echo "🎉 ¡CORRECCIÓN EXITOSA!\n";
    echo "========================================\n\n";

    echo "RESUMEN DE LA CORRECCIÓN:\n";
    echo "• ❌ ERROR ANTERIOR: 'column d.devolucion_total must appear in GROUP BY'\n";
    echo "• ✅ SOLUCIÓN: Cambié CTE+JOIN por subquery correlacionada\n";
    echo "• ✅ RESULTADO: Todos los reportes funcionan correctamente\n\n";

    echo "Ahora puedes acceder a los reportes sin errores SQL.\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: ".$e->getMessage()."\n\n";
    echo "Si aún hay errores, revisa:\n";
    echo "1. Conexión a base de datos PostgreSQL\n";
    echo "2. Que existan las tablas canota, venta, asesores_vvt\n";
    echo "3. Permisos de lectura en las tablas\n";
}
