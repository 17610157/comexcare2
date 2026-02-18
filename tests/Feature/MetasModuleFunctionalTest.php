<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MetasModuleFunctionalTest extends TestCase
{
    /**
     * Test funcional completo del módulo de metas con todas las nuevas características
     */
    
    public function test_vista_dinamica_toggle()
    {
        // Test 1: Por defecto debe mostrar vista de tarjeta
        $response = $this->get('/reportes/metas-ventas');
        $response->assertStatus(200);
        $response->assertSee('Vista de Tarjeta por defecto');
    }
    
    public function test_vista_tabla_toggle()
    {
        // Test 2: Cambiar a vista de tabla
        $response = $this->get('/reportes/metas-ventas?vista_tipo=tabla');
        $response->assertStatus(200);
        $response->assertSee('Vista de Tabla activa');
    }
    
    public function test_consulta_metas_dias_con_periodo_valido()
    {
        // Test 3: Consultar metas_dias con período válido
        $response = $this->post('/reportes/metas/consultar-datos', [
            'periodo' => '2024-01'
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'vista_tipo' => 'metas_dias',
            'periodo' => '2024-01',
            'resultados' => [],
            'mensaje_informativo' => ''
        ]);
    }
    
    public function test_consulta_metas_mias_con_periodo_invalido()
    {
        // Test 4: Consultar con período inválido
        $response = $this->post('/reportes/metas/consultar-datos', [
            'periodo' => '2024-13' // Formato inválido (13 meses)
        ]);
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'vista_tipo' => 'metas_dias',
            'periodo' => '2024-13',
            'resultados' => [],
            'mensaje_informativo' => 'El periodo debe tener formato YYYY-MM'
        ]);
    }
    
    public function test_consulta_metas_mensual_con_periodo_valido()
    {
        // Test 5: Consultar metas_mensual con período válido
        $response = $this->post('/reportes/metas/consultar-datos', [
            'periodo' => '2024-01'
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'vista_tipo' => 'metas_mensual',
            'periodo' => '2024-01',
            'resultados' => [],
            'mensaje_informativo' => ''
        ]);
    }
    
    public function test_consulta_metas_con_filtros_combinados()
    {
        // Test 6: Consulta con múltiples filtros
        $response = $this->post('/reportes/metas/consultar-datos', [
            'plaza' => 'A001',
            'tienda' => 'B001',
            'periodo' => '2024-01',
            'zona' => 'NORTE'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'vista_tipo' => 'metas_dias',
            'plaza' => 'A001',
            'tienda' => 'B001', 
            'periodo' => '2024-01',
            'zona' => 'NORTE'
        ]);
    }
    
    public function test_consulta_metas_sin_datos()
    {
        // Test 7: Consulta sin filtros (mostrar todos los datos)
        $response = $this->post('/reportes/metas/consultar-datos', []);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'vista_tipo' => 'metas_dias',
            'plaza' => '',
            'tienda' => '',
            'periodo' => '',
            'resultados' => [],
            'mensaje_informativo' => ''
        ]);
    }
    
    public function test_api_ventas_acumuladas()
    {
        // Test 8: API de ventas acumuladas
        $response = $this->get('/reportes/metas/venta-acumulada', [
            'fecha' => '20240115'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'plaza' => '',
            'tienda' => '',
            'fecha' => '20240115'
        ]);
    }
    
    public function test_exportaciones_excel()
    {
        // Test 9: Exportación Excel con filtros
        $response = $this->post('/reportes/metas-ventas/export', [
            'plaza' => 'A001',
            'fecha_inicio' => '2024-01',
            'fecha_fin' => '2024-31'
        ]);
        
        $response->assertStatus(200);
        $response->assertDownload('application/vnd.openxmlformats-officedocument.openxmlformats-officedocument.ms-excel.sheet.miml');
    }
    
    public function test_exportaciones_pdf()
    {
        // Test 10: Exportación PDF con filtros
        $response = $this->post('/reportes/metas-ventas/export-pdf', [
            'plaza' => 'A001',
            'fecha_inicio' => '2024-01',
            'fecha_fin' => '2024-31'
        ]);
        
        $response->assertStatus(200);
        $response->assertDownload('application/pdf');
    }
    
    public function test_vista_dinamica_seguridad()
    {
        // Test 11: Seguridad en el parámetro vista_tipo
        $response = $this->get('/reportes/metas-ventas?vista_tipo=malicious');
        
        $response->assertStatus(400);
        $response->assertJsonStructure([
            'error' => 'Tipo de vista no permitido'
        ]);
    }
    
    public function test_periodo_validacion_abierto()
    {
        // Test 12: Validar diferentes formatos de período
        $periodos_validos = [
            '2024-01', '2024-02', '2024-03', '2024-04', '2024-05', '12-2024', '06', '2024-07'
        ];
        
        foreach ($periodos_validos as $periodo) {
            $response = $this->post('/reportes/metas/consultar-datos', [
                'periodo' => $periodo,
                'vista_tipo' => 'metas_dias'
            ]);
            
            $response->assertStatus(200);
            $response->assertJsonStructure([
                'vista_tipo' => 'metas_dias',
                'periodo' => $periodo,
                'resultados' => [],
                'mensaje_informativo' => ''
            ]);
        }
    }
    
    public function test_alertas_informativas()
    {
        // Test 13: Verificar mensajes informativos
        $response = $this->post('/reportes/metas/consultar-datos', [
            'periodo' => '2024-01',
            'plaza' => 'Z999', // No existe
            'tienda' => 'T999' // No existe
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'vista_tipo' => 'metas_dias',
            'periodo' => '2024-01',
            'plaza' => 'Z999',
            'tienda' => 'T999', // No existe
            'resultados' => [],
            'mensaje_informativo' => 'No hay datos para el período especificado'
        ]);
    }
    
    /**
     * Test completo de integración del módulo
     */
    public function test_integracion_completa()
    {
        // Test 14: Flujo completo metas-ventas y metas-matricial
        $response = $this->get('/reportes/metas-ventas');
        $response->assertStatus(200);
        $response->assertSee('Vista principal con todos los botones');
        
        // Verificar que se puedan alternar las vistas
        $this->assertSee('Botón Ver Tabla');
        $this->assertSee('Botón Ver Tarjeta');
        $this->assertSee('Botón Tabla');
    }
    
    /**
     * Helper para impresión de resultados
     */
    protected function printTestResult($message)
    {
        echo $message . "\n";
    }
}