# Manual de Instalación - ComexCare

## Requisitos del Sistema

| Software | Versión Mínima | Notas |
|----------|----------------|-------|
| PHP | 8.3+ | Con extensiones requeridas |
| Composer | 2.x | Gestor de dependencias PHP |
| Node.js | 22.x | Para asset compilation |
| PostgreSQL | 14+ | Base de datos principal |
| MySQL | 8.4+ | Alternativo (base de datos secundaria) |
| Redis | 5.0+ | Cache y colas |
| Nginx | 1.27+ | Servidor web |

---

## Instalación desde Cero (Ubuntu Server 22.04+)

### 1. Preparación del Sistema

```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar utilidades básicas
sudo apt install -y curl wget git unzip software-properties-common gnupg2 lsb-release

# Agregar repositorio PHP 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
```

### 2. Instalar PHP y Extensiones

```bash
# PHP 8.3 con todas las extensiones necesarias
sudo apt install -y \
    php8.3 \
    php8.3-cli \
    php8.3-fpm \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-gd \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-redis \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-sqlite3 \
    php8.3-imagick \
    php8.3-bz2
```

### 3. Instalar Composer

```bash
# Descargar e instalar Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Verificar instalación
composer --version
```

### 4. Instalar Node.js 22

```bash
# Agregar repositorio Node.js
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -

# Instalar Node.js
sudo apt install -y nodejs

# Verificar versiones
node --version    # v22.x.x
npm --version     # 10.x.x
```

### 5. Instalar Base de Datos (PostgreSQL)

```bash
# Instalar PostgreSQL
sudo apt install -y postgresql postgresql-contrib
sudo systemctl enable postgresql
sudo systemctl start postgresql

# Configurar PostgreSQL
sudo -u postgres psql
```

```sql
-- En la consola de PostgreSQL:
CREATE USER comexcare WITH PASSWORD '2ct1v3.d1r3ct';
CREATE DATABASE comexcare OWNER comexcare;
ALTER USER comexcare CREATEDB;
\q
```

### 6. Instalar Redis (Opcional)

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 7. Instalar Nginx

```bash
sudo apt install -y nginx
sudo systemctl enable nginx
```

---

## Clonar el Repositorio

```bash
# Ir al directorio web
cd /var/www

# Clonar el repositorio
sudo git clone https://github.com/TU_USUARIO/TU_REPO.git comexcare

# Entrar al proyecto
cd /var/www/comexcare
```

### Configurar Permisos

```bash
# Dar permisos al usuario actual y www-data
sudo chown -R $USER:www-data /var/www/comexcare
sudo chmod -R 755 /var/www/comexcare
sudo chmod -R 777 /var/www/comexcare/storage
sudo chmod -R 777 /var/www/comexcare/bootstrap/cache
```

---

## Configuración del Proyecto

### 1. Copiar archivo de configuración

```bash
cp .env.example .env
```

### 2. Editar archivo .env

```bash
nano .env
```

**Configuración mínima requerida:**

```env
# Aplicación
APP_NAME=ComexCare
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_KEY=

# Base de datos (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=comexcare
DB_USERNAME=comexcare
DB_PASSWORD=TU_PASSWORD_AQUI

# Sesión y Cola
SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Cache
CACHE_STORE=file

# Redis (opcional)
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

**Para producción:**

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=comexcare
DB_USERNAME=comexcare
DB_PASSWORD=password_seguro

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379

LOG_CHANNEL=daily
LOG_LEVEL=warning
```

### 3. Generar clave de aplicación

```bash
php artisan key:generate
```

---

## Instalación de Dependencias

### Dependencias PHP

```bash
# Instalar dependencias Composer
composer install --optimize-autoloader --no-dev

# Si hay errores de memoria
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev
```

### Dependencias Node.js

```bash
# Instalar dependencias npm
npm install

# Compilar assets para producción
npm run build
```

---

## Migraciones y Base de Datos

### Ejecutar Migraciones

```bash
# Ejecutar todas las migraciones
php artisan migrate --force

# Si hay errores, verificar la conexión
php artisan tinker
DB::connection()->getPdo();
```

### Datos Semilla (Opcional)

```bash
# Crear usuario administrador
php artisan make:seeder UserSeeder

# O crear usuario manualmente
php artisan tinker
User::create(['name' => 'Admin', 'email' => 'admin@comexcare.com', 'password' => Hash::make('password')]);
```

---

## Configuración de Nginx

### Crear archivo de configuración

```bash
sudo nano /etc/nginx/sites-available/comexcare
```

