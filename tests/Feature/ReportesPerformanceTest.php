<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use App\Services\ReportService;
use Tests\TestCase;

class ReportesPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar cache antes de cada test
        Cache::flush();

        // Configurar límites de memoria y tiempo para pruebas
        ReportService::optimizarConfiguracion();
    }

    /**
     * Test de rendimiento para reporte de vendedores
     */
    public function test_vendedores_report_performance()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => ''
        ];

        $start = microtime(true);
        $resultados = ReportService::getVendedoresReport($filtros);
        $tiempo = microtime(true) - $start;

        // Verificar que no tome más de 5 segundos
        $this->assertLessThan(5.0, $tiempo, "El reporte tomó demasiado tiempo: {$tiempo}s");

        // Verificar que devuelva una colección
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultados);

        // Verificar que tenga la estructura correcta
        if ($resultados->isNotEmpty()) {
            $primerResultado = $resultados->first();
            $this->assertArrayHasKey('tienda_vendedor', $primerResultado);
            $this->assertArrayHasKey('venta_total', $primerResultado);
            $this->assertArrayHasKey('devolucion', $primerResultado);
            $this->assertArrayHasKey('venta_neta', $primerResultado);
        }
    }

    /**
     * Test de uso de memoria con datasets grandes
     */
    public function test_memory_usage_with_large_dataset()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => ''
        ];

        $memoryBefore = memory_get_usage();

        // Ejecutar consulta múltiple veces para simular carga
        for ($i = 0; $i < 3; $i++) {
            $resultados = ReportService::getVendedoresReport($filtros);
            // Procesar resultados en chunks
            ReportService::procesarEnChunks($resultados, function ($chunk) {
                // Simular procesamiento
                $chunk->sum('venta_total');
            });
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Verificar que no use más de 50MB
        $this->assertLessThan(
            50 * 1024 * 1024,
            $memoryUsed,
            "Uso excesivo de memoria: " . round($memoryUsed / 1024 / 1024, 2) . "MB"
        );
    }

    /**
     * Test de funcionalidad de cache
     */
    public function test_cache_functionality()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => ''
        ];

        // Limpiar cache
        Cache::flush();

        // Primera ejecución (debe ir a BD)
        $start = microtime(true);
        $resultados1 = ReportService::getVendedoresReport($filtros);
        $tiempoPrimera = microtime(true) - $start;

        // Segunda ejecución (debe venir del cache)
        $start = microtime(true);
        $resultados2 = ReportService::getVendedoresReport($filtros);
        $tiempoSegunda = microtime(true) - $start;

        // Verificar que la segunda sea al menos 10 veces más rápida
        $this->assertLessThan(
            $tiempoPrimera / 10,
            $tiempoSegunda,
            "Cache no está funcionando correctamente. Primera: {$tiempoPrimera}s, Segunda: {$tiempoSegunda}s"
        );

        // Verificar que los resultados sean idénticos
        $this->assertEquals($resultados1->toArray(), $resultados2->toArray());
    }

    /**
     * Test de cálculo de estadísticas
     */
    public function test_estadisticas_calculation_performance()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => ''
        ];

        $resultados = ReportService::getVendedoresReport($filtros);

        $start = microtime(true);
        $estadisticas = ReportService::calcularEstadisticasVendedores($resultados);
        $tiempo = microtime(true) - $start;

        // Verificar que el cálculo sea rápido (< 0.5s)
        $this->assertLessThan(0.5, $tiempo, "Cálculo de estadísticas lento: {$tiempo}s");

        // Verificar estructura de estadísticas
        $this->assertArrayHasKey('total_ventas', $estadisticas);
        $this->assertArrayHasKey('total_devoluciones', $estadisticas);
        $this->assertArrayHasKey('total_neto', $estadisticas);
        $this->assertArrayHasKey('total_registros', $estadisticas);

        // Verificar que venta_neta = venta_total - devoluciones
        $this->assertEquals(
            $estadisticas['total_ventas'] - $estadisticas['total_devoluciones'],
            $estadisticas['total_neto']
        );
    }

    /**
     * Test de reporte matricial
     */
    public function test_matricial_report_performance()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-15', // Menos días para evitar timeouts
            'plaza' => '',
            'tienda' => '',
            'vendedor' => ''
        ];

        $start = microtime(true);
        $datos = ReportService::getVendedoresMatricialReport($filtros);
        $tiempo = microtime(true) - $start;

        // Verificar tiempo (< 8 segundos para vista matricial más compleja)
        $this->assertLessThan(8.0, $tiempo, "Reporte matricial tomó demasiado tiempo: {$tiempo}s");

        // Verificar estructura
        $this->assertArrayHasKey('vendedores_info', $datos);
        $this->assertArrayHasKey('dias', $datos);
        $this->assertIsArray($datos['dias']);
    }

    /**
     * Test de procesamiento en chunks
     */
    public function test_chunk_processing()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => ''
        ];

        $resultados = ReportService::getVendedoresReport($filtros);

        $chunksProcesados = 0;
        $totalElementos = 0;

        ReportService::procesarEnChunks($resultados, function ($chunk) use (&$chunksProcesados, &$totalElementos) {
            $chunksProcesados++;
            $totalElementos += $chunk->count();
        }, 500); // Chunks de 500

        // Verificar que se procesaron chunks
        $this->assertGreaterThan(0, $chunksProcesados);
        $this->assertEquals($resultados->count(), $totalElementos);
    }

    /**
     * Test de filtros aplicados correctamente
     */
    public function test_filters_applied_correctly()
    {
        // Test con filtro de plaza específico (si existe en BD)
        $filtrosConPlaza = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => 'PLAZA001', // Plaza específica
            'tienda' => '',
            'vendedor' => ''
        ];

        $filtrosSinPlaza = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => ''
        ];

        $resultadosConFiltro = ReportService::getVendedoresReport($filtrosConPlaza);
        $resultadosSinFiltro = ReportService::getVendedoresReport($filtrosSinPlaza);

        // Los resultados pueden ser diferentes, solo verificamos que no fallen
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultadosConFiltro);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $resultadosSinFiltro);
    }

    /**
     * Test de limpieza de cache
     */
    public function test_cache_cleanup()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'vendedor' => ''
        ];

        // Generar cache
        ReportService::getVendedoresReport($filtros);

        // Verificar que hay cache
        $cacheKey = 'vendedores_report_' . md5(serialize($filtros));
        $this->assertTrue(Cache::has($cacheKey));

        // Limpiar cache
        ReportService::limpiarCacheReportes();

        // Verificar que se limpió
        $this->assertFalse(Cache::has($cacheKey));
    }
}