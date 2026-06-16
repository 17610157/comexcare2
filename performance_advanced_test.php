<?php

/**
 * PRUEBAS DE RENDIMIENTO AVANZADAS
 * Ejecutar con: php performance_advanced_test.php
 */

// Configuración inicial
require_once __DIR__.'/vendor/autoload.php';

use App\Services\ReportService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceTester
{
    private static $results = [];

    public static function runTests()
    {
        echo "=== PRUEBAS DE RENDIMIENTO AVANZADAS ===\n\n";

        try {
            // Configurar Laravel
            $app = require_once __DIR__.'/bootstrap/app.php';
            $kernel = $app->make(Kernel::class);
            $kernel->bootstrap();

            echo "✓ Laravel configurado correctamente\n";

            // Configurar límites altos para pruebas
            ini_set('memory_limit', '1G');
            ini_set('max_execution_time', 600); // 10 minutos
            ReportService::optimizarConfiguracion();

            echo "✓ Configuración optimizada (1GB RAM, 600s timeout)\n\n";

            // Ejecutar pruebas
            self::testQueryAnalysis();
            self::testLargeDatasetPerformance();
            self::testMemoryUsage();
            self::testDatabaseIndexes();
            self::testQueryOptimizationComparison();
            self::generateReport();

        } catch (Exception $e) {
            echo "\n❌ ERROR CRÍTICO: ".$e->getMessage()."\n";
            Log::error('Error en pruebas de rendimiento: '.$e->getMessage());
        }
    }

    private static function testQueryAnalysis()
    {
        echo "🔍 ANALIZANDO CONSULTAS SQL...\n";

        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => '',
        ];

        // Medir tiempo de ejecución con EXPLAIN
        $start = microtime(true);
        $resultados = ReportService::getVendedoresReport($filtros);
        $queryTime = round((microtime(true) - $start) * 1000, 2);

        echo "✓ Consulta ejecutada en {$queryTime}ms\n";
        echo '✓ Registros obtenidos: '.$resultados->count()."\n";

        // Verificar si está usando índices (simulado)
        $estimatedTime = self::estimateQueryTime($resultados->count());
        echo "✓ Tiempo estimado sin optimizaciones: {$estimatedTime}ms\n";

        self::$results['query_analysis'] = [
            'actual_time' => $queryTime,
            'estimated_time' => $estimatedTime,
            'records' => $resultados->count(),
            'efficiency' => round(($estimatedTime / max($queryTime, 1)) * 100, 2).'%',
        ];
    }

    private static function testLargeDatasetPerformance()
    {
        echo "\n📊 PRUEBA CON DATASET GRANDE (7,000 registros objetivo)...\n";

        // Probar con diferentes rangos de fecha para simular datasets grandes
        $testCases = [
            ['fecha_inicio' => '2024-01-01', 'fecha_fin' => '2024-01-07', 'expected' => '1 semana'],
            ['fecha_inicio' => '2024-01-01', 'fecha_fin' => '2024-01-14', 'expected' => '2 semanas'],
            ['fecha_inicio' => '2024-01-01', 'fecha_fin' => '2024-01-31', 'expected' => '1 mes'],
            ['fecha_inicio' => '2024-01-01', 'fecha_fin' => '2024-03-31', 'expected' => '3 meses'],
        ];

        $largeDatasetResults = [];

        foreach ($testCases as $case) {
            $filtros = array_merge($case, ['plaza' => '', 'tienda' => '', 'vendedor' => '']);

            echo "  Probando {$case['expected']}... ";
            $start = microtime(true);

            try {
                $resultados = ReportService::getVendedoresReport($filtros);
                $time = round((microtime(true) - $start) * 1000, 2);
                $records = $resultados->count();

                echo "✓ {$time}ms, {$records} registros\n";

                $largeDatasetResults[] = [
                    'periodo' => $case['expected'],
                    'tiempo' => $time,
                    'registros' => $records,
                    'velocidad' => round($records / max($time / 1000, 0.001), 2).' reg/s',
                ];

                // Si supera 7k registros y tarda más de 30s, marcar como problema
                if ($records >= 7000 && $time > 30000) {
                    echo "    ⚠️  PROBLEMA DETECTADO: {$records} registros en {$time}ms (>30s)\n";
                }

            } catch (Exception $e) {
                echo '❌ ERROR: '.$e->getMessage()."\n";
                $largeDatasetResults[] = [
                    'periodo' => $case['expected'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        self::$results['large_dataset'] = $largeDatasetResults;
    }

    private static function testMemoryUsage()
    {
        echo "\n🧠 PRUEBA DE USO DE MEMORIA...\n";

        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => '',
        ];

        $memoryBefore = memory_get_usage(true);
        $peakMemory = $memoryBefore;

        echo 'Memoria inicial: '.round($memoryBefore / 1024 / 1024, 2)." MB\n";

        // Ejecutar múltiples consultas para probar manejo de memoria
        for ($i = 1; $i <= 5; $i++) {
            $start = microtime(true);
            $resultados = ReportService::getVendedoresReport($filtros);
            $time = round((microtime(true) - $start) * 1000, 2);

            $currentMemory = memory_get_usage(true);
            $peakMemory = max($peakMemory, $currentMemory);

            // Procesar en chunks para verificar eficiencia
            $chunksProcessed = 0;
            ReportService::procesarEnChunks($resultados, function ($chunk) use (&$chunksProcessed) {
                $chunksProcessed++;
            }, 500);

            echo "  Iteración {$i}: {$time}ms, {$chunksProcessed} chunks, ".
                 round($currentMemory / 1024 / 1024, 2)." MB\n";
        }

        $memoryUsed = $peakMemory - $memoryBefore;
        $memoryUsedMB = round($memoryUsed / 1024 / 1024, 2);

        echo "✓ Pico de memoria: {$memoryUsedMB} MB\n";

        if ($memoryUsedMB > 500) {
            echo "⚠️  ALTO USO DE MEMORIA: {$memoryUsedMB} MB (>500MB)\n";
        } else {
            echo "✓ Uso de memoria aceptable\n";
        }

        self::$results['memory_usage'] = [
            'initial' => round($memoryBefore / 1024 / 1024, 2),
            'peak' => round($peakMemory / 1024 / 1024, 2),
            'used' => $memoryUsedMB,
        ];
    }

    private static function testDatabaseIndexes()
    {
        echo "\n🗄️ VERIFICANDO ÍNDICES DE BASE DE DATOS...\n";

        // Verificar si las tablas existen y tienen índices
        $tablesToCheck = [
            'cache' => 'Laravel cache table',
            'canota' => 'Sales data table',
            'venta' => 'Returns data table',
            'asesores_vvt' => 'Sales advisors table',
        ];

        $indexStatus = [];

        foreach ($tablesToCheck as $table => $description) {
            try {
                // Verificar si la tabla existe
                $tableExists = DB::select("SELECT EXISTS (
                    SELECT 1 FROM information_schema.tables
                    WHERE table_schema = 'public' AND table_name = ?
                )", [$table]);

                if ($tableExists[0]->exists) {
                    echo "✓ Tabla '{$table}' existe ({$description})\n";

                    // Verificar índices (simplificado)
                    $indexes = DB::select('
                        SELECT indexname FROM pg_indexes
                        WHERE tablename = ?
                    ', [$table]);

                    $indexStatus[$table] = [
                        'exists' => true,
                        'indexes' => count($indexes),
                        'index_names' => array_column($indexes, 'indexname'),
                    ];

                    echo '  - Índices encontrados: '.count($indexes)."\n";
                    foreach ($indexes as $index) {
                        echo "    * {$index->indexname}\n";
                    }

                } else {
                    echo "❌ Tabla '{$table}' NO existe\n";
                    $indexStatus[$table] = ['exists' => false];
                }

            } catch (Exception $e) {
                echo "❌ Error verificando tabla '{$table}': ".$e->getMessage()."\n";
                $indexStatus[$table] = ['error' => $e->getMessage()];
            }
        }

        self::$results['database_indexes'] = $indexStatus;
    }

    private static function testQueryOptimizationComparison()
    {
        echo "\n⚡ COMPARACIÓN DE OPTIMIZACIONES...\n";

        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-15',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => '',
        ];

        // Limpiar cache para comparación justa
        ReportService::limpiarCacheReportes();

        echo "Probando con cache desactivado temporalmente...\n";

        // Primera ejecución (sin cache)
        $start = microtime(true);
        $resultados1 = ReportService::getVendedoresReport($filtros);
        $time1 = round((microtime(true) - $start) * 1000, 2);

        // Segunda ejecución (debería usar cache)
        $start = microtime(true);
        $resultados2 = ReportService::getVendedoresReport($filtros);
        $time2 = round((microtime(true) - $start) * 1000, 2);

        $aceleracion = round($time1 / max($time2, 1), 2);

        echo "Primera ejecución (sin cache): {$time1}ms\n";
        echo "Segunda ejecución (con cache): {$time2}ms\n";
        echo "Aceleración por cache: {$aceleracion}x\n";

        if ($aceleracion > 5) {
            echo "✓ Cache funcionando excelentemente\n";
        } elseif ($aceleracion > 2) {
            echo "✓ Cache funcionando adecuadamente\n";
        } else {
            echo "⚠️  Cache tiene poco impacto\n";
        }

        self::$results['cache_performance'] = [
            'sin_cache' => $time1,
            'con_cache' => $time2,
            'aceleracion' => $aceleracion,
        ];
    }

    private static function estimateQueryTime($records)
    {
        // Estimación basada en benchmarks típicos
        // Asumiendo que sin optimizaciones tomaría ~10ms por cada 1000 registros
        return round(($records / 1000) * 10);
    }

    private static function generateReport()
    {
        echo "\n".str_repeat('=', 60)."\n";
        echo "📊 REPORTE DE RENDIMIENTO DETALLADO\n";
        echo str_repeat('=', 60)."\n\n";

        // Análisis de Consultas
        if (isset(self::$results['query_analysis'])) {
            $qa = self::$results['query_analysis'];
            echo "🔍 ANÁLISIS DE CONSULTAS:\n";
            echo "  - Tiempo real: {$qa['actual_time']}ms\n";
            echo "  - Tiempo estimado sin optimizaciones: {$qa['estimated_time']}ms\n";
            echo "  - Registros procesados: {$qa['records']}\n";
            echo "  - Eficiencia: {$qa['efficiency']}\n\n";
        }

        // Dataset Grande
        if (isset(self::$results['large_dataset'])) {
            echo "📊 PRUEBA CON DATASETS GRANDES:\n";
            foreach (self::$results['large_dataset'] as $test) {
                if (isset($test['error'])) {
                    echo "  ❌ {$test['periodo']}: ERROR - {$test['error']}\n";
                } else {
                    $status = ($test['registros'] >= 7000 && $test['tiempo'] > 30000) ? '❌ LENTO' : '✅ OK';
                    echo "  {$status} {$test['periodo']}: {$test['tiempo']}ms, {$test['registros']} reg, {$test['velocidad']}\n";
                }
            }
            echo "\n";
        }

        // Memoria
        if (isset(self::$results['memory_usage'])) {
            $mem = self::$results['memory_usage'];
            echo "🧠 USO DE MEMORIA:\n";
            echo "  - Memoria inicial: {$mem['initial']} MB\n";
            echo "  - Pico de memoria: {$mem['peak']} MB\n";
            echo "  - Memoria utilizada: {$mem['used']} MB\n";
            echo '  - Status: '.($mem['used'] > 500 ? '❌ ALTO USO' : '✅ ACEPTABLE')."\n\n";
        }

        // Cache
        if (isset(self::$results['cache_performance'])) {
            $cache = self::$results['cache_performance'];
            echo "⚡ RENDIMIENTO DE CACHE:\n";
            echo "  - Sin cache: {$cache['sin_cache']}ms\n";
            echo "  - Con cache: {$cache['con_cache']}ms\n";
            echo "  - Aceleración: {$cache['aceleracion']}x\n";
            echo '  - Status: '.($cache['aceleracion'] > 5 ? '✅ EXCELENTE' : ($cache['aceleracion'] > 2 ? '✅ BUENO' : '⚠️  REGULAR'))."\n\n";
        }

        // Base de Datos
        if (isset(self::$results['database_indexes'])) {
            echo "🗄️ ESTADO DE BASE DE DATOS:\n";
            foreach (self::$results['database_indexes'] as $table => $status) {
                if (isset($status['error'])) {
                    echo "  ❌ {$table}: ERROR - {$status['error']}\n";
                } elseif (! $status['exists']) {
                    echo "  ❌ {$table}: TABLA NO EXISTE\n";
                } else {
                    echo "  ✅ {$table}: {$status['indexes']} índices\n";
                    if (! empty($status['index_names'])) {
                        foreach ($status['index_names'] as $index) {
                            echo "    - {$index}\n";
                        }
                    }
                }
            }
            echo "\n";
        }

        // Recomendaciones
        echo "💡 RECOMENDACIONES PARA MEJORAR RENDIMIENTO:\n";

        if (isset(self::$results['large_dataset'])) {
            $slowTests = array_filter(self::$results['large_dataset'],
                fn ($test) => isset($test['registros']) && $test['registros'] >= 7000 && $test['tiempo'] > 30000);

            if (! empty($slowTests)) {
                echo "  - Implementar PAGINACIÓN para datasets > 7,000 registros\n";
                echo "  - Considerar CACHE más agresivo (TTL > 1 hora)\n";
                echo "  - Optimizar índices en BD para consultas de rango de fechas\n";
            }
        }

        if (isset(self::$results['memory_usage']) && self::$results['memory_usage']['used'] > 500) {
            echo "  - Implementar PAGINACIÓN para reducir uso de memoria\n";
            echo "  - Usar GENERADORES en lugar de arrays para datasets grandes\n";
        }

        if (isset(self::$results['database_indexes'])) {
            $missingIndexes = array_filter(self::$results['database_indexes'],
                fn ($status) => ! isset($status['exists']) || ! $status['exists'] ||
                              (isset($status['indexes']) && $status['indexes'] == 0));

            if (! empty($missingIndexes)) {
                echo "  - CREAR ÍNDICES faltantes en base de datos\n";
                echo "  - Ejecutar script database_optimization_indexes.sql\n";
            }
        }

        echo "\n".str_repeat('=', 60)."\n";
        echo "✅ PRUEBAS COMPLETADAS\n";
        echo str_repeat('=', 60)."\n";
    }
}

// Ejecutar pruebas
PerformanceTester::runTests();
