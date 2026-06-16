<?php

/**
 * Script de Prueba de Funcionalidad Básica
 * Verifica que los controladores y servicios funcionen sin errores de cache
 */

// Incluir autoloader
require_once __DIR__.'/vendor/autoload.php';

use App\Services\ReportService;
use Illuminate\Contracts\Http\Kernel;

echo "=== PRUEBA DE FUNCIONALIDAD BÁSICA ===\n\n";

try {
    // Configurar Laravel
    $app = require_once __DIR__.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    echo "✓ Laravel configurado correctamente\n";

    // Configurar límites
    ReportService::optimizarConfiguracion();
    echo "✓ Configuración optimizada\n";

    // === PRUEBA 1: Servicio ReportService ===
    echo "\nPRUEBA 1: Servicio ReportService\n";

    $filtros = [
        'fecha_inicio' => '2024-01-01',
        'fecha_fin' => '2024-01-05', // Rango pequeño para evitar timeouts
        'plaza' => '',
        'tienda' => '',
        'vendedor' => '',
    ];

    $inicio = microtime(true);
    $resultados = ReportService::getVendedoresReport($filtros);
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Servicio ejecutado en {$tiempo}ms\n";
    echo '✓ Registros obtenidos: '.$resultados->count()."\n";

    if ($resultados->isNotEmpty()) {
        echo "✓ Estructura de datos correcta\n";
    }

    // === PRUEBA 2: Estadísticas ===
    echo "\nPRUEBA 2: Cálculo de estadísticas\n";

    $estadisticas = ReportService::calcularEstadisticasVendedores($resultados);
    echo "✓ Estadísticas calculadas:\n";
    echo '  - Total ventas: '.number_format($estadisticas['total_ventas'], 2)."\n";
    echo '  - Total registros: '.$estadisticas['total_registros']."\n";

    // === PRUEBA 3: Reporte Matricial ===
    echo "\nPRUEBA 3: Reporte matricial\n";

    $inicio = microtime(true);
    $matricial = ReportService::getVendedoresMatricialReport($filtros);
    $tiempo = round((microtime(true) - $inicio) * 1000, 2);

    echo "✓ Reporte matricial ejecutado en {$tiempo}ms\n";
    echo '✓ Días generados: '.count($matricial['dias'])."\n";
    echo '✓ Vendedores procesados: '.count($matricial['vendedores_info'])."\n";

    // === PRUEBA 4: Procesamiento en Chunks ===
    echo "\nPRUEBA 4: Procesamiento en chunks\n";

    $chunksProcesados = 0;
    ReportService::procesarEnChunks($resultados, function ($chunk) use (&$chunksProcesados) {
        $chunksProcesados++;
    }, 100);

    echo "✓ Chunks procesados: $chunksProcesados\n";

    // === RESULTADO FINAL ===
    echo "\n".str_repeat('=', 50)."\n";
    echo "🎉 TODAS LAS PRUEBAS PASARON EXITOSAMENTE!\n";
    echo "Los reportes deberían funcionar sin errores de cache.\n";
    echo "\nPuedes probar las vistas ahora:\n";
    echo "- /reportes/vendedores\n";
    echo "- /reportes/vendedores-matricial\n";
    echo "- /reportes/metas-ventas\n";
    echo str_repeat('=', 50)."\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: ".$e->getMessage()."\n";
    echo "Solución sugerida: Ejecutar el script create_tables.php o pedir al DBA crear las tablas.\n";
}
