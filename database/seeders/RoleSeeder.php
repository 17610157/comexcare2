<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Obtener permisos
        $adminPermissions = [
            'admin.ver', 'admin.usuarios.ver', 'admin.usuarios.crear', 'admin.usuarios.editar', 'admin.usuarios.eliminar',
            'admin.roles.ver', 'admin.roles.crear', 'admin.roles.editar', 'admin.roles.eliminar',
            'admin.permissions.ver', 'admin.permissions.crear', 'admin.permissions.editar', 'admin.permissions.eliminar',
        ];

        $tiendasPermissions = ['tiendas.ver', 'tiendas.crear', 'tiendas.editar', 'tiendas.eliminar'];

        $reportesPermissions = [
            'reportes.ver', 'reportes.vendedores.ver', 'reportes.vendedores.editar', 'reportes.vendedores.sincronizar',
            'reportes.vendedores.filtrar',             'reportes.vendedores_matricial.ver', 'reportes.vendedores_matricial.editar',
            'reportes.metas-ventas.ver', 'reportes.metas-ventas.editar', 'reportes.metas-matricial.ver', 'reportes.metas-matricial.editar',
            'reportes.cartera-abonos.ver', 'reportes.cartera-abonos.editar', 'reportes.cartera-abonos.sincronizar',
            'reportes.notas-completas.ver', 'reportes.notas-completas.editar', 'reportes.notas-completas.sincronizar',
            'reportes.club-comex.ver', 'reportes.club-comex.sincronizar', 'reportes.compras-directo.ver',
            'reportes.compras-directo.editar', 'reportes.compras-directo.sincronizar', 'reportes.vendedores_b2b.ver',
            'reportes.desglose.ver', 'reportes.vales.ver', 'reportes.vales.editar',
        ];

        $metasPermissions = ['metas.ver', 'metas.crear', 'metas.editar', 'metas.eliminar', 'metas.importar'];

        $distributionPermissions = [
            'distribution.ver', 'distribution.crear', 'distribution.editar', 'distribution.eliminar',
            'reception.ver', 'reception.crear', 'reception.editar', 'reception.eliminar',
            'computers.ver', 'computers.crear', 'computers.editar', 'computers.eliminar',
            'groups.ver', 'groups.crear', 'groups.editar', 'groups.eliminar',
            'agent-versions.ver', 'agent-versions.crear', 'agent-versions.eliminar',
            'dbf-files.ver',
            'file-lists.ver', 'file-lists.crear', 'file-lists.editar', 'file-lists.eliminar',
        ];

        $userPlazaPermissions = ['user-plaza-tienda.ver', 'user-plaza-tienda.editar'];

        // Rol: Super Admin - Todos los permisos
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->givePermissionTo(Permission::all());
        $this->command->info('✓ Rol creado: super_admin (todos los permisos)');

        // Rol: Administrador
        $admin = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $admin->givePermissionTo(array_merge($adminPermissions, $reportesPermissions, $metasPermissions, $tiendasPermissions, $distributionPermissions, $userPlazaPermissions));
        $this->command->info('✓ Rol creado: administrador');

        // Rol: Gerente de Plaza
        $gerentePlaza = Role::firstOrCreate(['name' => 'gerente_plaza', 'guard_name' => 'web']);
        $gerentePlaza->givePermissionTo(array_merge($reportesPermissions, $metasPermissions, $userPlazaPermissions));
        $this->command->info('✓ Rol creado: gerente_plaza');

        // Rol: Coordinador
        $coordinador = Role::firstOrCreate(['name' => 'coordinador', 'guard_name' => 'web']);
        $coordinador->givePermissionTo(array_merge($reportesPermissions, $metasPermissions));
        $this->command->info('✓ Rol creado: coordinador');

        // Rol: Gerente de Tienda
        $gerenteTienda = Role::firstOrCreate(['name' => 'gerente_tienda', 'guard_name' => 'web']);
        $gerenteTienda->givePermissionTo($reportesPermissions);
        $this->command->info('✓ Rol creado: gerente_tienda');

        // Rol: Vendedor
        $vendedor = Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web']);
        $vendedor->givePermissionTo(['reportes.vendedores.ver', 'reportes.metas-ventas.ver']);
        $this->command->info('✓ Rol creado: vendedor');

        // Rol: Solo Lectura
        $soloLectura = Role::firstOrCreate(['name' => 'solo_lectura', 'guard_name' => 'web']);
        $soloLectura->givePermissionTo(['reportes.ver', 'reportes.vendedores.ver', 'reportes.metas-ventas.ver']);
        $this->command->info('✓ Rol creado: solo_lectura');

        $this->command->info('✓ Roles creados: super_admin, administrador, gerente_plaza, coordinador, gerente_tienda, vendedor, solo_lectura');
    }
}
