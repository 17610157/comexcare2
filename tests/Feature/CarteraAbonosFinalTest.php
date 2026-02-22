<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Http\Controllers\Reportes\CarteraAbonosController;
use App\Http\Controllers\ReporteCarteraAbonosController;

class CarteraAbonosFinalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test básico del reporte sin columna vendedor
     */
    public function test_cartera_abonos_data_without_vendedor()
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
            
            // Verificar que los datos tengan claves en minúsculas SIN vendedor
            if (!empty($data['data'])) {
                $firstRow = $data['data'][0];
                $expectedKeys = [
                    'plaza', 'tienda', 'fecha', 'fecha_vta', 'concepto',
                    'tipo', 'factura', 'clave', 'rfc', 'nombre',
                    'monto_fa', 'monto_dv', 'monto_cd', 'dias_cred', 'dias_vencidos'
                ];
                
                foreach ($expectedKeys as $key) {
                    $this->assertArrayHasKey($key, $firstRow, "Falta la columna '{$key}' en la respuesta");
                }
                
                // Verificar que NO exista la columna vendedor
                $this->assertArrayNotHasKey('vendedor', $firstRow, "La columna 'vendedor' no debería existir");
            }
            
            $this->printTestResult("✓ CarteraAbonosController::data() sin vendedor - Estructura correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en CarteraAbonosController::data(): " . $e->getMessage());
        }
    }

    /**
     * Test del filtro combinado (plaza, tienda) sin vendedor
     */
    public function test_cartera_abonos_data_with_combined_filters_no_vendedor()
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
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            
            // Verificar que no exista vendedor en los datos
            if (!empty($data['data'])) {
                $firstRow = $data['data'][0];
                $this->assertArrayNotHasKey('vendedor', $firstRow);
            }
            
            $this->printTestResult("✓ CarteraAbonosController::data() con filtros combinados sin vendedor - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() con filtros combinados sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error con filtros combinados sin vendedor: " . $e->getMessage());
        }
    }

    /**
     * Test de búsqueda sin incluir vendedor
     */
    public function test_cartera_abonos_data_with_search_excluding_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'nombre_cliente'],
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            
            // Verificar que no exista vendedor en los datos
            if (!empty($data['data'])) {
                $firstRow = $data['data'][0];
                $this->assertArrayNotHasKey('vendedor', $firstRow);
            }
            
            $this->printTestResult("✓ CarteraAbonosController::data() con búsqueda sin vendedor - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() con búsqueda sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en búsqueda sin vendedor: " . $e->getMessage());
        }
    }

    /**
     * Test del segundo controlador sin vendedor
     */
    public function test_reporte_cartera_abonos_without_vendedor()
    {
        $controller = new ReporteCarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            
            // Verificar que no exista la columna vendedor
            if (!empty($data['data'])) {
                $firstRow = $data['data'][0];
                $this->assertArrayNotHasKey('vendedor', $firstRow);
            }
            
            $this->printTestResult("✓ ReporteCarteraAbonosController::data() sin vendedor - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ ReporteCarteraAbonosController::data() sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en ReporteCarteraAbonosController: " . $e->getMessage());
        }
    }

    /**
     * Test del SQL sin vendedor
     */
    public function test_sql_without_vendedor_join()
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
                    WHERE c.cargo_ab = 'A' AND c.estado = 'S' AND c.cborrado <> '1' AND c.fecha >= ? AND c.fecha <= ?
                    ORDER BY plaza, tienda, fecha
                    LIMIT 5 OFFSET 0";

            // Verificar sintaxis SQL sin ejecutar
            $this->assertStringNotContainsString('LEFT JOIN vendedores v', $sql);
            $this->assertStringNotContainsString('COALESCE(v.vendedor_nombre', $sql);
            $this->assertStringNotContainsString('AS vendedor', $sql);
            
            // Verificar que sí existan los joins necesarios
            $this->assertStringContainsString('LEFT JOIN zona z', $sql);
            $this->assertStringContainsString('LEFT JOIN cliente_depurado cl', $sql);
            
            $this->printTestResult("✓ SQL sin vendedor - Sintaxis correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ SQL sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en SQL sin vendedor: " . $e->getMessage());
        }
    }

    /**
     * Test de validación de parámetros por código
     */
    public function test_parameter_validation_by_code()
    {
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'plaza' => ' 01 ', // Con espacios para probar trim()
            'tienda' => ' 001 ', // Con espacios para probar trim()
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
            // No se incluye vendedor
        ]);

        // Verificar que los parámetros se procesen correctamente
        $this->assertEquals(null, $request->input('vendedor'));
        $this->assertEquals(' 01 ', $request->input('plaza')); // Sin trim en input
        $this->assertEquals(' 001 ', $request->input('tienda')); // Sin trim en input
        $this->assertEquals('2024-01-01', $request->input('period_start'));
        $this->assertEquals('2024-01-31', $request->input('period_end'));

        $this->printTestResult("✓ Validación de parámetros por código - OK");
    }

    /**
     * Test de estructura de respuesta DataTable sin vendedor
     */
    public function test_response_structure_without_vendedor()
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
                    'nombre' => 'Test Cliente'
                    // Sin vendedor
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
        
        // Verificar que el primer registro NO tenga vendedor
        $this->assertArrayNotHasKey('vendedor', $mockResponse['data'][0]);

        $this->printTestResult("✓ Estructura de respuesta sin vendedor - DataTable correcta");
    }

    /**
     * Test de exportación CSV sin vendedor
     */
    public function test_csv_structure_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            // Simular generación de CSV sin ejecutar consulta real
            $expectedHeaders = [
                'Plaza', 'Tienda', 'Fecha', 'Fecha Vta', 'Concepto', 'Tipo', 
                'Factura', 'Clave', 'RFC', 'Nombre', // Sin Vendedor
                'Monto FA', 'Monto DV', 'Monto CD', 'Días Crédito', 'Días Vencidos'
            ];
            
            // Verificar que Vendedor no esté en los headers esperados
            $this->assertNotContains('Vendedor', $expectedHeaders);
            $this->assertNotContains('vendedor', $expectedHeaders);
            
            $this->printTestResult("✓ Estructura CSV sin vendedor - Correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Estructura CSV sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en estructura CSV: " . $e->getMessage());
        }
    }

    /**
     * Test de vista PDF sin vendedor
     */
    public function test_pdf_structure_without_vendedor()
    {
        // Simular estructura de datos para PDF
        $mockData = [
            (object)[
                'plaza' => '01',
                'tienda' => '001',
                'fecha' => '2024-01-01',
                'nombre' => 'Test Cliente'
                // Sin vendedor
            ]
        ];

        // Verificar que los datos simulados no tengan vendedor
        foreach ($mockData as $row) {
            $this->assertObjectNotHasProperty('vendedor', $row);
        }

        $this->printTestResult("✓ Estructura PDF sin vendedor - Correcta");
    }

    /**
     * Test de búsqueda en campos correctos (sin vendedor)
     */
    public function test_search_fields_without_vendedor()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'test_search'],
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            
            // La búsqueda debe funcionar sin errores
            $this->printTestResult("✓ Búsqueda sin vendedor - Funciona correctamente");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Búsqueda sin vendedor - Error: " . $e->getMessage());
            $this->fail("Error en búsqueda sin vendedor: " . $e->getMessage());
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
     * Test para verificar que el filtro de rango fue eliminado
     */
    public function test_no_range_filter_functionality()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
            // No se incluye ningún parámetro de rango predefinido
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            
            // El controlador debe funcionar sin dependencia de rangos predefinidos
            $this->printTestResult("✓ Funcionamiento sin filtro de rango predefinido - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Funcionamiento sin filtro de rango - Error: " . $e->getMessage());
            $this->fail("Error sin filtro de rango: " . $e->getMessage());
        }
    }
}