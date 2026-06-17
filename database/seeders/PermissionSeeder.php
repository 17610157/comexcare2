<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Admin
            'admin.ver',
            'admin.usuarios.ver',
            'admin.usuarios.crear',
            'admin.usuarios.editar',
            'admin.usuarios.eliminar',
            'admin.usuarios.sincronizar',
            'admin.roles.ver',
            'admin.roles.crear',
            'admin.roles.editar',
            'admin.roles.eliminar',
            'admin.permissions.ver',
            'admin.permissions.crear',
            'admin.permissions.editar',
            'admin.permissions.eliminar',
            'admin.configuracion.ver',
            'admin.configuracion.editar',

            // Usuarios
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',

            // Roles
            'roles.ver',
            'roles.crear',
            'roles.editar',
            'roles.eliminar',

            // Tiendas
            'tiendas.ver',
            'tiendas.crear',
            'tiendas.editar',
            'tiendas.eliminar',

            // Plazas
            'plazas.ver',
            'plazas.crear',
            'plazas.editar',
            'plazas.eliminar',

            // Distribution
            'distribution.ver',
            'distribution.crear',
            'distribution.editar',
            'distribution.eliminar',

            // Distributions (alternativo)
            'distributions.ver',
            'distributions.crear',
            'distributions.editar',
            'distributions.eliminar',
            'distributions.importar',
            'distributions.exportar',

            // Computers
            'computers.ver',
            'computers.crear',
            'computers.editar',
            'computers.eliminar',

            // Groups
            'groups.ver',
            'groups.crear',
            'groups.editar',
            'groups.eliminar',

            // Agent Versions
            'agentversions.ver',
            'agentversions.crear',
            'agentversions.editar',
            'agentversions.eliminar',

            // Metas
            'metas.ver',
            'metas.crear',
            'metas.editar',
            'metas.eliminar',
            'metas.importar',
            'metas.exportar',

            // User Plaza Tienda
            'user-plaza-tienda.ver',
            'user-plaza-tienda.editar',

            // Reportes General
            'reportes.ver',
            'reportes.vendedores.ver',
            'reportes.vendedores.crear',
            'reportes.vendedores.editar',
            'reportes.vendedores.eliminar',
            'reportes.vendedores.sincronizar',
            'reportes.vendedores.exportar',
            'reportes.vendedores.filtrar',

            // Reportes Vendedores Matricial
            'reportes.vendedores_matricial.ver',
            'reportes.vendedores_matricial.crear',
            'reportes.vendedores_matricial.editar',
            'reportes.vendedores_matricial.eliminar',
            'reportes.vendedores_matricial.exportar',
            'reportes.vendedores_matricial.filtrar',

            // Reportes Metas Ventas
            'reportes.metas_ventas.ver',
            'reportes.metas_ventas.crear',
            'reportes.metas_ventas.editar',
            'reportes.metas_ventas.eliminar',
            'reportes.metas_ventas.exportar',
            'reportes.metas_ventas.filtrar',

            // Reportes Metas Ventas (alternativo)
            'reportes.metas-ventas.ver',
            'reportes.metas-ventas.editar',
            'reportes.metas-ventas.exportar',
            'reportes.metas-ventas.filtrar',

            // Reportes Metas Matricial
            'reportes.metas_matricial.ver',
            'reportes.metas_matricial.crear',
            'reportes.metas_matricial.editar',
            'reportes.metas_matricial.eliminar',
            'reportes.metas_matricial.exportar',
            'reportes.metas_matricial.filtrar',

            // Reportes Metas Matricial (alternativo)
            'reportes.metas-matricial.ver',
            'reportes.metas-matricial.editar',

            // Reportes Cartera Abonos
            'reportes.cartera_abonos.ver',
            'reportes.cartera_abonos.crear',
            'reportes.cartera_abonos.editar',
            'reportes.cartera_abonos.eliminar',
            'reportes.cartera_abonos.exportar',
            'reportes.cartera_abonos.filtrar',
            'reportes.cartera_abonos.sincronizar',

            // Reportes Cartera Abonos (alternativo)
            'reportes.cartera-abonos.ver',
            'reportes.cartera-abonos.editar',
            'reportes.cartera-abonos.sincronizar',

            // Reportes Notas Completas
            'reportes.notas_completas.ver',
            'reportes.notas_completas.crear',
            'reportes.notas_completas.editar',
            'reportes.notas_completas.eliminar',
            'reportes.notas_completas.exportar',
            'reportes.notas_completas.filtrar',
            'reportes.notas_completas.sincronizar',

            // Reportes Notas Completas (alternativo)
            'reportes.notas-completas.ver',
            'reportes.notas-completas.editar',
            'reportes.notas-completas.sincronizar',

            // Reportes Compras Directo
            'reportes.compras_directo.ver',
            'reportes.compras_directo.crear',
            'reportes.compras_directo.editar',
            'reportes.compras_directo.eliminar',
            'reportes.compras_directo.exportar',
            'reportes.compras_directo.filtrar',
            'reportes.compras_directo.sincronizar',

            // Reportes Compras Directo (alternativo)
            'reportes.compras-directo.ver',
            'reportes.compras-directo.editar',
            'reportes.compras-directo.sincronizar',

            // Reportes Redenciones Club
            'reportes.redenciones_club.ver',
            'reportes.redenciones_club.crear',
            'reportes.redenciones_club.editar',
            'reportes.redenciones_club.eliminar',
            'reportes.redenciones_club.exportar',
            'reportes.redenciones_club.filtrar',
            'reportes.redenciones_club.sincronizar',

            // Reportes Club Comex
            'reportes.club-comex.ver',
            'reportes.club-comex.sincronizar',

            // Reportes Vales
            'reportes.vales.ver',
            'reportes.vales.editar',
            'reportes.vales.sincronizar',

            // Reportes Vendedores B2B
            'reportes.vendedores_b2b.ver',
            'reportes.vendedores_b2b.editar',
            'reportes.vendedores_b2b.sincronizar',
            'reportes.vendedores_b2b.exportar',
            'reportes.vendedores_b2b.filtrar',

            // Reportes Desglose
            'reportes.desglose.ver',

            // Reception
            'reception.ver',
            'reception.crear',
            'reception.editar',
            'reception.eliminar',

            // Agent Versions
            'agent-versions.ver',
            'agent-versions.crear',
            'agent-versions.eliminar',

            // File Lists
            'file-lists.ver',
            'file-lists.crear',
            'file-lists.editar',
            'file-lists.eliminar',

            // DBF Files
            'dbf-files.ver',

            // Reportes Acumulaciones Club
            'reportes.acumulaciones-club.ver',
            'reportes.acumulaciones-club.editar',
            'reportes.acumulaciones-club.sincronizar',
            'reportes.acumulaciones-club.filtrar',
            'reportes.acumulaciones-club.exportar',

            // Reportes Redenciones Completo
            'reportes.redenciones-completo.ver',
            'reportes.redenciones-completo.editar',
            'reportes.redenciones-completo.filtrar',
            'reportes.redenciones-completo.exportar',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('✓ Permisos creados: '.count($permissions));
    }
}
