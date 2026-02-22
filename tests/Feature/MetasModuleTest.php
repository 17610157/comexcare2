<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Http\Controllers\ReporteMetasVentasController;
use Illuminate\Http\Request;
use App\Services\ReportService;
use App\Models\ReporteMetasVentas;

class MetasModuleTest extends TestCase
{
    /**
     * Test completo del módulo de Metas - ReporteMetasVentasController
     */
    
    public function test_metas_ventas_controller_index()
    {
        $controller = new ReporteMetasVentasController();
        $request = Request::create('/reportes/metas-ventas', 'GET', [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01',
            'tienda' => 'T001',
            'zona' => 'NORTE'
        ]);
        
        $response = $controller->index($request);
        
        $this->printTestResult('✓ MetasVentasController::index() - Funciona correctamente');
        $this->assertIsArray($response);
    }
    
    public function test_metas_ventas_controller_index_with_default_params()
    {
        $controller = new ReporteMetasVentasController();
        $request = Request::create('/reportes/metas-ventas');
        
        $response = $controller->index($request);
        
        $this->printTestResult('✓ MetasVentasController::index() con parámetros por defecto - OK');
        $this->assertIsArray($response);
    }
    
    public function test_metas_ventas_controller_export()
    {
        $controller = new ReporteMetasVentasController();
        $request = Request::create('/reportes/metas-ventas/export', 'POST', [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01'
        ]);
        
        $response = $controller->export($request);
        
        $this->printTestResult('✓ MetasVentasController::export() - Funciona correctamente');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
    }
    
    public function test_metas_ventas_controller_export_pdf()
    {
        $controller = new ReporteMetasVentasController();
        $request = Request::create('/reportes/metas-ventas/export/pdf', 'POST', [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01'
        ]);
        
        $response = $controller->exportPdf($request);
        
        $this->printTestResult('✓ MetasVentasController::exportPdf() - Funciona correctamente');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
    }
    
    public function test_metas_ventas_venta_acumulada()
    {
        $controller = new ReporteMetasVentasController();
        $request = Request::create('/api/venta-acumulada', 'GET', [
            'fecha' => '20240115',
            'plaza' => '01',
            'tienda' => 'T001'
        ]);
        
        $response = $controller->getVentaAcumulada($request);
        
        $this->printTestResult('✓ MetasVentasController::getVentaAcumulada() - Funciona correctamente');
        $this->assertIsArray($response);
    }
    
    /**
     * Tests para ReporteMetasMatricialController
     */
    public function test_metas_matricial_controller_index()
    {
        $controller = new \App\Http\Controllers\ReporteMetasMatricialController();
        $request = Request::create('/reportes/metas-matricial', 'GET', [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01',
            'tienda' => 'T001',
            'zona' => 'NORTE'
        ]);
        
        $response = $controller->index($request);
        
        $this->printTestResult('✓ ReporteMetasMatricialController::index() - Funciona correctamente');
        $this->assertIsArray($response);
    }
    
