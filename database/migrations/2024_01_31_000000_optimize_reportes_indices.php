<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optimización de índices para reportes - Acelera consultas pesadas
     * Solo se ejecutará si las tablas existen
     */
    public function up(): void
    {
        $connection = Schema::getConnection()->getDriverName();

        // Tablas externas que pueden no existir en testing
        $tables = [
            'cobranza' => [
                ['name' => 'idx_cobranza_filtro_principal', 'cols' => ['cargo_ab', 'estado', 'cborrado']],
                ['name' => 'idx_cobranza_fecha_tipo', 'cols' => ['fecha', 'cargo_ab']],
                ['name' => 'idx_cobranza_plaza_tienda_clave', 'cols' => ['cplaza', 'ctienda', 'clave_cl']],
                ['name' => 'idx_cobranza_tipo_ref', 'cols' => ['tipo_ref', 'no_ref']],
                ['name' => 'idx_cobranza_plaza_fecha', 'cols' => ['cplaza', 'fecha']],
                ['name' => 'idx_cobranza_tienda_fecha', 'cols' => ['ctienda', 'fecha']],
            ],
            'cliente_depurado' => [
                ['name' => 'idx_cliente_depurado_completo', 'cols' => ['ctienda', 'cplaza', 'clie_clave']],
                ['name' => 'idx_cliente_nombre', 'cols' => ['clie_nombr']],
                ['name' => 'idx_cliente_rfc', 'cols' => ['clie_rfc']],
            ],
            'zona' => [
                ['name' => 'idx_zona_plaza_tienda', 'cols' => ['plaza', 'tienda']],
            ],
            'cotizacion' => [
                ['name' => 'idx_cotizacion_vendedor_fecha', 'cols' => ['vend_clave', 'nota_fecha']],
                ['name' => 'idx_cotizacion_tienda_vendedor', 'cols' => ['ctienda', 'vend_clave']],
                ['name' => 'idx_cotizacion_plaza_fecha', 'cols' => ['cplaza', 'nota_fecha']],
                ['name' => 'idx_cotizacion_tienda_fecha', 'cols' => ['ctienda', 'nota_fecha']],
                ['name' => 'idx_cotizacion_fecha_importe', 'cols' => ['nota_fecha', 'nota_impor']],
            ],
            'venta' => [
                ['name' => 'idx_venta_fecha_vendedor_tipo', 'cols' => ['f_emision', 'clave_vend', 'tipo_doc']],
                ['name' => 'idx_venta_vendedor_plaza_tienda', 'cols' => ['clave_vend', 'cplaza', 'ctienda']],
                ['name' => 'idx_venta_fecha_emision', 'cols' => ['f_emision']],
                ['name' => 'idx_venta_vendedor', 'cols' => ['clave_vend']],
            ],
            'vendedor' => [
                ['name' => 'idx_vendedor_clave', 'cols' => ['vend_clave']],
                ['name' => 'idx_vendedor_plaza_estatus', 'cols' => ['cve_plaza', 'estatus']],
            ],
            'metas' => [
                ['name' => 'idx_metas_vendedor_periodo', 'cols' => ['clave_vend', 'ano', 'mes']],
                ['name' => 'idx_metas_plaza_periodo', 'cols' => ['clave_plaza', 'ano', 'mes']],
                ['name' => 'idx_metas_tienda_periodo', 'cols' => ['cve_tienda', 'ano', 'mes']],
                ['name' => 'idx_metas_periodo', 'cols' => ['ano', 'mes']],
            ],
            'compra' => [
                ['name' => 'idx_compra_plaza_tienda_fecha', 'cols' => ['cve_plaza', 'cve_tienda', 'fec_compra']],
                ['name' => 'idx_compra_fecha', 'cols' => ['fec_compra']],
                ['name' => 'idx_compra_proveedor', 'cols' => ['cve_prov']],
                ['name' => 'idx_compra_factura', 'cols' => ['num_fact']],
            ],
        ];

        foreach ($tables as $tableName => $indexes) {
            if (! $this->tableExists($tableName)) {
                continue;
            }

            foreach ($indexes as $index) {
                try {
                    if ($connection === 'pgsql') {
                        if (! $this->indexExists($tableName, $index['name'])) {
                            $cols = implode(', ', $index['cols']);
                            DB::statement("CREATE INDEX {$index['name']} ON {$tableName} ({$cols})");
                        }
                    } else {
                        if (! Schema::hasTable($tableName)) {
                            continue;
                        }
                        Schema::table($tableName, function (Blueprint $table) use ($index) {
                            $table->index($index['cols'], $index['name']);
                        });
                    }
                } catch (Exception $e) {
                    // Silenciar errores - la tabla puede no soportar ciertos índices
                }
            }
        }
    }

    public function down(): void
    {
        // Los índices se eliminan automáticamente con las tablas
    }

    protected function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Exception $e) {
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
            }
        } catch (Exception $e) {
            return true;
        }

        return false;
    }
};
