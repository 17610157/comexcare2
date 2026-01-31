<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Tests\TestCase;
use App\Http\Controllers\Reportes\CarteraAbonosController;
use App\Http\Controllers\ReporteCarteraAbonosController;

class CarteraAbonosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test básico del método data de CarteraAbonosController
     */
    public function test_cartera_abonos_data_basic_request()
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
            
            // Verificar que los datos tienen claves en minúsculas
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
            }
            
            $this->printTestResult("✓ CarteraAbonosController::data() - Estructura correcta");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() - Error: " . $e->getMessage());
            $this->fail("Error en CarteraAbonosController::data(): " . $e->getMessage());
        }
    }

    /**
     * Test del método data con búsqueda multi-campo
     */
    public function test_cartera_abonos_data_with_search()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'test'],
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ CarteraAbonosController::data() con búsqueda - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() con búsqueda - Error: " . $e->getMessage());
            $this->fail("Error en búsqueda: " . $e->getMessage());
        }
    }

    /**
     * Test con filtros de plaza y tienda
     */
    public function test_cartera_abonos_data_with_filters()
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
            $this->printTestResult("✓ CarteraAbonosController::data() con filtros - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ CarteraAbonosController::data() con filtros - Error: " . $e->getMessage());
            $this->fail("Error con filtros: " . $e->getMessage());
        }
    }

    /**
     * Test del segundo controlador (ReporteCarteraAbonosController)
     */
    public function test_reporte_cartera_abonos_data()
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
            $this->printTestResult("✓ ReporteCarteraAbonosController::data() - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ ReporteCarteraAbonosController::data() - Error: " . $e->getMessage());
            $this->fail("Error en ReporteCarteraAbonosController: " . $e->getMessage());
        }
    }

    /**
     * Test directo de la consulta SQL
     */
    public function test_direct_sql_query()
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
                    LIMIT 10 OFFSET 0";

            $rows = DB::select($sql, ['2024-01-01', '2024-01-31']);
            
            $this->assertIsArray($rows);
            $this->printTestResult("✓ Consulta SQL directa - OK, " . count($rows) . " registros encontrados");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Consulta SQL directa - Error: " . $e->getMessage());
            $this->fail("Error en consulta SQL directa: " . $e->getMessage());
        }
    }

    /**
     * Test de generación de PDF
     */
    public function test_pdf_generation()
    {
        $controller = new CarteraAbonosController();
        $request = new Request([
            'period_start' => '2024-01-01',
            'period_end' => '2024-01-31'
        ]);

        try {
            $response = $controller->pdf($request);
            
            // La respuesta puede ser HTML (fallback) o PDF
            $this->assertContains($response->headers->get('content-type'), [
                'text/html',
                'application/pdf'
            ]);
            
            $this->printTestResult("✓ Generación PDF - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Generación PDF - Error: " . $e->getMessage());
            $this->fail("Error en generación PDF: " . $e->getMessage());
        }
    }

    /**
     * Test de validación de parámetros
     */
    public function test_parameter_validation()
    {
        $controller = new CarteraAbonosController();
        
        // Test sin parámetros obligatorios
        $request = new Request([]);
        
        try {
            $response = $controller->data($request);
            $data = $response->getData(true);
            
            $this->assertArrayHasKey('data', $data);
            $this->printTestResult("✓ Validación de parámetros sin datos - OK");
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Validación de parámetros - Error: " . $e->getMessage());
            $this->fail("Error en validación: " . $e->getMessage());
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
     * Test para verificar estructura de tablas requeridas
     */
    public function test_table_structure()
    {
        try {
            // Verificar que existan las tablas principales
            $tables = ['cobranza', 'zona', 'cliente_depurado'];
            
            foreach ($tables as $table) {
                try {
                    $count = DB::table($table)->count();
                    $this->printTestResult("✓ Tabla '{$table}' existe, {$count} registros");
                } catch (\Exception $e) {
                    $this->printTestResult("✗ Error en tabla '{$table}': " . $e->getMessage());
                    $this->fail("Tabla '{$table}' no accesible: " . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Error verificando estructura: " . $e->getMessage());
            $this->fail("Error en estructura de tablas: " . $e->getMessage());
        }
    }

    /**
     * Test para verificar columnas específicas de la tabla cobranza
     */
    public function test_cobranza_columns()
    {
        try {
            $columns = DB::getSchemaBuilder()->getColumnListing('cobranza');
            $requiredColumns = [
                'cplaza', 'ctienda', 'fecha', 'concepto', 'tipo_ref', 
                'no_ref', 'IMPORTE', 'cargo_ab', 'estado', 'cborrado', 'clave_cl'
            ];
            
            foreach ($requiredColumns as $column) {
                if (in_array($column, $columns)) {
                    $this->printTestResult("✓ Columna 'cobranza.{$column}' existe");
                } else {
                    $this->printTestResult("✗ Columna 'cobranza.{$column}' NO existe");
                    $this->fail("Columna requerida no existe: cobranza.{$column}");
                }
            }
            
        } catch (\Exception $e) {
            $this->printTestResult("✗ Error verificando columnas: " . $e->getMessage());
            $this->fail("Error verificando columnas: " . $e->getMessage());
        }
    }
}