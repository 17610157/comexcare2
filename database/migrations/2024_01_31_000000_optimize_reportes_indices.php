<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optimización de índices para reportes - Acelera consultas pesadas
     */
    public function up(): void
    {
        // Índices para Cartera Abonos (reporte más pesado)
        Schema::table('cobranza', function (Blueprint $table) {
            // Índices compuestos para los filtros más usados
            $table->index(['cargo_ab', 'estado', 'cborrado'], 'idx_cobranza_filtro_principal');
            $table->index(['fecha', 'cargo_ab'], 'idx_cobranza_fecha_tipo');
            $table->index(['cplaza', 'ctienda', 'clave_cl'], 'idx_cobranza_plaza_tienda_clave');
            $table->index(['tipo_ref', 'no_ref'], 'idx_cobranza_tipo_ref');
            $table->index(['cplaza', 'fecha'], 'idx_cobranza_plaza_fecha');
            $table->index(['ctienda', 'fecha'], 'idx_cobranza_tienda_fecha');
        });

        Schema::table('cliente_depurado', function (Blueprint $table) {
            // Índices para búsquedas de clientes
            $table->index(['ctienda', 'cplaza', 'clie_clave'], 'idx_cliente_depurado_completo');
            $table->index(['clie_nombr'], 'idx_cliente_nombre');
            $table->index(['clie_rfc'], 'idx_cliente_rfc');
        });

        Schema::table('zona', function (Blueprint $table) {
            // Índice para joins de zona
            $table->index(['plaza', 'tienda'], 'idx_zona_plaza_tienda');
        });

        // Índices para Vendedores
        Schema::table('cotizacion', function (Blueprint $table) {
            // Índices compuestos para reportes de vendedores
            $table->index(['vend_clave', 'nota_fecha'], 'idx_cotizacion_vendedor_fecha');
            $table->index(['ctienda', 'vend_clave'], 'idx_cotizacion_tienda_vendedor');
            $table->index(['cplaza', 'nota_fecha'], 'idx_cotizacion_plaza_fecha');
            $table->index(['ctienda', 'nota_fecha'], 'idx_cotizacion_tienda_fecha');
            $table->index(['nota_fecha', 'nota_impor'], 'idx_cotizacion_fecha_importe');
        });

        Schema::table('venta', function (Blueprint $table) {
            // Índices para subqueries de vendedores
            $table->index(['f_emision', 'clave_vend', 'tipo_doc'], 'idx_venta_fecha_vendedor_tipo');
            $table->index(['clave_vend', 'cplaza', 'ctienda'], 'idx_venta_vendedor_plaza_tienda');
            $table->index(['f_emision'], 'idx_venta_fecha_emision');
            $table->index(['clave_vend'], 'idx_venta_vendedor');
        });

        Schema::table('vendedor', function (Blueprint $table) {
            // Índices para lookup de vendedores
            $table->index(['vend_clave'], 'idx_vendedor_clave');
            $table->index(['cve_plaza', 'estatus'], 'idx_vendedor_plaza_estatus');
        });

        // Índices para Metas
        Schema::table('metas', function (Blueprint $table) {
            // Índices compuestos para metas
            $table->index(['clave_vend', 'ano', 'mes'], 'idx_metas_vendedor_periodo');
            $table->index(['clave_plaza', 'ano', 'mes'], 'idx_metas_plaza_periodo');
            $table->index(['cve_tienda', 'ano', 'mes'], 'idx_metas_tienda_periodo');
            $table->index(['ano', 'mes'], 'idx_metas_periodo');
        });

        // Índices para Compras
        Schema::table('compra', function (Blueprint $table) {
            // Índices para reportes de compras
            $table->index(['cve_plaza', 'cve_tienda', 'fec_compra'], 'idx_compra_plaza_tienda_fecha');
            $table->index(['fec_compra'], 'idx_compra_fecha');
            $table->index(['cve_prov'], 'idx_compra_proveedor');
            $table->index(['num_fact'], 'idx_compra_factura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar índices de Cartera Abonos
        Schema::table('cobranza', function (Blueprint $table) {
            $table->dropIndex('idx_cobranza_filtro_principal');
            $table->dropIndex('idx_cobranza_fecha_tipo');
            $table->dropIndex('idx_cobranza_plaza_tienda_clave');
            $table->dropIndex('idx_cobranza_tipo_ref');
            $table->dropIndex('idx_cobranza_plaza_fecha');
            $table->dropIndex('idx_cobranza_tienda_fecha');
        });

        Schema::table('cliente_depurado', function (Blueprint $table) {
            $table->dropIndex('idx_cliente_depurado_completo');
            $table->dropIndex('idx_cliente_nombre');
            $table->dropIndex('idx_cliente_rfc');
        });

        Schema::table('zona', function (Blueprint $table) {
            $table->dropIndex('idx_zona_plaza_tienda');
        });

        // Eliminar índices de Vendedores
        Schema::table('cotizacion', function (Blueprint $table) {
            $table->dropIndex('idx_cotizacion_vendedor_fecha');
            $table->dropIndex('idx_cotizacion_tienda_vendedor');
            $table->dropIndex('idx_cotizacion_plaza_fecha');
            $table->dropIndex('idx_cotizacion_tienda_fecha');
            $table->dropIndex('idx_cotizacion_fecha_importe');
        });

        Schema::table('venta', function (Blueprint $table) {
            $table->dropIndex('idx_venta_fecha_vendedor_tipo');
            $table->dropIndex('idx_venta_vendedor_plaza_tienda');
            $table->dropIndex('idx_venta_fecha_emision');
            $table->dropIndex('idx_venta_vendedor');
        });

        Schema::table('vendedor', function (Blueprint $table) {
            $table->dropIndex('idx_vendedor_clave');
            $table->dropIndex('idx_vendedor_plaza_estatus');
        });

        // Eliminar índices de Metas
        Schema::table('metas', function (Blueprint $table) {
            $table->dropIndex('idx_metas_vendedor_periodo');
            $table->dropIndex('idx_metas_plaza_periodo');
            $table->dropIndex('idx_metas_tienda_periodo');
            $table->dropIndex('idx_metas_periodo');
        });

        // Eliminar índices de Compras
        Schema::table('compra', function (Blueprint $table) {
            $table->dropIndex('idx_compra_plaza_tienda_fecha');
            $table->dropIndex('idx_compra_fecha');
            $table->dropIndex('idx_compra_proveedor');
            $table->dropIndex('idx_compra_factura');
        });
    }
};