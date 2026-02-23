# Instalación Mínima - Servidor Local ComexCare

## Requisitos del Entorno

| Software | Versión Mínima |
|----------|----------------|
| PHP | 8.3+ |
| Composer | 2.x |
| Node.js | 22.x |
| PostgreSQL | 14+ |
| Redis | 5.0+ |
| Nginx | 1.27+ |

---

## Instalación en Ubuntu Server (Local)

### 1. PHP 8.3 y Extensiones

```bash
sudo apt update
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-pgsql php8.3-sqlite3 php8.3-redis
```

### 2. Composer

```bash
curl -sS https://getcomposer.org/installer | php8.3
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

### 3. Node.js 22

```bash
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt install -y nodejs
```

### 4. PostgreSQL 14+

```bash
sudo apt install -y postgresql postgresql-contrib
sudo systemctl enable postgresql
sudo systemctl start postgresql
```

### 5. Redis

```bash
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server
```

### 6. Nginx

```bash
sudo apt install -y nginx
```

---

## Configuración Rápida

### PostgreSQL - Crear Base de Datos

```bash
sudo -u postgres psql
```

```sql
CREATE USER comexcare WITH PASSWORD 'password123';
CREATE DATABASE comexcare OWNER comexcare;
ALTER USER comexcare CREATEDB;
\q
```

### Clonar y Configurar Proyecto

```bash
cd /var/www
sudo git clone https://github.com/tu-repo/comexcare.git
cd comexcare

sudo chown -R $USER:www-data .
chmod -R 755 .
chmod -R 777 storage bootstrap/cache

cp .env.example .env
```

### Editar .env

```env
APP_NAME=ComexCare
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=comexcare
DB_USERNAME=comexcare
DB_PASSWORD=password123

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### Instalar Dependencias

```bash
composer install --no-interaction
npm install
npm run build

php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

### Configurar Nginx

```bash
sudo nano /etc/nginx/sites-available/comexcare
```

```nginx
server {
    listen 80;
    server_name localhost;
    root /var/www/comexcare/public;
    index index.php;

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

```bash
sudo ln -s /etc/nginx/sites-available/comexcare /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## Verificar Funcionamiento

```bash
# Probar conexión a base de datos
php artisan tinker
DB::connection()->getPdo();

# Ver servicios activos
systemctl status php8.3-fpm
systemctl status postgresql
systemctl status redis-server
systemctl status nginx
```

---

## Notas

- **Puerto PostgreSQL:** 5432
- **Puerto Redis:** 6379
- **Puerto Nginx:** 80
- **Ruta Proyecto:** `/var/www/comexcare`
