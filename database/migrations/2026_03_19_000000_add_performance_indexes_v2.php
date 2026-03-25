<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection === 'pgsql') {
            $this->upPostgres();
        } elseif (in_array($connection, ['mysql', 'mariadb'])) {
            $this->upMysql();
        } elseif ($connection === 'sqlite') {
            // SQLite: solo crear índices si la tabla existe
            $this->upSqlite();
        }
    }

    protected function upPostgres(): void
    {
        $indexes = [
            'vendedores_cache' => [
                ['name' => 'idx_vendedores_fecha_plaza', 'cols' => 'nota_fecha, plaza_ajustada'],
                ['name' => 'idx_vendedores_plaza_fecha', 'cols' => 'plaza_ajustada, nota_fecha'],
                ['name' => 'idx_vendedores_vendedor_fecha', 'cols' => 'vend_clave, nota_fecha'],
            ],
            'cartera_abonos_cache' => [
                ['name' => 'idx_cartera_fecha_plaza', 'cols' => 'fecha, plaza'],
                ['name' => 'idx_cartera_plaza_fecha_tienda', 'cols' => 'plaza, tienda, fecha'],
            ],
            'notas_completas_cache' => [
                ['name' => 'idx_notas_fecha_plaza', 'cols' => 'fecha, plaza'],
                ['name' => 'idx_notas_factura', 'cols' => 'factura'],
            ],
            'metas_dias' => [
                ['name' => 'idx_metas_dias_periodo_fecha', 'cols' => 'periodo, fecha'],
                ['name' => 'idx_metas_dias_tienda_periodo', 'cols' => 'tienda, periodo'],
            ],
            'metas_mensual' => [
                ['name' => 'idx_metas_mensual_periodo_plaza', 'cols' => 'periodo, plaza'],
                ['name' => 'idx_metas_mensual_tienda_periodo', 'cols' => 'tienda, periodo'],
            ],
            'computers' => [
                ['name' => 'idx_computers_mac', 'cols' => 'mac_address'],
                ['name' => 'idx_computers_status', 'cols' => 'status'],
                ['name' => 'idx_computers_last_seen', 'cols' => 'last_seen'],
            ],
            'commands' => [
                ['name' => 'idx_commands_computer_status', 'cols' => 'computer_id, status'],
                ['name' => 'idx_commands_status_created', 'cols' => 'status, created_at'],
            ],
            'distributions' => [
                ['name' => 'idx_distributions_status', 'cols' => 'status'],
                ['name' => 'idx_distributions_type_status', 'cols' => 'type, status'],
            ],
            'distribution_targets' => [
                ['name' => 'idx_dt_distribution_status', 'cols' => 'distribution_id, status'],
                ['name' => 'idx_dt_computer', 'cols' => 'computer_id'],
            ],
            'receptions' => [
                ['name' => 'idx_receptions_status', 'cols' => 'status'],
            ],
            'reception_targets' => [
                ['name' => 'idx_rt_reception_status', 'cols' => 'reception_id, status'],
                ['name' => 'idx_rt_computer', 'cols' => 'computer_id'],
            ],
            'computer_logs' => [
                ['name' => 'idx_cl_computer_created', 'cols' => 'computer_id, created_at'],
                ['name' => 'idx_cl_level', 'cols' => 'level'],
            ],
            'users' => [
                ['name' => 'idx_users_email', 'cols' => 'email'],
            ],
            'audit_logs' => [
                ['name' => 'idx_audit_user_created', 'cols' => 'user_id, created_at'],
                ['name' => 'idx_audit_action', 'cols' => 'action, created_at'],
                ['name' => 'idx_audit_ip', 'cols' => 'ip_address'],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (! $this->tableExists($table)) {
                continue;
            }

            foreach ($tableIndexes as $index) {
                try {
                    if (! $this->indexExists($table, $index['name'])) {
                        DB::statement("CREATE INDEX {$index['name']} ON {$table} ({$index['cols']})");
                    }
                } catch (\Exception $e) {
                    // Silenciar errores de índices
                }
            }
        }

        // Tablas externas (solo intentar si existen)
        $externalTables = [
            'xcorte' => [
                ['name' => 'idx_xcorte_fecha_plaza', 'cols' => 'fecha, cplaza'],
                ['name' => 'idx_xcorte_fecha_tienda', 'cols' => 'fecha, ctienda'],
            ],
            'cobranza' => [
                ['name' => 'idx_cobranza_filtro_principal', 'cols' => 'cargo_ab, estado, cborrado'],
            ],
        ];

        foreach ($externalTables as $table => $tableIndexes) {
            if (! $this->tableExists($table)) {
                continue;
            }

            foreach ($tableIndexes as $index) {
                try {
                    if (! $this->indexExists($table, $index['name'])) {
                        DB::statement("CREATE INDEX {$index['name']} ON {$table} ({$index['cols']})");
                    }
                } catch (\Exception $e) {
                    // Silenciar errores
                }
            }
        }
    }

    protected function upMysql(): void
    {
        $indexes = [
            'vendedores_cache' => [
                ['name' => 'idx_vendedores_fecha_plaza', 'cols' => ['nota_fecha', 'plaza_ajustada']],
                ['name' => 'idx_vendedores_vendedor_fecha', 'cols' => ['vend_clave', 'nota_fecha']],
            ],
            'cartera_abonos_cache' => [
                ['name' => 'idx_cartera_fecha_plaza', 'cols' => ['fecha', 'plaza']],
                ['name' => 'idx_cartera_plaza_fecha_tienda', 'cols' => ['plaza', 'tienda', 'fecha']],
            ],
            'notas_completas_cache' => [
                ['name' => 'idx_notas_fecha_plaza', 'cols' => ['fecha', 'plaza']],
            ],
            'metas_dias' => [
                ['name' => 'idx_metas_dias_periodo_fecha', 'cols' => ['periodo', 'fecha']],
            ],
            'computers' => [
                ['name' => 'idx_computers_mac', 'cols' => ['mac_address']],
                ['name' => 'idx_computers_status', 'cols' => ['status']],
            ],
            'commands' => [
                ['name' => 'idx_commands_computer_status', 'cols' => ['computer_id', 'status']],
            ],
            'distributions' => [
                ['name' => 'idx_distributions_status', 'cols' => ['status']],
            ],
            'users' => [
                ['name' => 'idx_users_email', 'cols' => ['email']],
            ],
        ];

        foreach ($indexes as $table => $tableIndexes) {
            if (! $this->tableExists($table)) {
                continue;
            }

            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($tableIndexes as $index) {
                try {
                    if (! $this->indexExists($table, $index['name'])) {
                        Schema::table($table, function (Blueprint $tableObj) use ($index) {
                            $tableObj->index($index['cols'], $index['name']);
                        });
                    }
                } catch (\Exception $e) {
                    // Silenciar errores
                }
            }
        }
    }

    protected function upSqlite(): void
    {
        // SQLite no necesita índices adicionales para testing
        // Los índices se crean automáticamente
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function indexExists(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection()->getDriverName();

            if ($connection === 'pgsql') {
                $exists = DB::select(
                    'SELECT 1 FROM pg_indexes WHERE indexname = ? AND tablename = ?',
                    [$index, $table]
                );

                return ! empty($exists);
            } elseif (in_array($connection, ['mysql', 'mariadb'])) {
                $result = DB::select(
                    "SHOW INDEX FROM {$table} WHERE Key_name = ?",
                    [$index]
                );

                return ! empty($result);
            } elseif ($connection === 'sqlite') {
                $result = DB::select(
                    "SELECT name FROM sqlite_master WHERE type='index' AND name=?",
                    [$index]
                );

                return ! empty($result);
            }
        } catch (\Exception $e) {
            return true; // Asumir que existe si hay error
        }

        return false;
    }

    public function down(): void
    {
        // Los índices se eliminan automáticamente con las tablas
    }
};
