<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optimización específica para el módulo de metas
     */
    public function up(): void
    {
        // Optimización para tabla metas (reporte principal)
        Schema::table('metas', function (Blueprint $table) {
            // Índices para filtros principales de metas
            $table->index(['fecha', 'plaza', 'tienda'], 'idx_metas_fecha_plaza_tienda');
            $table->index(['clave_vend', 'ano', 'mes'], 'idx_metas_vendedor_periodo');
            $table->index(['clave_plaza', 'ano', 'mes'], 'idx_metas_plaza_periodo');
            $table->index(['cve_tienda', 'ano', 'mes'], 'idx_metas_tienda_periodo');
            $table->index(['ano', 'mes'], 'idx_metas_periodo_simple');
            
            // Índice para el cálculo de porcentajes
            $table->index(['meta_dia', 'meta_total', 'fecha'], 'idx_metas_calculo_porcentaje');
            
            // Índice para ordenamiento en vistas
            $table->index(['plaza', 'tienda', 'fecha'], 'idx_metas_orden_principal');
        });

        // Optimización para bi_sys_tiendas (usada en metas matricial)
        Schema::table('bi_sys_tiendas', function (Blueprint $table) {
            // Índices para joins flexibles con TRIM()
            $table->index(['id_plaza', 'clave_tienda'], 'idx_tiendas_plaza_tienda');
            $table->index(['id_plaza', 'zona'], 'idx_tiendas_plaza_zona');
            $table->index(['clave_alterna'], 'idx_tiendas_clave_alterna');
            $table->index(['clave_tienda', 'zona'], 'idx_tiendas_tienda_zona');
            
            // Índice compuesto para búsquedas flexibles
            $table->index(['id_plaza', 'clave_tienda', 'zona', 'nombre'], 'idx_tiendas_completo');
        });

        // Optimización para xcorte (usado en metas matricial)
        Schema::table('xcorte', function (Blueprint $table) {
            // Índices para rendimiento de reportes matriciales
            $table->index(['fecha', 'cplaza', 'ctienda'], 'idx_xcorte_fecha_plaza_tienda');
            $table->index(['cplaza', 'ctienda', 'fecha'], 'idx_xcorte_plaza_tienda_fecha');
            $table->index(['ctienda', 'fecha'], 'idx_xcorte_tienda_fecha');
            
            // Índices para exclusiones de tiendas
            $table->index(['ctienda'], 'idx_xcorte_exclusion_tiendas');
            
            // Índice para cálculos de totales
            $table->index(['vend_clave', 'fecha'], 'idx_xcorte_vendedor_fecha');
            $table->index(['vend_clave', 'nota_fecha'], 'idx_xcorte_vendedor_nota_fecha');
        });

        // Optimización para venta (usada en cálculos de metas)
        Schema::table('venta', function (Blueprint $table) {
            // Índices para sumas rápidas de ventas
            $table->index(['f_emision', 'clave_vend'], 'idx_venta_fecha_vendedor');
            $table->index(['clave_vend', 'f_emision'], 'idx_venta_vendedor_fecha');
            $table->index(['cplaza', 'clave_vend', 'f_emision'], 'idx_venta_plaza_vendedor_fecha');
            $table->index(['ctienda', 'clave_vend', 'f_emision'], 'idx_venta_tienda_vendedor_fecha');
            $table->index(['tipo_doc'], 'idx_venta_tipo_doc');
            $table->index(['f_emision'], 'idx_venta_fecha_emision');
            
            // Índices para cálculos de contado vs crédito
            $table->index(['clave_vend', 'tipo_doc', 'estado'], 'idx_venta_vendedor_tipo_estado');
        });

        // Optimización para cotizacion (usada en reportes de vendedores)
        Schema::table('cotizacion', function (Blueprint $table) {
            // Índices para reportes matriciales de vendedores
            $table->index(['vend_clave', 'nota_fecha'], 'idx_cotizacion_vendedor_fecha');
            $table->index(['cplaza', 'vend_clave', 'nota_fecha'], 'idx_cotizacion_plaza_vendedor_fecha');
            $table->index(['ctienda', 'vend_clave', 'nota_fecha'], 'idx_cotizacion_tienda_vendedor_fecha');
            $table->index(['vend_clave', 'cplaza', 'ctienda'], 'idx_cotizacion_vendedor_plaza_tienda');
            $table->index(['nota_fecha'], 'idx_cotizacion_nota_fecha');
            $table->index(['nota_impor'], 'idx_cotizacion_importe');
        });

        // Optimización para vendedor
        Schema::table('vendedor', function (Blueprint $table) {
            $table->index(['vend_clave'], 'idx_vendedor_clave');
            $table->index(['cve_plaza', 'estatus'], 'idx_vendedor_plaza_estatus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices de metas
        Schema::table('metas', function (Blueprint $table) {
            $table->dropIndex('idx_metas_fecha_plaza_tienda');
            $table->dropIndex('idx_metas_vendedor_periodo');
            $table->dropIndex('idx_metas_plaza_periodo');
            $table->dropIndex('idx_metas_tienda_periodo');
            $table->dropIndex('idx_metas_periodo_simple');
            $table->dropIndex('idx_metas_calculo_porcentaje');
            $table->dropIndex('idx_metas_orden_principal');
        });

        // Eliminar índices de bi_sys_tiendas
        Schema::table('bi_sys_tiendas', function (Blueprint $table) {
            $table->dropIndex('idx_tiendas_plaza_tienda');
            $table->dropIndex('idx_tiendas_plaza_zona');
            $table->dropIndex('idx_tiendas_clave_alterna');
            $table->dropIndex('idx_tiendas_tienda_zona');
            $table->dropIndex('idx_tiendas_completo');
        });

        // Eliminar índices de xcorte
        Schema::table('xcorte', function (Blueprint $table) {
            $table->dropIndex('idx_xcorte_fecha_plaza_tienda');
            $table->dropIndex('idx_xcorte_plaza_tienda_fecha');
            $table->dropIndex('idx_xcorte_tienda_fecha');
            $table->dropIndex('idx_xcorte_exclusion_tiendas');
            $table->dropIndex('idx_xcorte_vendedor_fecha');
            $table->dropIndex('idx_xcorte_vendedor_nota_fecha');
        });

        // Eliminar índices de venta
        Schema::table('venta', function (Blueprint $table) {
            $table->dropIndex('idx_venta_fecha_vendedor');
            $table->dropIndex('idx_venta_vendedor_fecha');
            $table->dropIndex('idx_venta_plaza_vendedor_fecha');
            $table->dropIndex('idx_venta_tienda_vendedor_fecha');
            $table->dropIndex('idx_venta_tipo_doc');
            $table->dropIndex('idx_venta_fecha_emision');
            $table->dropIndex('idx_venta_vendedor_tipo_estado');
        });

        // Eliminar índices de cotizacion
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->dropIndex('idx_cotizacion_vendedor_fecha');
            $table->dropIndex('idx_cotizacion_plaza_vendedor_fecha');
            $table->dropIndex('idx_cotizacion_tienda_vendedor_fecha');
            $table->dropIndex('idx_cotizacion_vendedor_plaza_tienda');
            $table->dropIndex('idx_cotizacion_nota_fecha');
            $table->dropIndex('idx_cotizacion_importe');
        });

        // Eliminar índices de vendedor
        Schema::table('vendedor', function (Blueprint $table) {
            $table->dropIndex('idx_vendedor_clave');
            $table->dropIndex('idx_vendedor_plaza_estatus');
        });
    }
};