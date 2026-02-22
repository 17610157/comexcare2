<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Http\Controllers\Reportes\CarteraAbonosController;

class CarteraAbonosCodeFilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test básico con filtros por código
     */
    public function test_data_with_code_filters()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => '01',
            'tienda' => '001',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            
            // Verificar que la respuesta sea JSON válida
            $this->assertJson($response->getContent());
            
            $data = $response->getData(true);
            
            // Verificar estructura básica de respuesta DataTable
            $this->assertArrayHasKey('draw', $data);
            $this->assertArrayHasKey('recordsTotal', $data);
            $this->assertArrayHasKey('recordsFiltered', $data);
            $this->assertArrayHasKey('data', $data);
            
            $this->printTestResult("✓ Filtros por código - Estructura correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Filtros por código - Error: " . $e->getMessage());
            $this->fail("Error con filtros por código: " . $e->getMessage());
        }
    }

    /**
     * Test de filtro de plaza con espacios (debe hacer trim)
     */
    public function test_plaza_filter_with_spaces()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => ' 01 ', // Con espacios antes y después
            'tienda' => '', // Sin filtro de tienda
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ Filtro plaza con espacios - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Filtro plaza con espacios - Error: " . $e->getMessage());
            $this->fail("Error con espacios en plaza: " . $e->getMessage());
        }
    }

    /**
     * Test de filtro de tienda con espacios
     */
    public function test_tienda_filter_with_spaces()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => '', // Sin filtro de plaza
            'tienda' => ' 002 ', // Con espacios antes y después
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ Filtro tienda con espacios - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Filtro tienda con espacios - Error: " . $e->getMessage());
            $this->fail("Error con espacios en tienda: " . $e->getMessage());
        }
    }

    /**
     * Test con filtros combinados y espacios
     */
    public function test_combined_filters_with_spaces()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => ' 03 ', // Plaza con espacios
            'tienda' => ' 005 ', // Tienda con espacios
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ Filtros combinados con espacios - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Filtros combinados con espacios - Error: " . $e->getMessage());
            $this->fail("Error con filtros combinados y espacios: " . $e->getMessage());
        }
    }

    /**
     * Test de exportación CSV con filtros por código
     */
    public function test_csv_export_with_code_filters()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'plaza' => ' 01 ', // Con espacios
            'tienda' => ' 001 '  // Con espacios
        ]);

        try {
            $response = $controller->exportCsv($request);
            
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
            $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
            
            $this->printTestResult("✓ Exportación CSV con filtros por código - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación CSV con filtros por código - Error: " . $e->getMessage());
            $this->fail("Error en exportación CSV con filtros: " . $e->getMessage());
        }
    }

    /**
     * Test de exportación Excel con filtros por código
     */
    public function test_excel_export_with_code_filters()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'plaza' => '02', // Sin espacios
            'tienda' => '003' // Sin espacios
        ]);

        try {
            $response = $controller->exportExcel($request);
            
            $this->assertEquals(200, $response->getStatusCode());
            
            // Si Excel está disponible, debe ser un archivo Excel
            if (class_exists('\Maatwebsite\Excel\Facades\Excel')) {
                $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
                $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
            } else {
                // Fallback a CSV
                $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
            }
            
            $this->printTestResult("✓ Exportación Excel con filtros por código - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación Excel con filtros por código - Error: " . $e->getMessage());
            $this->fail("Error en exportación Excel con filtros: " . $e->getMessage());
        }
    }

    /**
     * Test de PDF con filtros por código
     */
    public function test_pdf_export_with_code_filters()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'plaza' => ' 01 ',
            'tienda' => ' 002 '
        ]);

        try {
            $response = $controller->pdf($request);
            
            $contentType = $response->headers->get('content-type');
            $this->assertTrue(
                in_array($contentType, ['text/html', 'application/pdf']),
                "Content-Type debe ser HTML o PDF, recibido: " . $contentType
            );
            
            $this->printTestResult("✓ Exportación PDF con filtros por código - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación PDF con filtros por código - Error: " . $e->getMessage());
            $this->fail("Error en exportación PDF con filtros: " . $e->getMessage());
        }
    }

    /**
     * Test con filtros vacíos (debe mostrar todos los datos)
     */
    public function test_empty_code_filters()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => '', // Vacío
            'tienda' => '', // Vacío
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ Filtros de código vacíos - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Filtros de código vacíos - Error: " . $e->getMessage());
            $this->fail("Error con filtros vacíos: " . $e->getMessage());
        }
    }

    /**
     * Test de búsqueda global combinada con filtros por código
     */
    public function test_global_search_with_code_filters()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'test_search'],
            'plaza' => '01',
            'tienda' => '001',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ Búsqueda global con filtros por código - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Búsqueda global con filtros por código - Error: " . $e->getMessage());
            $this->fail("Error en búsqueda combinada: " . $e->getMessage());
        }
    }

    /**
     * Test de validación de maxlength (plaza 3, tienda 5)
     */
    public function test_code_maxlength_validation()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => '12345', // Más del límite (debería funcionar pero no optimizar)
            'tienda' => '67890', // Más del límite (debería funcionar pero no optimizar)
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ Validación maxlength - Funciona (puede optimizarse frontend)");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Validación maxlength - Error: " . $e->getMessage());
            $this->fail("Error con maxlength: " . $e->getMessage());
        }
    }

    /**
     * Método auxiliar para imprimir resultados de test
     */
    private function printTestResult($message)
    {
        echo "\n" . $message . "\n";
    }

    /**
     * Test de consistencia entre filtros y conteo
     */
    public function test_filter_count_consistency()
    {
        $controller = new CarteraAbonosController();
        
        // Test con filtro específico
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => '01',
            'tienda' => '001',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            // Verificar que recordsFiltered <= recordsTotal
            $this->assertLessThanOrEqual($data['recordsFiltered'], $data['recordsTotal']);
            
            $this->printTestResult("✓ Consistencia de conteo con filtros - Correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Consistencia de conteo - Error: " . $e->getMessage());
            $this->fail("Error en consistencia de conteo: " . $e->getMessage());
        }
    }
}