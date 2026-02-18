<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CarteraAbonosFrontendTest extends TestCase
{
    /**
     * Test para verificar que el HTML y JavaScript se generan correctamente
     */
    
    public function test_html_contains_required_elements()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que los botones existen
        $response->assertSee('btn_search');
        $response->assertSee('btn_refresh');
        $response->assertSee('btn_reset_filters');
        $response->assertSee('btn_pdf');
        
        // Verificar que los inputs existen
        $response->assertSee('plaza');
        $response->assertSee('tienda');
        
        // Verificar patrones de validación HTML5
        $response->assertSee('pattern="[A-Z0-9]{5}"');
        $response->assertSee('pattern="[A-Z0-9]{1,10}"');
        $response->assertSee('maxlength="5"');
        $response->assertSee('maxlength="10"');
        
        $this->printTestResult('✓ HTML contiene todos los elementos requeridos - OK');
        $response->assertStatus(200);
    }
    
    public function test_javascript_validation_functions_exist()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que las funciones JavaScript existen
        $response->assertSee('validateCodeInput');
        $response->assertSee('showInputError');
        $response->assertSee('performSearch');
        
        $this->printTestResult('✓ Funciones JavaScript de validación - Presentes');
        $response->assertStatus(200);
    }
    
    public function test_button_event_handlers()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que los event handlers están configurados
        $response->assertSee('btn_search');
        $response->assertSee('on(');
        $response->assertSee('btn_refresh');
        $response->assertSee('btn_reset_filters');
        $response->assertSee('btn_pdf');
        
        $this->printTestResult('✓ Event handlers de botones - Configurados');
        $response->assertStatus(200);
    }
    
    public function test_input_validation_patterns()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar patrones específicos
        $response->assertSee('title="5 caracteres: letras mayúsculas y números"');
        $response->assertSee('title="Hasta 10 caracteres: letras mayúsculas y números"');
        $response->assertSee('placeholder="Ej: A001"');
        $response->assertSee('placeholder="Ej: B001"');
        
        $this->printTestResult('✓ Patrones de validación y placeholders - Correctos');
        $response->assertStatus(200);
    }
    
    public function test_css_styles_included()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que los estilos CSS están incluidos
        $response->assertSee('code-filter-tooltip');
        $response->assertSee('code-filter-input');
        
        $this->printTestResult('✓ Estilos CSS para validación - Incluidos');
        $response->assertStatus(200);
    }
    
    public function test_data_table_initialization()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que DataTable está configurado correctamente
        $response->assertSee('report-table');
        $response->assertSee('ajax: {');
        $response->assertSee('/reportes/cartera-abonos/data');
        
        $this->printTestResult('✓ Inicialización de DataTable - Correcta');
        $response->assertStatus(200);
    }
    
    public function test_filter_parameter_passing()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que los parámetros se pasan correctamente
        $response->assertSee("d.plaza = $('#plaza').val()");
        $response->assertSee("d.tienda = $('#tienda').val()");
        $response->assertSee("d.period_start = $('#period_start').val()");
        $response->assertSee("d.period_end = $('#period_end').val()");
        
        $this->printTestResult('✓ Paso de parámetros de filtros - Correcto');
        $response->assertStatus(200);
    }
    
    public function test_enter_key_functionality()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que la funcionalidad de Enter está configurada
        $response->assertSee('if (e.which === 13)');
        $response->assertSee('performSearch()');
        
        $this->printTestResult('✓ Funcionalidad de tecla Enter - Configurada');
        $response->assertStatus(200);
    }
    
    public function test_escape_key_functionality()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que la funcionalidad de ESC está configurada
        $response->assertSee('if (e.which === 27)');
        $response->assertSee("$(this).val('').removeClass('is-valid is-warning is-invalid')");
        
        $this->printTestResult('✓ Funcionalidad de tecla ESC - Configurada');
        $response->assertStatus(200);
    }
    
    public function test_debounce_mechanism()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que el mecanismo de debounce está configurado
        $response->assertSee('clearTimeout($this.data(\'timer\'))');
        $response->assertSee('$this.data(\'timer\', setTimeout(() => {');
        $response->assertSee('500)');
        
        $this->printTestResult('✓ Mecanismo de debounce - Configurado');
        $response->assertStatus(200);
    }
    
    public function test_uppercase_conversion()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que la conversión a mayúsculas está configurada
        $response->assertSee('$this.val(value.toUpperCase().replace(/[^A-Z0-9]/g, \'\'))');
        
        $this->printTestResult('✓ Conversión a mayúsculas - Configurada');
        $response->assertStatus(200);
    }
    
    public function test_tooltip_system()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que el sistema de tooltips está configurado
        $response->assertSee('code-filter-tooltip');
        $response->assertSee('createElement(\'div\')');
        $response->assertSee('setTimeout(() => {');
        $response->assertSee('3000');
        
        $this->printTestResult('✓ Sistema de tooltips - Configurado');
        $response->assertStatus(200);
    }
    
    public function test_validation_classes()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que las clases de validación están configuradas
        $response->assertSee('is-valid');
        $response->assertSee('is-warning');
        $response->assertSee('is-invalid');
        $response->assertSee('removeClass(\'is-valid\', \'is-warning\', \'is-invalid\')');
        
        $this->printTestResult('✓ Clases de validación Bootstrap - Configuradas');
        $response->assertStatus(200);
    }
    
    public function test_error_messages_spanish()
    {
        $response = $this->get('/reportes/cartera-abonos');
        
        // Verificar que los mensajes de error están en español
        $response->assertSee('Formato: 5 caracteres, letras mayúsculas y números (ej: A001)');
        $response->assertSee('Formato: hasta 10 caracteres, letras mayúsculas y números (ej: B001)');
        
        $this->printTestResult('✓ Mensajes de error en español - Correctos');
        $response->assertStatus(200);
    }
    
    protected function printTestResult($message)
    {
        echo $message . "\n";
    }
}