# Instalación del Proyecto ComexCare en Servidor Linux

## Versiones Compatibles del Entorno de Desarrollo (Referencia)

Estas son las versiones del entorno de desarrollo local (Laragon Windows):
- **PHP:** 8.3.30
- **Node.js:** 22.x
- **MySQL:** 8.4.3
- **Nginx:** 1.27.3
- **Redis:** 5.0.14.1
- **Memcached:** 1.6.8

## Requisitos Previos para Servidor Linux

- Servidor con **Ubuntu 20.04+** o **Debian 11+**
- **PHP 8.3+** (con extensiones requeridas)
- **Composer** (gestor de dependencias PHP)
- **Node.js 22+** y **npm**
- **MySQL 8.4+** o **PostgreSQL 14+**
- **Redis 5.0+** (para caché)
- **Nginx 1.27+** (servidor web)
- **Git**

---

## Paso 1: Actualizar Sistema e Instalar Dependencias

```bash
# Actualizar paquetes
sudo apt update && sudo apt upgrade -y

# Instalar dependencias básicas
sudo apt install -y curl wget git unzip software-properties-common gnupg2

# Instalar PHP 8.3 y extensiones
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-redis php8.3-mysql php8.3-sqlite3 php8.3-curl php8.3-imagick php8.3-bz2

# Instalar Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Instalar Node.js 22
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs

# Versión Node.js y npm
node --version    # v22.x.x
npm --version     # 10.x.x

# Instalar MySQL 8.4 (compatible con el proyecto)
sudo apt install -y mysql-server
sudo systemctl enable mysql
sudo systemctl start mysql

# Instalar Redis 5.0
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

---

## Paso 2: Configurar Base de Datos (MySQL 8.4)

```bash
# Acceder a MySQL
sudo mysql -u root

# Crear usuario y base de datos
CREATE USER 'comexcare'@'localhost' IDENTIFIED BY 'TU_PASSWORD_AQUI';
CREATE DATABASE comexcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON comexcare.* TO 'comexcare'@'localhost';
FLUSH PRIVILEGES;

# Salir
EXIT;
```

---

## Paso 3: Clonar Proyecto desde GitHub

```bash
# Ir al directorio web
cd /var/www

# Clonar repositorio (reemplazar URL)
sudo git clone https://github.com/TU_USUARIO/TU_REPO.git comexcare

# Ir al directorio del proyecto
cd /var/www/comexcare

# Cambiar permisos
sudo chown -R www-data:www-data /var/www/comexcare
sudo chmod -R 755 /var/www/comexcare
sudo chmod -R 777 /var/www/comexcare/storage
sudo chmod -R 777 /var/www/comexcare/bootstrap/cache
```

---

## Paso 4: Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Editar archivo .env
nano .env
```

**Contenido mínimo del `.env`:**

```env
APP_NAME=ComexCare
APP_ENV=production
APP_DEBUG=false
APP_URL=http://tu-dominio.com

LOG_CHANNEL=daily

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=comexcare
DB_USERNAME=comexcare
DB_PASSWORD=TU_PASSWORD_AQUI

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## Paso 5: Instalar Dependencias PHP

```bash
cd /var/www/comexcare

# Instalar dependencias
composer install --optimize-autoloader --no-dev

# Si hay errores de memoria
COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev
```

---

## Paso 6: Configurar Permisos y Cache

```bash
# Permisos correctos
sudo chown -R www-data:www-data /var/www/comexcare
sudo chmod -R 755 /var/www/comexcare
sudo chmod -R 777 /var/www/comexcare/storage /var/www/comexcare/bootstrap/cache

# Limpiar caché
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan clear-compiled

# Regenerar caché optimizado
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## Paso 7: Migraciones y Datos Semilla

```bash
# Ejecutar migraciones
php artisan migrate --force

# Opcional: ejecutar seeders si existen
php artisan db:seed --force
```

---

## Paso 8: Configurar Nginx

```bash
# Crear archivo de configuración
sudo nano /etc/nginx/sites-available/comexcare
```

**Contenido del archivo Nginx:**

```nginx
server {
    listen 80;
    server_name tu-dominio.com www.tu-dominio.com;
    root /var/www/comexcare/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(jpg|jpeg|gif|png|webp|svg|woff|woff2|ttf|eot|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Logs
    access_log /var/log/nginx/comexcare_access.log;
    error_log /var/log/nginx/comexcare_error.log;
}
```

```bash
# Habilitar sitio
sudo ln -s /etc/nginx/sites-available/comexcare /etc/nginx/sites-enabled/

# Probar configuración
sudo nginx -t

# Reiniciar Nginx
sudo systemctl reload nginx
```

---

## Paso 9: Configurar Supervisor (Colas)

```bash
# Instalar Supervisor
sudo apt install -y supervisor

# Crear configuración
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

```bash
# Recargar Supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start comexcare-worker:*
```

---

## Paso 10: Configurar SSL (Let's Encrypt)

```bash
# Instalar Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d tu-dominio.com -d www.tu-dominio.com

# Reiniciar Nginx
sudo systemctl reload nginx
```

---

## Comandos Útiles para Mantenimiento

```bash
# Ver estado de colas
sudo supervisorctl status

# Ver logs de errores
tail -f /var/log/nginx/comexcare_error.log
tail -f /var/log/comexcare-worker.log
tail -f /var/www/comexcare/storage/logs/laravel.log

# Regenerar claves de aplicación
php artisan key:generate

# Limpiar toda la caché
php artisan optimize:clear

# Sincronizar permisos
sudo chown -R www-data:www-data /var/www/comexcare
```

---

## Solución de Problemas Comunes

### Error 500 Internal Server Error
```bash
# Ver logs
tail -f /var/log/nginx/comexcare_error.log
tail -f /var/www/comexcare/storage/logs/laravel.log
```

### Error de Permisos
```bash
sudo chown -R www-data:www-data /var/www/comexcare
sudo chmod -R 755 /var/www/comexcare
sudo chmod -R 777 /var/www/comexcare/storage /var/www/comexcare/bootstrap/cache
```

### Error de Conexión a Base de Datos
```bash
# Verificar conexión
php artisan tinker
DB::connection()->getPdo();
```

---

## URLs de Acceso

- **Aplicación:** http://tu-dominio.com
- **Colas:** Configuradas en Supervisor

---

**Nota:** Reemplaza `TU_PASSWORD_AQUI`, `tu-dominio.com`, y las URLs de GitHub con los valores correctos de tu proyecto.
