<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optimización específica para el módulo de metas
     * Solo se ejecutará si las tablas existen
     */
    public function up(): void
    {
        $connection = Schema::getConnection()->getDriverName();

        $tables = [
            'metas' => [
                ['name' => 'idx_metas_fecha_plaza_tienda', 'cols' => ['fecha', 'plaza', 'tienda']],
                ['name' => 'idx_metas_vendedor_periodo', 'cols' => ['clave_vend', 'ano', 'mes']],
                ['name' => 'idx_metas_plaza_periodo', 'cols' => ['clave_plaza', 'ano', 'mes']],
                ['name' => 'idx_metas_tienda_periodo', 'cols' => ['cve_tienda', 'ano', 'mes']],
                ['name' => 'idx_metas_periodo_simple', 'cols' => ['ano', 'mes']],
                ['name' => 'idx_metas_calculo_porcentaje', 'cols' => ['meta_dia', 'meta_total', 'fecha']],
                ['name' => 'idx_metas_orden_principal', 'cols' => ['plaza', 'tienda', 'fecha']],
            ],
            'bi_sys_tiendas' => [
                ['name' => 'idx_tiendas_plaza_tienda', 'cols' => ['id_plaza', 'clave_tienda']],
                ['name' => 'idx_tiendas_plaza_zona', 'cols' => ['id_plaza', 'zona']],
                ['name' => 'idx_tiendas_clave_alterna', 'cols' => ['clave_alterna']],
                ['name' => 'idx_tiendas_tienda_zona', 'cols' => ['clave_tienda', 'zona']],
                ['name' => 'idx_tiendas_completo', 'cols' => ['id_plaza', 'clave_tienda', 'zona', 'nombre']],
            ],
            'xcorte' => [
                ['name' => 'idx_xcorte_fecha_plaza_tienda', 'cols' => ['fecha', 'cplaza', 'ctienda']],
                ['name' => 'idx_xcorte_plaza_tienda_fecha', 'cols' => ['cplaza', 'ctienda', 'fecha']],
                ['name' => 'idx_xcorte_tienda_fecha', 'cols' => ['ctienda', 'fecha']],
                ['name' => 'idx_xcorte_exclusion_tiendas', 'cols' => ['ctienda']],
                ['name' => 'idx_xcorte_vendedor_fecha', 'cols' => ['vend_clave', 'fecha']],
                ['name' => 'idx_xcorte_vendedor_nota_fecha', 'cols' => ['vend_clave', 'nota_fecha']],
            ],
            'venta' => [
                ['name' => 'idx_venta_fecha_vendedor', 'cols' => ['f_emision', 'clave_vend']],
                ['name' => 'idx_venta_vendedor_fecha', 'cols' => ['clave_vend', 'f_emision']],
                ['name' => 'idx_venta_plaza_vendedor_fecha', 'cols' => ['cplaza', 'clave_vend', 'f_emision']],
                ['name' => 'idx_venta_tienda_vendedor_fecha', 'cols' => ['ctienda', 'clave_vend', 'f_emision']],
                ['name' => 'idx_venta_tipo_doc', 'cols' => ['tipo_doc']],
                ['name' => 'idx_venta_fecha_emision', 'cols' => ['f_emision']],
                ['name' => 'idx_venta_vendedor_tipo_estado', 'cols' => ['clave_vend', 'tipo_doc', 'estado']],
            ],
            'cotizacion' => [
                ['name' => 'idx_cotizacion_vendedor_fecha', 'cols' => ['vend_clave', 'nota_fecha']],
                ['name' => 'idx_cotizacion_plaza_vendedor_fecha', 'cols' => ['cplaza', 'vend_clave', 'nota_fecha']],
                ['name' => 'idx_cotizacion_tienda_vendedor_fecha', 'cols' => ['ctienda', 'vend_clave', 'nota_fecha']],
                ['name' => 'idx_cotizacion_vendedor_plaza_tienda', 'cols' => ['vend_clave', 'cplaza', 'ctienda']],
                ['name' => 'idx_cotizacion_nota_fecha', 'cols' => ['nota_fecha']],
                ['name' => 'idx_cotizacion_importe', 'cols' => ['nota_impor']],
            ],
            'vendedor' => [
                ['name' => 'idx_vendedor_clave', 'cols' => ['vend_clave']],
                ['name' => 'idx_vendedor_plaza_estatus', 'cols' => ['cve_plaza', 'estatus']],
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
                            continue 2;
                        }
                        Schema::table($tableName, function (Blueprint $table) use ($index) {
                            $table->index($index['cols'], $index['name']);
                        });
                    }
                } catch (Exception $e) {
                    // Silenciar errores
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