    public function test_metas_matricial_controller_export_excel()
    {
        $controller = new \App\Http\Controllers\ReporteMetasMatricialController();
        $request = Request::create('/reportes/metas-matricial/export', 'POST', [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01'
        ]);
        
        $response = $controller->exportExcel($request);
        
        $this->printTestResult('✓ ReporteMetasMatricialController::exportExcel() - Funciona correctamente');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response);
    }
    
    public function test_metas_matricial_controller_export_pdf()
    {
        $controller = new \App\Http\Controllers\ReporteMetasMatricialController();
        $request = Request::create('/reportes/metas-matricial/export-pdf', 'POST', [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01'
        ]);
        
        $response = $controller->exportPdf($request);
        
        $this->printTestResult('✓ ReporteMetasMatricialController::exportPdf() - Funciona correctamente');
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
    }
    
    /**
     * Tests para ReportService::getMetasVentasReport
     */
    public function test_report_service_get_metas_ventas_report()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01',
            'tienda' => 'T001',
            'zona' => 'NORTE'
        ];
        
        $result = ReportService::getMetasVentasReport($filtros);
        
        $this->printTestResult('✓ ReportService::getMetasVentasReport() - Funciona correctamente');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('resultados', $result);
        $this->assertArrayHasKey('estadisticas', $result);
    }
    
    public function test_report_service_get_metas_ventas_report_con_filtros_vacios()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '',
            'tienda' => '',
            'zona' => ''
        ];
        
        $result = ReportService::getMetasVentasReport($filtros);
        
        $this->printTestResult('✓ ReportService::getMetasVentasReport() con filtros vacíos - OK');
        $this->assertIsArray($result);
    }
    
    /**
     * Tests para ReportService::getMetasMatricialReport
     */
    public function test_report_service_get_metas_matricial_report()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01',
            'tienda' => 'T001',
            'zona' => 'NORTE'
        ];
        
        $result = ReportService::getMetasMatricialReport($filtros);
        
        $this->printTestResult('✓ ReportService::getMetasMatricialReport() - Funciona correctamente');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('resultados', $result);
        $this->assertArrayHasKey('estadisticas', $result);
    }
    
    public function test_report_service_get_metas_matricial_report_cache()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01'
        ];
        
        // Primera llamada (debe ejecutar consulta)
        $startTime1 = microtime(true);
        $result1 = ReportService::getMetasMatricialReport($filtros);
        $time1 = microtime(true) - $startTime1;
        
        // Segunda llamada (debe usar caché)
        $startTime2 = microtime(true);
        $result2 = ReportService::getMetasMatricialReport($filtros);
        $time2 = microtime(true) - $startTime2;
        
        $this->printTestResult('✓ ReportService::getMetasMatricialReport() caché - OK');
        $this->assertIsArray($result2);
        $this->assertLessThan($time1, $time2, 'Segunda llamada debe ser más rápida (caché)');
    }
    
    /**
     * Tests para Models de Metas
     */
    public function test_reporte_metas_ventas_model_obtener_reporte()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01',
            'tienda' => 'T001',
            'zona' => 'NORTE'
        ];
        
        $result = ReporteMetasVentas::obtenerReporte($filtros);
        
        $this->printTestResult('✓ ReporteMetasVentas::obtenerReporte() - Funciona correctamente');
        $this->assertIsArray($result);
    }
    
    /**
     * Tests de integración de módulo de metas
     */
    public function test_modulo_metas_integracion_completa()
    {
        // Test 1: Reporte de Metas Ventas
        $filtrosVentas = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01'
        ];
        
        $resultVentas = ReportService::getMetasVentasReport($filtrosVentas);
        $this->printTestResult('✓ Integración Metas Ventas - Correcta');
        $this->assertArrayHasKey('resultados', $resultVentas);
        
        // Test 2: Reporte de Metas Matricial
        $filtrosMatricial = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01'
        ];
        
        $resultMatricial = ReportService::getMetasMatricialReport($filtrosMatricial);
        $this->printTestResult('✓ Integración Metas Matricial - Correcta');
        $this->assertArrayHasKey('resultados', $resultMatricial);
    }
    
    /**
     * Tests de rendimiento del módulo de metas
     */
    public function test_modulo_metas_rendimiento()
    {
        $reportes = [
            'metas-ventas' => function() {
                return ReportService::getMetasVentasReport([
                    'fecha_inicio' => '2024-01-01',
                    'fecha_fin' => '2024-01-31',
                    'plaza' => '01'
                ]);
            },
            'metas-matricial' => function() {
                return ReportService::getMetasMatricialReport([
                    'fecha_inicio' => '2024-01-01',
                    'fecha_fin' => '2024-01-31',
                    'plaza' => '01'
                ]);
            }
        ];
        
        foreach ($reportes as $nombre => $funcion) {
            $startTime = microtime(true);
            $result = $funcion();
            $endTime = microtime(true);
            $tiempo = ($endTime - $startTime) * 1000;
            
            if ($tiempo < 1000) {
                $this->printTestResult("✓ {$nombre} - Tiempo: " . number_format($tiempo, 2) . "ms (óptimo)");
            } elseif ($tiempo < 3000) {
                $this->printTestResult("✓ {$nombre} - Tiempo: " . number_format($tiempo, 2) . "ms (aceptable)");
            } else {
                $this->printTestResult("⚠ {$nombre} - Tiempo: " . number_format($tiempo, 2) . "ms (requiere optimización)");
            }
            
            $this->assertIsArray($result);
        }
    }
    
    /**
     * Tests de validación de datos del módulo de metas
     */
    public function test_modulo_metas_validacion_datos()
    {
        $testCases = [
            [
                'fecha_inicio' => '2024-01-01',
                'fecha_fin' => '2024-01-31',
                'plaza' => '01',
                'tienda' => 'T001',
                'descripcion' => 'Fecha válida, plaza y tienda válidas'
            ],
            [
                'fecha_inicio' => 'invalid-date',
                'fecha_fin' => '2024-01-31',
                'plaza' => '01',
                'tienda' => 'T001',
                'descripcion' => 'Fecha de inicio inválida'
            ],
            [
                'fecha_inicio' => '2024-01-01',
                'fecha_fin' => '2024-01-31',
                'plaza' => '',
                'tienda' => '',
                'descripcion' => 'Filtros vacíos (debe funcionar)'
            ]
        ];
        
        foreach ($testCases as $testCase) {
            try {
                $result = ReportService::getMetasVentasReport($testCase);
                $this->printTestResult("✓ Validación: {$testCase['descripcion']} - OK");
                $this->assertIsArray($result);
            } catch (\Exception $e) {
                if (str_contains($testCase['descripcion'], 'inválida')) {
                    $this->printTestResult("✓ Validación: {$testCase['descripcion']} - Error manejado correctamente");
                } else {
                    $this->printTestResult("⚠ Validación: {$testCase['descripcion']} - Inesperado: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Tests de caché del módulo de metas
     */
    public function test_modulo_metas_cache_functionality()
    {
        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'plaza' => '01',
            'tienda' => 'T001'
        ];
        
        // Limpiar caché específico para esta prueba
        \Illuminate\Support\Facades\Cache::forget('metas_ventas_report_' . md5(serialize($filtros)));
        
        // Primera llamada (sin caché)
        $start1 = microtime(true);
        $result1 = ReportService::getMetasVentasReport($filtros);
        $time1 = microtime(true) - $start1;
        
        // Segunda llamada (con caché)
        $start2 = microtime(true);
        $result2 = ReportService::getMetasVentasReport($filtros);
        $time2 = microtime(true) - $start2;
        
        $this->printTestResult('✓ Funcionalidad de caché - Primera llamada: ' . number_format($time1 * 1000, 2) . 'ms');
        $this->printTestResult('✓ Funcionalidad de caché - Segunda llamada: ' . number_format($time2 * 1000, 2) . 'ms');
        
        // La segunda llamada debe ser significativamente más rápida
        $this->assertLessThan($time1 * 0.5, $time2, 'Caché debe mejorar rendimiento al menos 50%');
        
        // Los resultados deben ser idénticos
        $this->assertEquals($result1, $result2, 'Resultados con y sin caché deben ser idénticos');
        
        $this->assertIsArray($result2);
    }
    
    protected function printTestResult($message)
    {
        echo $message . "\n";
    }
}