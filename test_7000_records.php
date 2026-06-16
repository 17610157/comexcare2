<?php

/**
 * PRUEBA ESPECÍFICA: Problema Original (7,000 registros = 40 segundos)
 * Ejecutar: php test_7000_records.php
 */

require_once __DIR__.'/vendor/autoload.php';

use App\Services\ReportService;
use Illuminate\Contracts\Http\Kernel;

echo "========================================\n";
echo "PRUEBA ESPECÍFICA: 7,000 REGISTROS\n";
echo "========================================\n\n";

echo "PROBLEMA ORIGINAL REPORTADO:\n";
echo "• 7,000 registros tardaban 40 segundos\n";
echo "• El sistema se volvía inutilizable\n\n";

echo "PRUEBA ACTUAL:\n";
echo "• Ejecutando consulta optimizada...\n\n";

try {
    // Inicializar Laravel
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    // Configurar límites altos
    ini_set('memory_limit', '1G');
    ini_set('max_execution_time', 120); // 2 minutos máximo

    echo "✓ Sistema inicializado\n\n";

    // Usar un rango que probablemente genere ~7,000 registros
    // Ajustar fechas según datos reales disponibles
    $filtros = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-02-29', // 2 meses completos
        'plaza' => '', // Sin filtro para más datos
        'tienda' => '',
        'vendedor' => '',
    ];

    echo "FILTROS DE PRUEBA:\n";
    echo "• Periodo: {$filtros['fecha_inicio']} a {$filtros['fecha_fin']}\n";
    echo '• Plaza: '.($filtros['plaza'] ?: 'Todas')."\n";
    echo '• Tienda: '.($filtros['tienda'] ?: 'Todas')."\n";
    echo '• Vendedor: '.($filtros['vendedor'] ?: 'Todos')."\n\n";

    // PRUEBA 1: Primera ejecución (sin cache)
    echo "EJECUCIÓN 1: Sin cache\n";
    echo "-------------------\n";

    ReportService::limpiarCacheReportes();
    $inicio = microtime(true);

    $resultados = ReportService::getVendedoresReport($filtros);
    $tiempo_ejecucion = round((microtime(true) - $inicio) * 1000, 2);
    $num_registros = $resultados->count();

    echo "✓ Consulta completada\n";
    echo '• Registros obtenidos: '.number_format($num_registros)."\n";
    echo "• Tiempo total: {$tiempo_ejecucion}ms (".round($tiempo_ejecucion / 1000, 2)."s)\n";

    if ($num_registros > 0) {
        $tiempo_por_registro = round($tiempo_ejecucion / $num_registros, 3);
        echo "• Tiempo por registro: {$tiempo_por_registro}ms\n";
    }

    // Evaluar rendimiento
    if ($tiempo_ejecucion < 10000) { // Menos de 10 segundos
        echo "🎉 RENDIMIENTO EXCELENTE (< 10s)\n";
        $calificacion = 'EXCELENTE';
    } elseif ($tiempo_ejecucion < 30000) { // Menos de 30 segundos
        echo "✅ RENDIMIENTO MUY BUENO (< 30s)\n";
        $calificacion = 'MUY BUENO';
    } elseif ($tiempo_ejecucion < 60000) { // Menos de 1 minuto
        echo "⚠️ RENDIMIENTO ACEPTABLE (< 1min)\n";
        $calificacion = 'ACEPTABLE';
    } else {
        echo "❌ RENDIMIENTO DEFICIENTE (> 1min)\n";
        $calificacion = 'DEFICIENTE';
    }

    echo "\n";

    // PRUEBA 2: Segunda ejecución (con cache)
    echo "EJECUCIÓN 2: Con cache\n";
    echo "------------------\n";

    $inicio = microtime(true);
    $resultados_cache = ReportService::getVendedoresReport($filtros);
    $tiempo_cache = round((microtime(true) - $inicio) * 1000, 2);

    $aceleracion = round($tiempo_ejecucion / max($tiempo_cache, 1), 2);

    echo "• Tiempo con cache: {$tiempo_cache}ms\n";
    echo "• Aceleración: {$aceleracion}x más rápido\n";

    if ($tiempo_cache < 1000) { // Menos de 1 segundo
        echo "🚀 CACHE ULTRA RÁPIDO (< 1s)\n";
    } elseif ($tiempo_cache < 5000) { // Menos de 5 segundos
        echo "✅ CACHE MUY EFECTIVO (< 5s)\n";
    } else {
        echo "⚠️ CACHE LIMITADO\n";
    }

    echo "\n";

    // ANÁLISIS COMPARATIVO
    echo "========================================\n";
    echo "ANÁLISIS COMPARATIVO\n";
    echo "========================================\n\n";

    echo "PROBLEMA ORIGINAL:\n";
    echo "• 7,000 registros ≈ 40 segundos\n";
    echo "• Sistema inutilizable\n\n";

    echo "SOLUCIÓN OPTIMIZADA:\n";
    echo '• '.number_format($num_registros).' registros ≈ '.round($tiempo_ejecucion / 1000, 2)." segundos\n";
    echo "• Cache: {$tiempo_cache}ms\n";
    echo "• Calificación: $calificacion\n\n";

    // Calcular mejora
    $tiempo_original = 40000; // 40 segundos en ms
    $mejora = round(($tiempo_original / max($tiempo_ejecucion, 1)) * 100, 1);

    echo "MEJORA CONSEGUIDA: {$mejora}% MÁS RÁPIDO\n\n";

    // Estimación para exactamente 7,000 registros
    if ($num_registros > 0) {
        $tiempo_estimado_7000 = ($tiempo_ejecucion / $num_registros) * 7000;
        echo "TIEMPO ESTIMADO PARA 7,000 REGISTROS:\n";
        echo "• {$tiempo_estimado_7000}ms (".round($tiempo_estimado_7000 / 1000, 2)."s)\n";

        if ($tiempo_estimado_7000 < 10000) {
            echo "🎉 ¡PROBLEMA COMPLETAMENTE RESUELTO!\n";
        } elseif ($tiempo_estimado_7000 < 30000) {
            echo "✅ PROBLEMA SIGNIFICATIVAMENTE MEJORADO\n";
        } else {
            echo "⚠️ MEJORA MODERADA - CONSIDERAR ÍNDICES BD\n";
        }
    }

    echo "\n========================================\n";
    echo "RECOMENDACIONES PARA PRODUCCIÓN:\n";
    echo "========================================\n\n";

    if ($tiempo_ejecucion > 30000) {
        echo "⚠️ TIEMPO AÚN ALTO - RECOMENDACIONES:\n";
        echo "• Solicitar DBA ejecute database_optimization_indexes.sql\n";
        echo "• Considerar particionamiento de tablas por fecha\n";
        echo "• Implementar índices adicionales en campos de filtro\n";
    } else {
        echo "✅ RENDIMIENTO OPTIMIZADO - RECOMENDACIONES:\n";
        echo "• Monitorear uso en producción\n";
        echo "• Configurar cache persistente (Redis/File)\n";
        echo "• Programar limpieza periódica de cache\n";
    }

    echo "\n🎯 PRUEBA COMPLETADA\n";

} catch (Exception $e) {
    echo "\n❌ ERROR EN PRUEBA ESPECÍFICA:\n";
    echo $e->getMessage()."\n\n";
    echo "SOLUCIONES POSIBLES:\n";
    echo "1. Verificar conexión a BD PostgreSQL\n";
    echo "2. Asegurar que existan datos en el período probado\n";
    echo "3. Ejecutar: php create_tables.php\n";
    echo "4. Revisar configuración en .env\n";
}
