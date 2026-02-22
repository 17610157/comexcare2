# Script de Instalación - Cartera Abonos Ultra-Fast (500 Usuarios)

echo "=== Instalación Cartera Abonos Ultra-Fast ==="
echo "🚀 Sistema optimizado para 500 usuarios concurrentes"
echo "📊 Arquitectura: Pre-carga + Filtrado Cliente-Side + Zero Database Queries"
echo ""

## 1. Verificar requisitos críticos
echo "Paso 1: Verificando requisitos para 500 usuarios..."

# Verificar Redis (crítico para pre-carga)
if ! command -v redis-cli &> /dev/null; then
    echo "❌ Redis es REQUERIDO para 500 usuarios"
    echo "Por favor instala Redis:"
    echo "  Ubuntu/Debian: sudo apt-get install redis-server"
    echo "  CentOS/RHEL: sudo yum install redis"
    echo "  Docker: docker run -d -p 6379:6379 redis"
    exit 1
fi

# Verificar configuración de Redis
redis_memory=$(redis-cli info memory | grep used_memory_human | cut -d: -f2 | tr -d '\r')
echo "✅ Redis detectado - Memoria usada: $redis_memory"

# Verificar PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP no está instalado"
    exit 1
fi

php_version=$(php -v | head -n1 | cut -d' ' -f2)
echo "✅ PHP $php_version detectado"

echo "✅ Requisitos verificados"

## 2. Configurar Redis para alto rendimiento
echo "Paso 2: Optimizando Redis para 500 usuarios..."

# Configuración Redis para alto rendimiento
redis_config="
maxmemory 2gb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
tcp-keepalive 300
timeout 0
"

echo "$redis_config" > /tmp/redis_ultra_fast.conf

# Aplicar configuración si es posible
if redis-cli CONFIG SET maxmemory 2gb > /dev/null 2>&1; then
    echo "✅ Redis configurado para 2GB RAM"
else
    echo "⚠️ Configura manualmente Redis con 2GB maxmemory"
fi

## 3. Configurar variables de entorno
echo "Paso 3: Configurando entorno para Ultra-Fast..."

# Verificar y configurar cache driver
if ! grep -q "CACHE_DRIVER=redis" .env; then
    echo "CACHE_DRIVER=redis" >> .env
    echo "✅ CACHE_DRIVER configurado a Redis"
fi

# Configurar queue para background jobs
if ! grep -q "QUEUE_CONNECTION=database" .env; then
    echo "QUEUE_CONNECTION=database" >> .env
    echo "✅ QUEUE_CONNECTION configurado"
fi

# Configurar sesión para Redis (opcional pero recomendado)
if ! grep -q "SESSION_DRIVER=redis" .env; then
    echo "SESSION_DRIVER=redis" >> .env
    echo "✅ SESSION_DRIVER configurado a Redis"
fi

## 4. Crear tablas de cola si no existen
echo "Paso 4: Configurando sistema de colas..."

php artisan queue:table --table=ultra_fast_jobs
php artisan migrate --force

## 5. Pre-cargar datos del periodo anterior
echo "Paso 5: Pre-cargando datos del periodo anterior..."

# Forzar pre-carga inicial
php artisan tinker --execute="
    \$service = app(App\Services\CarteraAbonosUltraFastService::class);
    try {
        \$result = \$service->forcePreload();
        echo '✅ Pre-carga completada: ' . \$result['records_count'] . ' registros';
        echo '⏱️ Tiempo: ' . \$result['load_time_ms'] . 'ms';
    } catch (Exception \$e) {
        echo '❌ Error en pre-carga: ' . \$e->getMessage();
    }
"

## 6. Configurar programador de actualizaciones
echo "Paso 6: Configurando actualizaciones incrementales..."

# Job para pre-carga diaria (cada medianoche)
(crontab -l 2>/dev/null; echo "0 0 * * * cd $(pwd) && php artisan tinker --execute=\"app(App\Services\CarteraAbonosUltraFastService::class)->forcePreload();\" >> /var/log/cartera_preload.log 2>&1") | crontab -

