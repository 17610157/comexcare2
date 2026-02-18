<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Http\Controllers\Reportes\CarteraAbonosController;

class CarteraAbonosExportFinalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test de exportación a CSV sin vendedor
     */
    public function test_export_csv_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->exportCsv($request);
            
            // Verificar respuesta
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
            $this->assertStringContainsString('attachment; filename=', $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition'));
            
            // Verificar contenido CSV sin vendedor
            $content = $response->getContent();
            $this->assertStringContainsString('Plaza,Tienda,Fecha,Fecha Vta,Concepto', $content);
            $this->assertStringNotContainsString('Vendedor', $content); // No debe tener vendedor
            
            $this->printTestResult("✓ Exportación CSV sin vendedor - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación CSV sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en exportación CSV: " . $e->getMessage());
        }
    }

    /**
     * Test de exportación a Excel sin vendedor
     */
    public function test_export_excel_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->exportExcel($request);
            
            // Verificar respuesta
            $this->assertEquals(200, $response->getStatusCode());
            
            // Si Excel está disponible, debe ser un archivo Excel
            if (class_exists('\Maatwebsite\Excel\Facades\Excel')) {
                $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $response->headers->get('Content-Type'));
                $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
            } else {
                // Fallback a CSV
                $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
            }
            
            $this->printTestResult("✓ Exportación Excel sin vendedor - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación Excel sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en exportación Excel: " . $e->getMessage());
        }
    }

    /**
     * Test de exportación CSV con filtros sin vendedor
     */
    public function test_export_csv_with_filters_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'plaza' => '01',
            'tienda' => '001'
            // No se incluye vendedor
        ]);

        try {
            $response = $controller->exportCsv($request);
            
            $this->assertEquals(200, $response->getStatusCode());
            $content = $response->getContent();
            
            // Verificar que el CSV tenga la estructura correcta sin vendedor
            $this->assertStringContainsString('Plaza,Tienda,Fecha', $content);
            $this->assertStringNotContainsString('Vendedor', $content); // No debe tener vendedor
            
            $this->printTestResult("✓ Exportación CSV con filtros sin vendedor - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación CSV con filtros sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en exportación CSV con filtros: " . $e->getMessage());
        }
    }

    /**
     * Test de exportación Excel con filtros sin vendedor
     */
    public function test_export_excel_with_filters_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31',
            'plaza' => '01'
            // No se incluye vendedor
        ]);

        try {
            $response = $controller->exportExcel($request);
            
            $this->assertEquals(200, $response->getStatusCode());
            
            $this->printTestResult("✓ Exportación Excel con filtros sin vendedor - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación Excel con filtros sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en exportación Excel con filtros: " . $e->getMessage());
        }
    }

    /**
     * Test de exportación PDF (existente) sin vendedor
     */
    public function test_export_pdf_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->pdf($request);
            
            // Verificar respuesta
            $contentType = $response->headers->get('content-type');
            $this->assertTrue(
                in_array($contentType, ['text/html', 'application/pdf']),
                "Content-Type debe ser HTML o PDF, recibido: " . $contentType
            );
            
            $this->printTestResult("✓ Exportación PDF sin vendedor - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación PDF sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en exportación PDF: " . $e->getMessage());
        }
    }

    /**
     * Test de nombres de archivos generados (sin cambios)
     */
    public function test_export_filename_format_unchanged()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            // Test CSV filename
            $csvResponse = $controller->exportCsv($request);
            $csvDisposition = $csvResponse->headers->get('Content-Disposition');
            $this->assertStringContainsString('cartera_abonos_20240101_to_20240131.csv', $csvDisposition);
            
            // Test Excel filename
            $excelResponse = $controller->exportExcel($request);
            $excelDisposition = $excelResponse->headers->get('Content-Disposition');
            $this->assertStringContainsString('cartera_abonos_20240101_to_20240131.xlsx', $excelDisposition);
            
            $this->printTestResult("✓ Formato de nombres de archivo - Correcto");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Formato de nombres de archivo - Error: " . $e->getMessage());
            $this->fail("Error en formato de nombres: " . $e->getMessage());
        }
    }

    /**
     * Test de contenido CSV sin vendedor
     */
    public function test_csv_content_structure_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->exportCsv($request);
            $content = $response->getContent();
            
            // Verificar headers del CSV sin vendedor
            $expectedHeaders = [
                'Plaza', 'Tienda', 'Fecha', 'Fecha Vta', 'Concepto', 'Tipo', 
                'Factura', 'Clave', 'RFC', 'Nombre', // Sin Vendedor
                'Monto FA', 'Monto DV', 'Monto CD', 'Días Crédito', 'Días Vencidos'
            ];
            
            foreach ($expectedHeaders as $header) {
                $this->assertStringContainsString($header, $content, "Falta header: {$header}");
            }
            
            // Verificar explícitamente que no existan headers de vendedor
            $this->assertStringNotContainsString('Vendedor', $content);
            $this->assertStringNotContainsString('vendedor', $content);
            
            $this->printTestResult("✓ Estructura de contenido CSV sin vendedor - Correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Estructura de contenido CSV sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en estructura CSV: " . $e->getMessage());
        }
    }

    /**
     * Test de manejo de parámetros vacíos sin vendedor
     */
    public function test_export_with_empty_parameters_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([]); // Sin parámetros

        try {
            $response = $controller->exportCsv($request);
            
            $this->assertEquals(200, $response->getStatusCode());
            
            // Debe usar fechas por defecto (mes anterior)
            $disposition = $response->headers->get('Content-Disposition');
            $this->assertStringContainsString('cartera_abonos_', $disposition);
            
            $this->printTestResult("✓ Exportación con parámetros vacíos sin vendedor - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación con parámetros vacíos sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error con parámetros vacíos: " . $e->getMessage());
        }
    }

    /**
     * Test de parámetros por defecto en exportación
     */
    public function test_export_default_parameters_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([]); // Sin parámetros

        try {
            $response = $controller->exportCsv($request);
            
            $this->assertEquals(200, $response->getStatusCode());
            
            // Debe usar fechas por defecto (mes anterior)
            $disposition = $response->headers->get('Content-Disposition');
            $this->assertStringContainsString('cartera_abonos_', $disposition);
            
            $this->printTestResult("✓ Exportación con parámetros por defecto - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación con parámetros por defecto - Error: " . $e->getMessage());
            $this->fail("Error con parámetros por defecto: " . $e->getMessage());
        }
    }

    /**
     * Test de exportación con solo filtros de plaza y tienda
     */
    public function test_export_with_only_plaza_tienda_filters()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'plaza' => '01',
            'tienda' => '002',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
            // Sin vendedor
        ]);

        try {
            $response = $controller->exportCsv($request);
            
            $this->assertEquals(200, $response->getStatusCode());
            $content = $response->getContent();
            
            // Verificar estructura sin vendedor
            $this->assertStringNotContainsString('Vendedor', $content);
            $this->assertStringContainsString('Plaza,Tienda', $content);
            
            $this->printTestResult("✓ Exportación con filtros plaza/tienda sin vendedor - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Exportación con filtros plaza/tienda sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error con filtros plaza/tienda: " . $e->getMessage());
        }
    }

    /**
     * Método auxiliar para imprimir resultados de test
     */
    private function printTestResult($message)
    {
        echo "\n" . $message . "\n";
    }
}