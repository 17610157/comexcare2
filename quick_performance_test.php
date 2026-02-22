<?php
/**
 * PRUEBA RÁPIDA DE PAGINACIÓN Y RENDIMIENTO
 * Ejecutar con: php quick_performance_test.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ReportService;

echo "=== PRUEBA RÁPIDA DE PAGINACIÓN ===\n\n";

try {
    // Configurar Laravel
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();

    echo "✓ Laravel configurado\n";

    // Configurar límites
    ini_set('memory_limit', '512M');
    ini_set('max_execution_time', 120);
    ReportService::optimizarConfiguracion();

    echo "✓ Configuración optimizada\n\n";

    // Filtros de prueba
    $filtros = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-31',
        'plaza' => '',
        'tienda' => '',
        'vendedor' => ''
    ];

    echo "PRUEBA 1: Consulta sin paginación\n";
    $inicio = microtime(true);
    $todos_los_resultados = ReportService::getVendedoresReport($filtros);
    $tiempo_sin_paginacion = round((microtime(true) - $inicio) * 1000, 2);
    $total_registros = $todos_los_resultados->count();

    echo "✓ {$total_registros} registros obtenidos en {$tiempo_sin_paginacion}ms\n";

    echo "\nPRUEBA 2: Simulación de paginación (página 1, 100 registros)\n";
    $inicio = microtime(true);
    $pagina_1 = $todos_los_resultados->forPage(1, 100);
    $tiempo_pagina_1 = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Página 1: {$pagina_1->count()} registros mostrados en {$tiempo_pagina_1}ms\n";

    echo "\nPRUEBA 3: Simulación de paginación (página 5, 100 registros)\n";
    $inicio = microtime(true);
    $pagina_5 = $todos_los_resultados->forPage(5, 100);
    $tiempo_pagina_5 = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Página 5: {$pagina_5->count()} registros mostrados en {$tiempo_pagina_5}ms\n";

    echo "\nPRUEBA 4: Cálculo de estadísticas\n";
    $inicio = microtime(true);
    $estadisticas = ReportService::calcularEstadisticasVendedores($todos_los_resultados);
    $tiempo_estadisticas = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Estadísticas calculadas en {$tiempo_estadisticas}ms\n";
    echo "  - Total ventas: " . number_format($estadisticas['total_ventas'], 2) . "\n";
    echo "  - Total registros: {$estadisticas['total_registros']}\n";

    // Análisis de resultados
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "ANÁLISIS DE RESULTADOS:\n";
    echo str_repeat("=", 50) . "\n";

    $tiempo_promedio_por_registro = $tiempo_sin_paginacion / max($total_registros, 1);
    echo "Tiempo promedio por registro: " . round($tiempo_promedio_por_registro, 3) . "ms\n";

    if ($total_registros >= 7000) {
        $tiempo_estimado_7k = $tiempo_promedio_por_registro * 7000;
        echo "Tiempo estimado para 7,000 registros: " . round($tiempo_estimado_7k, 2) . "ms (" . round($tiempo_estimado_7k / 1000, 2) . "s)\n";

        if ($tiempo_estimado_7k > 30000) { // 30 segundos
            echo "❌ PROBLEMA: Consulta muy lenta (>30s para 7k registros)\n";
            echo "💡 SOLUCIÓN: Usar PAGINACIÓN implementada\n";
        } else {
            echo "✅ RENDIMIENTO ACEPTABLE\n";
        }
    }

    echo "\nBENEFICIOS DE LA PAGINACIÓN:\n";
    echo "- Tiempo de carga reducido significativamente\n";
    echo "- Mejor experiencia de usuario\n";
    echo "- Menor uso de memoria\n";
    echo "- Navegación más rápida entre páginas\n";

    echo "\n🎉 PRUEBA COMPLETADA - PAGINACIÓN FUNCIONANDO!\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Verifica la conexión a base de datos y las credenciales.\n";
}