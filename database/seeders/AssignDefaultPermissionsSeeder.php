<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignDefaultPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos del módulo Admin
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

        // Permisos de Reportes (general)
        $reportesGeneralPermissions = [
            'reportes.ver',
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
            'reportes.vendedores.ver',
            'reportes.vendedores.editar',
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
            'reportes.compras-directo.ver',
            'reportes.compras-directo.editar',
            'reportes.compras-directo.sincronizar',
        ];

        // Permisos de Metas
        $metasPermissions = [
            'metas.ver',
            'metas.crear',
            'metas.editar',
            'metas.eliminar',
            'metas.importar',
        ];

        // Permisos de Distribución
        $distributionPermissions = [
            'distribution.ver',
            'distribution.crear',
            'distribution.editar',
            'distribution.eliminar',
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
            $reportesGeneralPermissions,
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

        // Crear rol Super Admin
        $superAdmin = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $superAdmin->givePermissionTo(Permission::all());

        // Crear rol Administrativo
        $administrativo = Role::firstOrCreate([
            'name' => 'administrativo',
            'guard_name' => 'web',
        ]);
        $administrativo->givePermissionTo(array_merge(
            $reportesPermissions,
            $metasPermissions,
            $tiendasPermissions
        ));

        // Crear rol Gerente Plaza
        $gerentePlaza = Role::firstOrCreate([
            'name' => 'gerente_plaza',
            'guard_name' => 'web',
        ]);
        $gerentePlaza->givePermissionTo(array_merge(
            $reportesPermissions,
            $metasPermissions
        ));

        // Crear rol Coordinador
        $coordinador = Role::firstOrCreate([
            'name' => 'coordinador',
            'guard_name' => 'web',
        ]);
        $coordinador->givePermissionTo(array_merge(
            $reportesPermissions,
            $metasPermissions
        ));

        // Crear rol Gerente Tienda
        $gerenteTienda = Role::firstOrCreate([
            'name' => 'gerente_tienda',
            'guard_name' => 'web',
        ]);
        $gerenteTienda->givePermissionTo($reportesPermissions);

        // Crear rol Vendedor
        $vendedor = Role::firstOrCreate([
            'name' => 'vendedor',
            'guard_name' => 'web',
        ]);
        $vendedor->givePermissionTo([
            'reportes.vendedores.ver',
            'reportes.metas-ventas.ver',
        ]);

        $this->command->info('Permisos y roles creados correctamente.');
        $this->command->info('Roles creados: super_admin, administrativo, gerente_plaza, coordinador, gerente_tienda, vendedor');
    }
}
