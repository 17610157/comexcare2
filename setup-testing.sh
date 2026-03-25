#!/bin/bash
# Script para configurar entorno de testing con PostgreSQL local

set -e

echo "=== ComexCare2 - Setup Testing Environment ==="

# 1. Verificar que Docker está instalado
if ! command -v docker &> /dev/null; then
    echo "❌ Docker no está instalado"
    echo "Por favor instala Docker primero: https://docs.docker.com/get-docker/"
    exit 1
fi

if ! command -v docker compose &> /dev/null && ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose no está instalado"
    echo "Por favor instala Docker Compose primero"
    exit 1
fi

# 2. Detener contenedor existente si hay uno
echo "🔄 Deteniendo contenedor existente (si existe)..."
docker compose -f docker-compose.test.yml down 2>/dev/null || \
docker-compose -f docker-compose.test.yml down 2>/dev/null || true

# 3. Iniciar PostgreSQL de testing
echo "🚀 Iniciando PostgreSQL de testing..."
docker compose -f docker-compose.test.yml up -d || docker-compose -f docker-compose.test.yml up -d

# 4. Esperar a que PostgreSQL esté listo
echo "⏳ Esperando a que PostgreSQL esté listo..."
sleep 5

# 5. Verificar conexión
echo "🔍 Verificando conexión..."
for i in {1..10}; do
    if PGPASSWORD=test123 psql -h localhost -p 5433 -U test -d comexcare2-testing -c "SELECT 1" &>/dev/null; then
        echo "✅ PostgreSQL está listo!"
        break
    fi
    echo "   Esperando... ($i/10)"
    sleep 2
done

# 6. Crear tablas externas necesarias (estructura básica)
echo "📦 Creando tablas externas de prueba..."

PGPASSWORD=test123 psql -h localhost -p 5433 -U test -d comexcare2-testing << 'EOF'
-- Tabla cobranza (estructura básica para tests)
CREATE TABLE IF NOT EXISTS cobranza (
    id SERIAL PRIMARY KEY,
    plaza VARCHAR(10),
    tienda VARCHAR(10),
    fecha DATE,
    fecha_vta DATE,
    concepto VARCHAR(100),
    tipo VARCHAR(10),
    factura VARCHAR(50),
    clave VARCHAR(50),
    rfc VARCHAR(20),
    nombre VARCHAR(255),
    monto_fa DECIMAL(15,2),
    monto_dv DECIMAL(15,2),
    monto_cd DECIMAL(15,2),
    dias_cred INTEGER,
    dias_vencidos INTEGER,
    cargo_ab VARCHAR(5),
    vendedor VARCHAR(100)
);

-- Tabla zona
CREATE TABLE IF NOT EXISTS zona (
    id SERIAL PRIMARY KEY,
    clave VARCHAR(20),
    nombre VARCHAR(100)
);

-- Tabla cliente_depurado
CREATE TABLE IF NOT EXISTS cliente_depurado (
    id SERIAL PRIMARY KEY,
    rfc VARCHAR(20),
    nombre VARCHAR(255)
);

-- Tabla metas (estructura básica)
CREATE TABLE IF NOT EXISTS metas (
    id SERIAL PRIMARY KEY,
    plaza VARCHAR(20),
    tienda VARCHAR(20),
    fecha DATE,
    meta_total DECIMAL(15,2),
    dias_total INTEGER,
    valor_dia DECIMAL(15,2),
    meta_dia DECIMAL(15,2)
);

-- Tabla vendedores
CREATE TABLE IF NOT EXISTS vendedores (
    id SERIAL PRIMARY KEY,
    clave VARCHAR(20),
    nombre VARCHAR(255),
    plaza VARCHAR(20),
    tienda VARCHAR(20)
);

-- Insertar datos de prueba
INSERT INTO cobranza (plaza, tienda, fecha, fecha_vta, concepto, tipo, factura, clave, rfc, nombre, monto_fa, monto_dv, monto_cd, dias_cred, dias_vencidos, cargo_ab, vendedor)
SELECT 
    LPAD((random()*10)::int::text, 2, '0'),
    'T' || LPAD((random()*100)::int::text, 3, '0'),
    CURRENT_DATE - (random()*30)::int,
    CURRENT_DATE - (random()*60)::int,
    'Venta de prueba',
    'FAC',
    'F' || (random()*10000)::int,
    'CLV' || (random()*1000)::int,
    'XAXX010101000',
    'Cliente Test ' || (random()*100)::int,
    (random()*10000)::decimal(15,2),
    (random()*1000)::decimal(15,2),
    (random()*500)::decimal(15,2),
    (random()*30)::int,
    (random()*60)::int,
    CASE WHEN random() > 0.5 THEN 'C' ELSE 'A' END,
    'Vendedor ' || (random()*10)::int
FROM generate_series(1, 100);

INSERT INTO zona (clave, nombre) VALUES
    ('NORTE', 'Zona Norte'),
    ('SUR', 'Zona Sur'),
    ('ESTE', 'Zona Este'),
    ('OESTE', 'Zona Oeste');

INSERT INTO cliente_depurado (rfc, nombre) VALUES
    ('XAXX010101000', 'Cliente Depurado 1'),
    ('XEXX010101000', 'Cliente Depurado 2');

INSERT INTO metas (plaza, tienda, fecha, meta_total, dias_total, valor_dia, meta_dia)
SELECT 
    LPAD((random()*10)::int::text, 2, '0'),
    'T' || LPAD((random()*100)::int::text, 3, '0'),
    CURRENT_DATE - (random()*30)::int,
    (random()*100000)::decimal(15,2),
    30,
    (random()*3333)::decimal(15,2),
    (random()*3500)::decimal(15,2)
FROM generate_series(1, 50);

INSERT INTO vendedores (clave, nombre, plaza, tienda) VALUES
    ('V001', 'Vendedor 1', '01', 'T001'),
    ('V002', 'Vendedor 2', '01', 'T002'),
    ('V003', 'Vendedor 3', '02', 'T001');

EOF

echo "✅ Base de datos de testing lista!"
echo ""
echo "=== Configuración completada ==="
echo ""
echo "Para ejecutar tests:"
echo "  cp .env.testing.pgsql .env.testing"
echo "  php artisan test"
echo ""
echo "O ejecuta los tests directamente con:"
echo "  DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=5433 DB_DATABASE=comexcare2-testing DB_USERNAME=test DB_PASSWORD=test123 php artisan test"
