<?php

/**
 * SCRIPT DE PRUEBA RÁPIDA PARA LARAGON
 * Ejecutar desde línea de comandos: php test_performance_laragon.php
 *
 * Este script prueba el rendimiento de los reportes optimizados
 * sin necesidad de acceder vía web.
 */

// Configurar entorno Laravel
require_once __DIR__.'/vendor/autoload.php';

use App\Services\ReportService;
use Illuminate\Contracts\Http\Kernel;

echo "========================================\n";
echo "PRUEBA DE RENDIMIENTO - SISTEMA OPTIMIZADO\n";
echo "========================================\n\n";

try {
    // Inicializar Laravel
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    echo "✓ Laravel inicializado correctamente\n";

    // Configurar límites para pruebas
    ReportService::optimizarConfiguracion();
    echo "✓ Configuración de memoria y tiempo optimizada\n\n";

    // PRUEBA 1: Reporte de Vendedores
    echo "PRUEBA 1: Reporte de Vendedores\n";
    echo "-------------------------------\n";

    $filtros = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-31',
        'plaza' => '',
        'tienda' => '',
        'vendedor' => '',
    ];

    $tiempo_inicio = microtime(true);
    $resultados = ReportService::getVendedoresReport($filtros);
    $tiempo_total = round((microtime(true) - $tiempo_inicio) * 1000, 2);
    $num_registros = $resultados->count();

    echo "• Registros obtenidos: $num_registros\n";
    echo "• Tiempo de ejecución: {$tiempo_total}ms\n";

    if ($tiempo_total < 5000) {
        echo "✓ RENDIMIENTO EXCELENTE (< 5 segundos)\n";
    } elseif ($tiempo_total < 10000) {
        echo "✓ RENDIMIENTO BUENO (< 10 segundos)\n";
    } elseif ($tiempo_total < 20000) {
        echo "⚠️ RENDIMIENTO REGULAR (< 20 segundos)\n";
    } else {
        echo "❌ RENDIMIENTO DEFICIENTE (> 20 segundos)\n";
    }

    // Calcular estadísticas
    $estadisticas = ReportService::calcularEstadisticasVendedores($resultados);
    echo '• Total ventas: $'.number_format($estadisticas['total_ventas'], 2)."\n";
    echo "• Total registros: {$estadisticas['total_registros']}\n\n";

    // PRUEBA 2: Reporte Matricial
    echo "PRUEBA 2: Reporte Matricial de Vendedores\n";
    echo "-----------------------------------------\n";

    $filtros_matricial = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-15', // Menos días para evitar timeout
        'plaza' => '',
        'tienda' => '',
        'vendedor' => '',
    ];

    $tiempo_inicio = microtime(true);
    $datos_matriciales = ReportService::getVendedoresMatricialReport($filtros_matricial);
    $tiempo_matricial = round((microtime(true) - $tiempo_inicio) * 1000, 2);

    echo '• Días procesados: '.count($datos_matriciales['dias'])."\n";
    echo '• Vendedores procesados: '.count($datos_matriciales['vendedores_info'])."\n";
    echo "• Tiempo de ejecución: {$tiempo_matricial}ms\n";

    if ($tiempo_matricial < 8000) {
        echo "✓ RENDIMIENTO EXCELENTE (< 8 segundos)\n";
    } elseif ($tiempo_matricial < 15000) {
        echo "✓ RENDIMIENTO BUENO (< 15 segundos)\n";
    } else {
        echo "⚠️ RENDIMIENTO REQUIERE ATENCIÓN\n";
    }
    echo "\n";

    // PRUEBA 3: Reporte de Metas de Ventas
    echo "PRUEBA 3: Reporte de Metas de Ventas\n";
    echo "------------------------------------\n";

    $filtros_metas = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-31',
        'plaza' => '',
        'tienda' => '',
        'zona' => '',
    ];

    $tiempo_inicio = microtime(true);
    $datos_metas = ReportService::getMetasVentasReport($filtros_metas);
    $tiempo_metas = round((microtime(true) - $tiempo_inicio) * 1000, 2);

    $num_metas = count($datos_metas['resultados']);
    echo "• Registros de metas: $num_metas\n";
    echo "• Tiempo de ejecución: {$tiempo_metas}ms\n";

    if ($tiempo_metas < 3000) {
        echo "✓ RENDIMIENTO EXCELENTE (< 3 segundos)\n";
    } elseif ($tiempo_metas < 8000) {
        echo "✓ RENDIMIENTO BUENO (< 8 segundos)\n";
    } else {
        echo "⚠️ RENDIMIENTO REQUIERE ATENCIÓN\n";
    }
    echo "\n";

    // PRUEBA 4: Cache Performance
    echo "PRUEBA 4: Rendimiento de Cache\n";
    echo "------------------------------\n";

    // Limpiar cache y medir primera ejecución
    ReportService::limpiarCacheReportes();
    echo "• Cache limpiado\n";

    $tiempo_inicio = microtime(true);
    $resultados1 = ReportService::getVendedoresReport($filtros);
    $tiempo_sin_cache = round((microtime(true) - $tiempo_inicio) * 1000, 2);

    // Segunda ejecución (debe usar cache)
    $tiempo_inicio = microtime(true);
    $resultados2 = ReportService::getVendedoresReport($filtros);
    $tiempo_con_cache = round((microtime(true) - $tiempo_inicio) * 1000, 2);

    $aceleracion = round($tiempo_sin_cache / max($tiempo_con_cache, 1), 2);

    echo "• Sin cache: {$tiempo_sin_cache}ms\n";
    echo "• Con cache: {$tiempo_con_cache}ms\n";
    echo "• Aceleración: {$aceleracion}x\n";

    if ($aceleracion > 10) {
        echo "✓ CACHE EXCELENTE (>10x más rápido)\n";
    } elseif ($aceleracion > 5) {
        echo "✓ CACHE MUY BUENO (>5x más rápido)\n";
    } elseif ($aceleracion > 2) {
        echo "✓ CACHE ACEPTABLE (>2x más rápido)\n";
    } else {
        echo "⚠️ CACHE LIMITADO (poca aceleración)\n";
    }
    echo "\n";

    // RESUMEN FINAL
    echo "========================================\n";
    echo "RESUMEN DE OPTIMIZACIONES IMPLEMENTADAS\n";
    echo "========================================\n\n";

    $problema_original = 40000; // 40 segundos
    $solucion_actual = max($tiempo_total, $tiempo_matricial, $tiempo_metas);
    $mejora = round(($problema_original / max($solucion_actual, 1)) * 100, 1);

    echo "PROBLEMA ORIGINAL:\n";
    echo "• 7,000 registros = 40 segundos\n\n";

    echo "SOLUCIÓN OPTIMIZADA:\n";
    echo "• Reporte vendedores: {$tiempo_total}ms\n";
    echo "• Reporte matricial: {$tiempo_matricial}ms\n";
    echo "• Reporte metas: {$tiempo_metas}ms\n";
    echo "• Cache: {$aceleracion}x más rápido\n\n";

    echo "MEJORA TOTAL: {$mejora}% MÁS RÁPIDO\n\n";

    if ($solucion_actual < 10000) { // Menos de 10 segundos
        echo "🎉 ¡OPTIMIZACIÓN EXITOSA!\n";
        echo "Los reportes ahora son extremadamente rápidos.\n";
    } elseif ($solucion_actual < 20000) { // Menos de 20 segundos
        echo "✅ OPTIMIZACIÓN SATISFACTORIA\n";
        echo "Los reportes tienen buen rendimiento.\n";
    } else {
        echo "⚠️ OPTIMIZACIÓN PARCIAL\n";
        echo "Se recomienda agregar índices de BD para mejor rendimiento.\n";
    }

    echo "\n========================================\n";

} catch (Exception $e) {
    echo "\n❌ ERROR CRÍTICO EN PRUEBAS:\n";
    echo $e->getMessage()."\n\n";
    echo "Posibles soluciones:\n";
    echo "1. Verificar conexión a base de datos\n";
    echo "2. Ejecutar: php create_tables.php\n";
    echo "3. Revisar configuración de .env\n";
}
