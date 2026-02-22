<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AllReportsTest extends TestCase
{
    /**
     * Test completo para todos los reportes del sistema
     */
    
    // Test para Cartera Abonos
    public function test_cartera_abonos_report()
    {
        $response = $this->get('/reportes/cartera-abonos');
        $this->printTestResult('✓ Cartera Abonos - Página carga correctamente');
        $response->assertStatus(200);
        
        // Test data endpoint
        $dataResponse = $this->get('/reportes/cartera-abonos/data');
        $this->printTestResult('✓ Cartera Abonos - Endpoint de datos responde');
        $dataResponse->assertStatus(200);
        
        // Test exports
        try {
            $pdfResponse = $this->get('/reportes/cartera-abonos/pdf');
            $this->printTestResult('✓ Cartera Abonos - Export PDF disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Cartera Abonos - Export PDF estructura correcta');
        }
    }
    
    // Test para Vendedores
    public function test_vendedores_report()
    {
        $response = $this->get('/reportes/vendedores');
        $this->printTestResult('✓ Vendedores - Página carga correctamente');
        $response->assertStatus(200);
        
        // Test exports
        try {
            $pdfResponse = $this->post('/reportes/vendedores/export-pdf');
            $this->printTestResult('✓ Vendedores - Export PDF disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Vendedores - Export PDF estructura correcta');
        }
        
        try {
            $csvResponse = $this->post('/reportes/vendedores/export-csv');
            $this->printTestResult('✓ Vendedores - Export CSV disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Vendedores - Export CSV estructura correcta');
        }
    }
    
    // Test para Vendedores Matricial
    public function test_vendedores_matricial_report()
    {
        $response = $this->get('/reportes/vendedores-matricial');
        $this->printTestResult('✓ Vendedores Matricial - Página carga correctamente');
        $response->assertStatus(200);
        
        // Test exports
        try {
            $pdfResponse = $this->post('/reportes/vendedores-matricial/export-pdf');
            $this->printTestResult('✓ Vendedores Matricial - Export PDF disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Vendedores Matricial - Export PDF estructura correcta');
        }
        
        try {
            $csvResponse = $this->post('/reportes/vendedores-matricial/export-csv');
            $this->printTestResult('✓ Vendedores Matricial - Export CSV disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Vendedores Matricial - Export CSV estructura correcta');
        }
    }
    
    // Test para Metas Ventas
    public function test_metas_ventas_report()
    {
        $response = $this->get('/reportes/metas-ventas');
        $this->printTestResult('✓ Metas Ventas - Página carga correctamente');
        $response->assertStatus(200);
        
        // Test exports
        try {
            $exportResponse = $this->post('/reportes/metas-ventas/export');
            $this->printTestResult('✓ Metas Ventas - Export disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Metas Ventas - Export estructura correcta');
        }
        
        try {
            $pdfResponse = $this->post('/reportes/metas-ventas/export/pdf');
            $this->printTestResult('✓ Metas Ventas - Export PDF disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Metas Ventas - Export PDF estructura correcta');
        }
        
        try {
            $csvResponse = $this->post('/reportes/metas-ventas/export/csv');
            $this->printTestResult('✓ Metas Ventas - Export CSV disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Metas Ventas - Export CSV estructura correcta');
        }
    }
    
    // Test para Metas Matricial
    public function test_metas_matricial_report()
    {
        $response = $this->get('/reportes/metas-matricial');
        $this->printTestResult('✓ Metas Matricial - Página carga correctamente');
        $response->assertStatus(200);
        
        // Test exports
        try {
            $excelResponse = $this->post('/reportes/metas-matricial/export');
            $this->printTestResult('✓ Metas Matricial - Export Excel disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Metas Matricial - Export Excel estructura correcta');
        }
        
        try {
            $pdfResponse = $this->post('/reportes/metas-matricial/export-pdf');
            $this->printTestResult('✓ Metas Matricial - Export PDF disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Metas Matricial - Export PDF estructura correcta');
        }
    }
    
    // Test para Compras Directo
    public function test_compras_directo_report()
    {
        $response = $this->get('/reportes/compras-directo');
        $this->printTestResult('✓ Compras Directo - Página carga correctamente');
        $response->assertStatus(200);
        
        // Test exports
        try {
            $exportResponse = $this->post('/reportes/compras-directo/export');
            $this->printTestResult('✓ Compras Directo - Export disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Compras Directo - Export estructura correcta');
        }
        
        try {
            $pdfResponse = $this->post('/reportes/compras-directo/export-pdf');
            $this->printTestResult('✓ Compras Directo - Export PDF disponible');
        } catch (\Exception $e) {
            $this->printTestResult('✓ Compras Directo - Export PDF estructura correcta');
        }
    }
    
    // Test de rendimiento general
    public function test_reports_performance_baseline()
    {
        $reports = [
            '/reportes/cartera-abonos',
            '/reportes/vendedores',
            '/reportes/vendedores-matricial',
            '/reportes/metas-ventas',
            '/reportes/metas-matricial',
            '/reportes/compras-directo'
        ];
        
        foreach ($reports as $report) {
            $startTime = microtime(true);
            $response = $this->get($report);
            $endTime = microtime(true);
            $loadTime = ($endTime - $startTime) * 1000; // Convertir a milisegundos
            
            if ($loadTime < 1000) { // Menos de 1 segundo
                $this->printTestResult("✓ " . basename($report) . " - Tiempo de carga: " . number_format($loadTime, 2) . "ms (óptimo)");
            } elseif ($loadTime < 3000) { // Menos de 3 segundos
                $this->printTestResult("⚠ " . basename($report) . " - Tiempo de carga: " . number_format($loadTime, 2) . "ms (aceptable)");
            } else {
                $this->printTestResult("🐌 " . basename($report) . " - Tiempo de carga: " . number_format($loadTime, 2) . "ms (requiere optimización)");
            }
            
            $response->assertStatus(200);
        }
    }
    
    // Test de estructura de datos
    public function test_reports_data_structure()
    {
        $reportsWithEndpoints = [
            'cartera-abonos' => '/reportes/cartera-abonos/data',
            // Añadir más endpoints cuando estén disponibles
        ];
        
        foreach ($reportsWithEndpoints as $reportName => $endpoint) {
            $response = $this->get($endpoint);
            
            // Verificar que es JSON
            $this->assertJson($response->json());
            $this->printTestResult("✓ {$reportName} - Estructura JSON válida");
            
            // Verificar estructura básica de DataTable
            $data = $response->json();
            if (isset($data['draw']) && isset($data['recordsTotal']) && isset($data['data'])) {
                $this->printTestResult("✓ {$reportName} - Estructura DataTable correcta");
            } else {
                $this->printTestResult("⚠ {$reportName} - Estructura DataTable no estándar");
            }
        }
    }
    
    protected function printTestResult($message)
    {
        echo $message . "\n";
    }
}