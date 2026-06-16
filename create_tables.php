<?php

/**
 * Script para crear tablas básicas de Laravel manualmente
 * Ejecutar con: php create_tables.php
 */
echo "=== CREANDO TABLAS BÁSICAS DE LARAVEL ===\n\n";

try {
    // Configuración de conexión a PostgreSQL
    $host = '192.168.10.200';
    $port = '5432';
    $database = 'pgdm-Index';
    $username = 'bryan.vazquez';
    $password = '3ha]PMJbqK-YnGC&OjAt';

    // Conectar a PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$database";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✓ Conexión a PostgreSQL exitosa\n";

    // Crear tabla cache
    $sqlCache = '
    CREATE TABLE IF NOT EXISTS cache (
        key VARCHAR(255) PRIMARY KEY,
        value TEXT,
        expiration INTEGER
    );
    ';
    $pdo->exec($sqlCache);
    echo "✓ Tabla 'cache' creada/verificada\n";

    // Crear tabla cache_locks
    $sqlCacheLocks = '
    CREATE TABLE IF NOT EXISTS cache_locks (
        key VARCHAR(255) PRIMARY KEY,
        owner VARCHAR(255),
        expiration INTEGER
    );
    ';
    $pdo->exec($sqlCacheLocks);
    echo "✓ Tabla 'cache_locks' creada/verificada\n";

    // Crear tabla jobs (para colas)
    $sqlJobs = '
    CREATE TABLE IF NOT EXISTS jobs (
        id BIGSERIAL PRIMARY KEY,
        queue VARCHAR(255) NOT NULL,
        payload TEXT NOT NULL,
        attempts SMALLINT NOT NULL DEFAULT 0,
        reserved_at INTEGER,
        available_at INTEGER NOT NULL,
        created_at INTEGER NOT NULL
    );
    ';
    $pdo->exec($sqlJobs);
    echo "✓ Tabla 'jobs' creada/verificada\n";

    // Crear tabla failed_jobs
    $sqlFailedJobs = '
    CREATE TABLE IF NOT EXISTS failed_jobs (
        id BIGSERIAL PRIMARY KEY,
        uuid VARCHAR(255) UNIQUE NOT NULL,
        connection TEXT NOT NULL,
        queue TEXT NOT NULL,
        payload TEXT NOT NULL,
        exception TEXT NOT NULL,
        failed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ';
    $pdo->exec($sqlFailedJobs);
    echo "✓ Tabla 'failed_jobs' creada/verificada\n";

    // Crear tabla users
    $sqlUsers = '
    CREATE TABLE IF NOT EXISTS users (
        id BIGSERIAL PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        email_verified_at TIMESTAMP,
        password VARCHAR(255) NOT NULL,
        remember_token VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    ';
    $pdo->exec($sqlUsers);
    echo "✓ Tabla 'users' creada/verificada\n";

    // Verificar que las tablas existen
    $tables = ['cache', 'cache_locks', 'jobs', 'failed_jobs', 'users'];
    foreach ($tables as $table) {
        $result = $pdo->query("SELECT EXISTS (
            SELECT 1 FROM information_schema.tables
            WHERE table_schema = 'public'
            AND table_name = '$table'
        )");
        $exists = $result->fetch()['exists'];
        echo "✓ Verificación tabla '$table': ".($exists ? 'EXISTE' : 'NO EXISTE')."\n";
    }

    echo "\n🎉 TODAS LAS TABLAS BÁSICAS DE LARAVEL HAN SIDO CREADAS!\n";
    echo "\nAhora puedes probar los reportes sin errores de tabla faltante.\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: ".$e->getMessage()."\n";
    echo "Posibles causas:\n";
    echo "- Sin permisos para crear tablas\n";
    echo "- Conexión a BD fallida\n";
    echo "- Error en la configuración\n";
}