**Contenido:**

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/comexcare/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/comexcare_access.log;
    error_log /var/log/nginx/comexcare_error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_index index.php;
    }

    # Proteger archivos sensibles
    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ /\.ht {
        deny all;
    }

    # Cache de archivos estáticos
    location ~* \.(jpg|jpeg|gif|png|webp|svg|woff|woff2|ttf|eot|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**Para dominio con SSL:**

```nginx
server {
    listen 80;
    server_name tu-dominio.com www.tu-dominio.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name tu-dominio.com www.tu-dominio.com;
    root /var/www/comexcare/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/tu-dominio.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/tu-dominio.com/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Habilitar sitio

```bash
# Crear enlace simbólico
sudo ln -s /etc/nginx/sites-available/comexcare /etc/nginx/sites-enabled/

# Probar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl reload nginx
```

---

## Optimización para Producción

### Limpiar caché

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan clear-compiled
```

### Generar caché optimizado

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## Configuración de Colas (Supervisor)

### Instalar Supervisor

```bash
sudo apt install -y supervisor
```

### Crear configuración

```bash
sudo nano /etc/supervisor/conf.d/comexcare-worker.conf
```

**Contenido:**

```ini
[program:comexcare-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/comexcare/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/comexcare-worker.log
stopwaitsecs=3600
```

### Iniciar Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start comexcare-worker:*
```

---

## Configuración de SSL (Let's Encrypt)

```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtener certificado (después de configurar DNS)
sudo certbot --nginx -d tu-dominio.com -d www.tu-dominio.com

# Verificar renovación automática
sudo certbot renew --dry-run
```

---

## Verificación de Instalación

### Probar endpoints

```bash
# Verificar que la aplicación responde
curl -I http://localhost

# Probar conexión a base de datos
php artisan tinker
DB::connection()->getPdo();
exit
```

### Verificar servicios

```bash
# Estado de servicios
systemctl status php8.3-fpm
systemctl status postgresql
systemctl status redis-server
systemctl status nginx
systemctl status supervisor
```

### Verificar colas

```bash
# Ver procesos de cola
sudo supervisorctl status

# Probar cola manualmente
php artisan queue:work --once
```

---

## Comandos de Mantenimiento

```bash
# Regenerar claves
php artisan key:generate

# Regenerar caché
php artisan optimize:clear
php artisan config:cache

# Ver logs en tiempo real
tail -f storage/logs/laravel.log

# Ver errores Nginx
tail -f /var/log/nginx/comexcare_error.log

# Ver logs de colas
tail -f /var/log/comexcare-worker.log

# Reiniciar colas
sudo supervisorctl restart comexcare-worker:*
```

---

## Estructura de Directorios Importantes

```
/var/www/comexcare/
├── app/
│   ├── Console/Commands/     # Comandos Artisan
│   ├── Http/Controllers/    # Controladores
│   ├── Models/              # Modelos Eloquent
│   └── Services/            # Servicios
├── config/                  # Archivos de configuración
├── database/
│   ├── migrations/          # Migraciones de BD
│   └── seeders/             # Seeders
├── public/                  # Archivos públicos
├── resources/
│   └── views/               # Vistas Blade
├── routes/                  # Rutas
├── storage/                 # Logs, cache, uploads
└── vendor/                  # Dependencias Composer
```

---

## Notas Importantes

1. **Base de datos externa**: El proyecto usa la tabla `bi_sys_tiendas` que viene de una base de datos del sistema original (configurar conexión adicional si es necesaria).

2. **Colas Redis**: Para producción, usar Redis como driver de colas.

3. **WebSockets**: El proyecto soporta WebSockets para notificaciones en tiempo real (requiere Soketi o Pusher).

4. **Permisos**: El usuario www-data debe tener acceso de lectura/escritura a storage y bootstrap/cache.

5. **Variables de entorno**: Nunca commitear el archivo .env con contraseñas reales.

---

## Solución de Problemas

### Error 500 Internal Server Error

```bash
# Ver logs detallados
tail -f /var/log/nginx/comexcare_error.log
tail -f /var/www/comexcare/storage/logs/laravel.log

# Verificar permisos
sudo chown -R www-data:www-data /var/www/comexcare
```

### Error de conexión a base de datos

```bash
# Verificar credenciales
php artisan tinker
DB::connection()->getPdo();

# Probar conexión directa
psql -U comexcare -d comexcare -h 127.0.0.1
```

### Problemas con Composer

```bash
# Limpiar caché de Composer
composer clear-cache

# Reinstalar dependencias
rm -rf vendor/
composer install
```

### Problemas con Node.js

```bash
# Limpiar caché npm
npm cache clean --force

# Reinstala dependencias
rm -rf node_modules/
npm install
```