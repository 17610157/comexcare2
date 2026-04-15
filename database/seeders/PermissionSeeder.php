<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos de Administración
        $adminPermissions = [
            'admin.ver',
            'admin.usuarios.ver',
            'admin.usuarios.crear',
            'admin.usuarios.editar',
            'admin.usuarios.eliminar',
            'admin.roles.ver',
            'admin.roles.crear',
            'admin.roles.editar',
            'admin.roles.eliminar',
            'admin.permissions.ver',
            'admin.permissions.crear',
            'admin.permissions.editar',
            'admin.permissions.eliminar',
        ];

        // Permisos de Tiendas
        $tiendasPermissions = [
            'tiendas.ver',
            'tiendas.crear',
            'tiendas.editar',
            'tiendas.eliminar',
        ];

        // Permisos de Reportes
        $reportesPermissions = [
            'reportes.ver',
            'reportes.vendedores.ver',
            'reportes.vendedores.editar',
            'reportes.vendedores.sincronizar',
            'reportes.vendedores.filtrar',
            'reportes.vendedores.matricial.ver',
            'reportes.vendedores.matricial.editar',
            'reportes.metas-ventas.ver',
            'reportes.metas-ventas.editar',
            'reportes.metas-matricial.ver',
            'reportes.metas-matricial.editar',
            'reportes.cartera-abonos.ver',
            'reportes.cartera-abonos.editar',
            'reportes.cartera-abonos.sincronizar',
            'reportes.notas-completas.ver',
            'reportes.notas-completas.editar',
            'reportes.notas-completas.sincronizar',
            'reportes.club-comex.ver',
            'reportes.club-comex.sincronizar',
            'reportes.compras-directo.ver',
            'reportes.compras-directo.editar',
            'reportes.compras-directo.sincronizar',
            'reportes.vendedores_b2b.ver',
            'reportes.desglose.ver',
        ];

        // Permisos de Metas
        $metasPermissions = [
            'metas.ver',
            'metas.crear',
            'metas.editar',
            'metas.eliminar',
            'metas.importar',
        ];

        // Permisos de Distribución y Recepción
        $distributionPermissions = [
            'distribution.ver',
            'distribution.crear',
            'distribution.editar',
            'distribution.eliminar',
            'reception.ver',
            'reception.crear',
            'reception.editar',
            'reception.eliminar',
            'computers.ver',
            'computers.crear',
            'computers.editar',
            'computers.eliminar',
            'groups.ver',
            'groups.crear',
            'groups.editar',
            'groups.eliminar',
            'agent-versions.ver',
            'agent-versions.crear',
            'agent-versions.eliminar',
            'dbf-files.ver',
        ];

        // Permisos de User Plaza Tienda
        $userPlazaPermissions = [
            'user-plaza-tienda.ver',
            'user-plaza-tienda.editar',
        ];

        // Combinar todos los permisos
        $allPermissions = array_merge(
            $adminPermissions,
            $tiendasPermissions,
            $reportesPermissions,
            $metasPermissions,
            $distributionPermissions,
            $userPlazaPermissions
        );

        // Crear permisos
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('✓ Permisos creados: '.count($allPermissions));
    }
}
