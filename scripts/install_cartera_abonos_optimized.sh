# Script de Instalación y Optimización para Cartera Abonos

echo "=== Instalación de Cartera Abonos Optimizado - Tiempo Real ==="
echo ""

## 1. Ejecutar índices de base de datos optimizados
echo "Paso 1: Creando índices de base de datos optimizados..."
mysql -u [usuario] -p [base_de_datos] < sql/reportes/cartera_abonos/performance_indexes.sql

if [ $? -eq 0 ]; then
    echo "✓ Índices creados exitosamente"
else
    echo "✗ Error al crear índices"
    exit 1
fi

## 2. Limpiar caché existente
echo "Paso 2: Limpiando caché existente..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

## 3. Publicar assets
echo "Paso 3: Publicando assets optimizados..."
php artisan vendor:publish --tag=assets --force

## 4. Optimizar autoloader
echo "Paso 4: Optimizando autoloader..."
composer dump-autoload --optimize

## 5. Configurar variables de entorno (si es necesario)
echo "Paso 5: Verificando configuración..."
if ! grep -q "CACHE_DRIVER=redis" .env; then
    echo "⚠ Recomendación: Configura CACHE_DRIVER=redis para mejor rendimiento"
fi

if ! grep -q "QUEUE_CONNECTION=database" .env; then
    echo "⚠ Recomendación: Configura QUEUE_CONNECTION=database para background jobs"
fi

## 6. Ejecutar migraciones (si hay nuevas)
echo "Paso 6: Verificando migraciones..."
php artisan migrate:status

## 7. Verificar instalación
echo "Paso 7: Verificando instalación..."
php artisan route:list | grep cartera-abonos-optimized

if [ $? -eq 0 ]; then
    echo "✓ Rutas optimizadas configuradas"
else
    echo "✗ Error: Rutas no encontradas. Verifica el archivo de rutas."
fi

## 8. Test de performance
echo "Paso 8: Ejecutando test de performance..."
php artisan tinker --execute="
    \$start = microtime(true);
    \$service = app(App\Services\CarteraAbonosCacheService::class);
    \$data = \$service->getCachedData(['start' => '2024-01-01', 'end' => '2024-01-31']);
    \$end = microtime(true);
    echo 'Tiempo de respuesta: ' . round((\$end - \$start) * 1000, 2) . 'ms';
    echo 'Registros: ' . count(\$data['data'] ?? []);
"

echo ""
echo "=== Instalación Completada ==="
echo "Accede al reporte optimizado en: /reportes/cartera-abonos-optimized"
echo ""
echo "Próximos pasos:"
echo "1. Prueba el reporte con diferentes filtros"
echo "2. Verifica el rendimiento en tiempo real"
echo "3. Configura el auto-refresh si es necesario"
echo "4. Monitorea las estadísticas de caché"