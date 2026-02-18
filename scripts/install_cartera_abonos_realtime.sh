# Script de Instalación - Cartera Abonos Tiempo Real con Tablas Materializadas

echo "=== Instalación Cartera Abonos - Tiempo Real ==="
echo "🚀 Sistema con Tablas Materializadas y Sincronización en Background"
echo ""

## 1. Verificar requisitos
echo "Paso 1: Verificando requisitos..."

# Verificar PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP no está instalado"
    exit 1
fi

# Verificar MySQL/PostgreSQL
if ! command -v mysql &> /dev/null && ! command -v psql &> /dev/null; then
    echo "❌ MySQL o PostgreSQL no está instalado"
    exit 1
fi

# Verificar Redis (recomendado para caché)
if ! command -v redis-cli &> /dev/null; then
    echo "⚠️ Redis no está instalado (recomendado para mejor rendimiento)"
fi

echo "✅ Requisitos verificados"

## 2. Crear estructura de base de datos
echo "Paso 2: Creando tablas materializadas..."

# Ejecutar script de tablas materializadas
if command -v mysql &> /dev/null; then
    mysql -u [usuario] -p [base_de_datos] < sql/reportes/cartera_abonos/materialized_table.sql
elif command -v psql &> /dev/null; then
    psql -U [usuario] -d [base_de_datos] -f sql/reportes/cartera_abonos/materialized_table.sql
fi

if [ $? -eq 0 ]; then
    echo "✅ Tablas materializadas creadas exitosamente"
else
    echo "❌ Error al crear tablas materializadas"
    exit 1
fi

## 3. Sincronización inicial
echo "Paso 3: Ejecutando sincronización inicial..."

php artisan cartera-abonos:sync --type=full --force

if [ $? -eq 0 ]; then
    echo "✅ Sincronización inicial completada"
else
    echo "❌ Error en sincronización inicial"
    exit 1
fi

## 4. Configurar cola de procesamiento
echo "Paso 4: Configurando cola de sincronización..."

# Verificar configuración de queue
if ! grep -q "QUEUE_CONNECTION=database" .env; then
    echo "⚠️ Configurando QUEUE_CONNECTION=database"
    echo "QUEUE_CONNECTION=database" >> .env
fi

# Ejecutar migraciones de cola
php artisan queue:table
php artisan migrate

# Iniciar worker de cola (en producción)
echo "📝 Para iniciar worker en producción:"
echo "php artisan queue:work --queue=sync --sleep=3 --tries=3"

## 5. Configurar programador de sincronización
echo "Paso 5: Configurando programador de sincronización..."

# Agregar al crontab del usuario
(crontab -l 2>/dev/null; echo "*/5 * * * * cd $(pwd) && php artisan cartera-abonos:sync --type=incremental >> /var/log/cartera_sync.log 2>&1") | crontab -

echo "✅ Programador configurado (sincronización cada 5 minutos)"

## 6. Limpiar cachés
echo "Paso 6: Limpiando y optimizando cachés..."

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache

composer dump-autoload --optimize

echo "✅ Cachés limpiadas y optimizadas"

## 7. Verificar instalación
echo "Paso 7: Verificando instalación..."

# Health check
php artisan cartera-abonos:sync --monitor

# Verificar rutas
php artisan route:list | grep cartera-abonos-realtime

# Verificar tablas
php artisan tinker --execute="
    echo 'Registros en tabla materializada: ' . DB::table('cartera_abonos_materialized')->count();
    echo 'Última sincronización: ' . DB::table('cartera_abonos_sync_control')->where('status', 'completed')->orderBy('completed_at', 'desc')->first()->completed_at ?? 'Nunca';
"

echo "✅ Verificación completada"

## 8. Configurar variables de entorno (opcional)
echo "Paso 8: Configuración adicional recomendada..."

if ! grep -q "CACHE_DRIVER=redis" .env; then
    echo "🔧 Añadir a .env para mejor rendimiento:"
    echo "CACHE_DRIVER=redis"
    echo "REDIS_HOST=127.0.0.1"
    echo "REDIS_PASSWORD=null"
    echo "REDIS_PORT=6379"
fi

echo ""
echo "=== 🎉 Instalación Completada Exitosamente ==="
echo ""
echo "📊 Accesos disponibles:"
echo "• Reporte Original: /reportes/cartera-abonos"
echo "• Reporte Optimizado: /reportes/cartera-abonos-optimized"
echo "• Reporte Tiempo Real: /reportes/cartera-abonos-realtime ⭐"
echo ""
echo "🔧 Comandos útiles:"
echo "• Forzar sincronización: php artisan cartera-abonos:sync --force"
echo "• Monitorear estado: php artisan cartera-abonos:sync --monitor"
echo "• Health check: curl http://localhost/reportes/cartera-abonos-realtime/health"
echo ""
echo "⚡ Características de Tiempo Real:"
echo "• Tabla materializada con datos pre-procesados"
echo "• Sincronización incremental cada 5 minutos"
echo "• Streaming Server-Sent Events para actualizaciones vivas"
echo "• Health check automático y monitoreo"
echo "• Fallback automático a tabla original"
echo ""
echo "🚀 Disfruta del reporte en tiempo real!"