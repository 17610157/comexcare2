<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\DB;

trait RequiresExternalTables
{
    protected array $requiredTables = [];

    protected function setUpRequiresExternalTables(): void
    {
        if (! empty($this->requiredTables)) {
            $this->requiresTables($this->requiredTables);
        }
    }

    protected function requiresTables(array $tables): void
    {
        $connection = DB::connection()->getDriverName();

        if ($connection !== 'pgsql') {
            foreach ($tables as $table) {
                if (! $this->tableExists($table)) {
                    $this->markTestSkipped("Test requires external table '{$table}' which does not exist in SQLite. Run tests with PostgreSQL: DB_CONNECTION=pgsql php artisan test");
                }
            }
        }
    }

    protected function tableExists(string $table): bool
    {
        try {
            return count(DB::select("SELECT 1 FROM sqlite_master WHERE type='table' AND name = ?", [$table])) > 0
                || count(DB::select('SELECT 1 FROM pg_tables WHERE tablename = ?', [$table])) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function requiresCobranza(): void
    {
        $this->requiresTables(['cobranza']);
    }

    protected function requiresMetas(): void
    {
        $this->requiresTables(['metas']);
    }

    protected function requiresVendedores(): void
    {
        $this->requiresTables(['vendedores']);
    }

    protected function requiresCarteraAbonos(): void
    {
        $this->requiresTables(['cobranza', 'zona', 'cliente_depurado']);
    }
}
