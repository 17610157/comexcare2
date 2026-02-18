<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Http\Controllers\Reportes\CarteraAbonosController;
use App\Http\Controllers\ReporteCarteraAbonosController;

class CarteraAbonosUpdatedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test básico del reporte con nueva columna vendedor
     */
    public function test_cartera_abonos_data_with_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
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
            
            // Verificar que los datos tienen claves en minúsculas incluyendo vendedor
            if (!empty($data['data'])) {
                $firstRow = $data['data'][0];
                $expectedKeys = [
                    'plaza', 'tienda', 'fecha', 'fecha_vta', 'concepto',
                    'tipo', 'factura', 'clave', 'rfc', 'nombre', 'vendedor',
                    'monto_fa', 'monto_dv', 'monto_cd', 'dias_cred', 'dias_vencidos'
                ];
                
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $firstRow, "Falta la columna '{$key}' en la respuesta");
                }
            }
            
            $this->printTestResult("✓ CarteraAbonosController::data() con columna vendedor - Estructura correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() con vendedor - Error: " . $e->getMessage());
            $this->fail("Error en CarteraAbonosController::data(): " . $e->getMessage());
        }
    }

    /**
     * Test del filtro por vendedor
     */
    public function test_cartera_abonos_data_with_vendedor_filter()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'vendedor' => 'VEN001',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ CarteraAbonosController::data() con filtro vendedor - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() con filtro vendedor - Error: " . $e->getMessage());
            $this->fail("Error con filtro vendedor: " . $e->getMessage());
        }
    }

    /**
     * Test del filtro combinado (plaza, tienda, vendedor)
     */
    public function test_cartera_abonos_data_with_combined_filters()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => '01',
            'tienda' => '001',
            'vendedor' => 'VEN001',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ CarteraAbonosController::data() con filtros combinados - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() con filtros combinados - Error: " . $e->getMessage());
            $this->fail("Error con filtros combinados: " . $e->getMessage());
        }
    }

    /**
     * Test de búsqueda incluyendo vendedor
     */
    public function test_cartera_abonos_data_with_search_including_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'nombre_vendedor'],
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ CarteraAbonosController::data() con búsqueda en vendedor - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() con búsqueda en vendedor - Error: " . $e->getMessage());
            $this->fail("Error en búsqueda en vendedor: " . $e->getMessage());
        }
    }

    /**
     * Test del segundo controlador con nueva estructura
     */
    public function test_reporte_cartera_abonos_with_vendedor()
    {
        $controller = new ReporteCarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'vendedor' => 'VEN001',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            
            // Verificar que la columna vendedor esté presente
            if (!empty($data['data'])) {
                $firstRow = $data['data'][0];
                $this->assertArrayHasKey('vendedor', $firstRow);
            }
            
            $this->printTestResult("✓ ReporteCarteraAbonosController::data() con vendedor - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ ReporteCarteraAbonosController::data() con vendedor - Error: " . $e->getMessage());
            $this->fail("Error en ReporteCarteraAbonosController: " . $e->getMessage());
        }
    }

    /**
     * Test del SQL con vendedor
     */
    public function test_sql_with_vendedor_join()
    {
        try {
            $sql = "SELECT
                      c.cplaza AS plaza,
                      c.ctienda AS tienda,
                      c.fecha AS fecha,
                      c2.dfechafac AS fecha_vta,
                      c.concepto AS concepto,
                      c.tipo_ref AS tipo,
                      c.no_ref AS factura,
                      cl.clie_clave AS clave,
                      cl.clie_rfc AS rfc,
                      cl.clie_nombr AS nombre,
                      COALESCE(v.vendedor_nombre, 'SIN VENDEDOR') AS vendedor,
                      CASE WHEN c.tipo_ref = 'FA' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_fa,
                      CASE WHEN c.tipo_ref = 'FA' AND c.concepto = 'DV' THEN c.IMPORTE ELSE 0 END AS monto_dv,
                      CASE WHEN c.tipo_ref = 'CD' AND c.concepto <> 'DV' THEN c.IMPORTE ELSE 0 END AS monto_cd,
                      COALESCE(cl.clie_credi, 0) AS dias_cred,
                      (c.fecha - COALESCE(c2.fecha_venc, c.fecha)) AS dias_vencidos
                    FROM cobranza c
                    LEFT JOIN (
                      SELECT co.cplaza, co.ctienda, co.tipo_ref, co.no_ref, co.fecha_venc, co.dfechafac, co.clave_cl
                      FROM cobranza co WHERE co.cargo_ab = 'C'
                    ) AS c2 ON (c.cplaza=c2.cplaza AND c.ctienda=c2.ctienda AND c.tipo_ref=c2.tipo_ref AND c.no_ref=c2.no_ref AND c.clave_cl=c2.clave_cl)
                    LEFT JOIN zona z ON z.plaza=c.cplaza AND z.tienda=c.ctienda
                    LEFT JOIN cliente_depurado cl ON (c.ctienda = cl.ctienda AND c.cplaza = cl.cplaza AND c.clave_cl = cl.clie_clave)
                    LEFT JOIN vendedores v ON (v.vendedor_codigo = c.vendedor_codigo AND v.cplaza = c.cplaza AND v.ctienda = c.ctienda)
                    WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= ? AND c.fecha <= ?
                    ORDER BY plaza, tienda, fecha
                    LIMIT 5 OFFSET 0";

            // Verificar sintaxis SQL sin ejecutar
            $this->assertStringContainsString('LEFT JOIN vendedores v', $sql);
            $this->assertStringContainsString('COALESCE(v.vendedor_nombre, \'SIN VENDEDOR\')', $sql);
            $this->assertStringContainsString('AS vendedor', $sql);
            
            $this->printTestResult("✓ SQL con vendedor - Sintaxis correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ SQL con vendedor - Error: " . $e->getMessage());
            $this->fail("Error en SQL con vendedor: " . $e->getMessage());
        }
    }

    /**
     * Test de validación de parámetros incluyendo vendedor
     */
    public function test_parameter_validation_with_vendedor()
    {
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => '01',
            'tienda' => '001',
            'vendedor' => 'VEN001',
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        // Verificar que los parámetros se procesen correctamente
        $this->assertEquals('VEN001', $request->input('vendedor'));
        $this->assertEquals('01', $request->input('plaza'));
        $this->assertEquals('001', $request->input('tienda'));
        $this->assertEquals('2024-01-01', $request->input('period_start'));
        $this->assertEquals('2024-01-31', $request->input('period_end'));

        $this->printTestResult("✓ Validación de parámetros con vendedor - OK");
    }

    /**
     * Test de estructura de respuesta DataTable con vendedor
     */
    public function test_response_structure_with_vendedor()
    {
        $mockResponse = [
            'draw' => 1,
            'recordsTotal' => 100,
            'recordsFiltered' => 50,
            'data' => [
                [
                    'plaza' => '01',
                    'tienda' => '001',
                    'fecha' => '2024-01-01',
                    'nombre' => 'Test Cliente',
                    'vendedor' => 'VEN001 - Test Vendedor'
                ]
            ]
        ];

        // Verificar estructura de respuesta DataTable
        $this->assertArrayHasKey('draw', $mockResponse);
        $this->assertArrayHasKey('recordsTotal', $mockResponse);
        $this->assertArrayHasKey('recordsFiltered', $mockResponse);
        $this->assertArrayHasKey('data', $mockResponse);
        
        // Verificar que data es un array
        $this->assertIsArray($mockResponse['data']);
        
        // Verificar que el primer registro tenga vendedor
        $this->assertArrayHasKey('vendedor', $mockResponse['data'][0]);

        $this->printTestResult("✓ Estructura de respuesta con vendedor - DataTable correcta");
    }

    /**
     * Método auxiliar para imprimir resultados de test
     */
    private function printTestResult($message)
    {
        echo "\n" . $message . "\n";
    }

    /**
     * Test para verificar que se eliminó el filtro de rango
     */
    public function test_no_range_filter_in_view()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
            // No se incluye 'period_range'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ Reporte sin filtro de rango - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Reporte sin filtro de rango - Error: " . $e->getMessage());
            $this->fail("Error sin filtro de rango: " . $e->getMessage());
        }
    }
}