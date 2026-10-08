<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Blindaje: la suite de pruebas únicamente puede correr contra SQLite
        // en memoria. Si se detecta cualquier otra conexión (por ejemplo, un
        // bootstrap/cache/config.php que haya fijado DB_CONNECTION=pgsql) se
        // aborta TODO antes de tocar la base real.
        if (config('database.default') !== 'sqlite') {
            throw new RuntimeException(
                'La suite de pruebas exige DB_CONNECTION=sqlite hacia :memory:. '.
                'Conexion detectada: '.config('database.default').'. '.
                'Revisa phpunit.xml y que no exista bootstrap/cache/config.php cacheado.'
            );
        }
    }
}