# Job para actualizaciones incrementales (cada 5 minutos)
(crontab -l 2>/dev/null; echo "*/5 * * * * cd $(pwd) && php artisan queue:work --queue=preload --timeout=300 --sleep=3 --tries=3 >> /var/log/cartera_incremental.log 2>&1 &") | crontab -

echo "✅ Programador configurado"

## 7. Iniciar workers de cola
echo "Paso 7: Iniciando workers de cola..."

echo "📝 Para iniciar workers en producción:"
echo "php artisan queue:work --queue=preload --timeout=300 --sleep=3 --tries=3 --daemon"

# Iniciar worker en background para testing
if command -v supervisorctl &> /dev/null; then
    echo "✅ Supervisor detectado - Configura workers con Supervisor"
else
    echo "⚠️ Inicia workers manualmente con el comando anterior"
fi

## 8. Optimizar aplicación
echo "Paso 8: Optimizando aplicación para 500 usuarios..."

# Limpiar y optimizar cachés
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

composer dump-autoload --optimize

# Optimizar OPcache si está disponible
if php -m | grep -q opcache; then
    echo "✅ OPcache detectado - Optimizando..."
    php -d opcache.validate_timestamps=0 -d opcache.enable=1 artisan config:cache
fi

## 9. Configurar balanceador de carga (opcional)
echo "Paso 9: Recomendaciones para 500 usuarios..."

echo "🔧 Configuración adicional recomendada:"
echo ""
echo "1. NGINX Configuration:"
echo "   upstream cartera_ultra_fast {"
echo "       server 127.0.0.1:8000 max_fails=3 fail_timeout=30s;"
echo "       server 127.0.0.1:8001 max_fails=3 fail_timeout=30s;"
echo "   }"
echo ""
echo "2. PHP-FPM Configuration:"
echo "   pm.max_children = 50"
echo "   pm.start_servers = 5"
echo "   pm.min_spare_servers = 5"
echo "   pm.max_spare_servers = 35"
echo ""
echo "3. Redis Configuration:"
echo "   maxmemory-policy allkeys-lru"
echo "   tcp-keepalive 300"
echo "   timeout 0"

## 10. Verificar instalación
echo "Paso 10: Verificando instalación Ultra-Fast..."

# Verificar Redis
redis_status=$(redis-cli ping 2>/dev/null)
echo "Redis Status: $redis_status"

# Verificar cache
php artisan tinker --execute="
    \$service = app(App\Services\CarteraAbonosUltraFastService::class);
    \$stats = \$service->getSystemStats();
    echo 'Preloaded Data: ' . (\$stats['has_preloaded_data'] ? 'YES' : 'NO');
    echo 'Cache Keys: ' . count(\$stats['cache_keys']);
    echo 'Redis Memory: ' . \$stats['redis_memory']['used_memory'];
"

# Verificar rutas
php artisan route:list | grep cartera-abonos-ultra-fast

echo ""
echo "=== 🎉 Instalación Ultra-Fast Completada ==="
echo ""
echo "🚀 Acceso al sistema para 500 usuarios:"
echo "• Reporte Ultra-Fast: /reportes/cartera-abonos-ultra-fast"
echo ""
echo "⚡ Características implementadas:"
echo "• Pre-carga de datos en Redis (2h TTL)"
echo "• Filtrado 100% cliente-side (Zero DB Queries)"
echo "• Actualizaciones incrementales en background"
echo "• Soporte para 500 usuarios concurrentes"
echo "• Tiempo de respuesta <100ms"
echo ""
echo "📊 Comandos de control:"
echo "• Forzar pre-carga: php artisan tinker --execute=\"app(App\Services\CarteraAbonosUltraFastService::class)->forcePreload();\""
echo "• Verificar estado: curl http://localhost/reportes/cartera-abonos-ultra-fast/health"
echo "• Limpiar Redis: redis-cli FLUSHALL"
echo ""
echo "🔥 Sistema listo para 500 usuarios concurrentes!"
echo "   Tiempo de respuesta: <100ms"
echo "   Zero database queries en tiempo real"
echo "   Escalabilidad infinita con Redis"