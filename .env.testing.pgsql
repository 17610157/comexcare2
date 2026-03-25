# Configuración para testing con PostgreSQL local (Docker)
# Usar con: cp .env.testing.pgsql .env.testing.local && php artisan test

APP_NAME=Laravel
APP_ENV=testing
APP_KEY=base64:7fmLYorBlZ64/TQAeWqLoMm0QveNcPrijvwug4XuJoQ=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=comexcare2-testing
DB_USERNAME=test
DB_PASSWORD=test123

SESSION_DRIVER=array
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
