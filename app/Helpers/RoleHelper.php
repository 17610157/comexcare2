<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RoleHelper
{
    /**
     * Obtener el filtro de usuario basado en su rol y asignaciones
     * 
     * Lógica:
     * - Si tiene asignaciones específicas (plaza/tienda): usar esas asignaciones
     * - Si es super_admin/admin Y NO tiene asignaciones: acceso completo
     * - usuario con plaza sin tienda específica: acceso a TODAS las tiendas de esa plaza
     * - usuario con plaza Y tienda específica: acceso solo a esas tiendas específicas
     */
    public static function getUserFilter()
    {
        $user = Auth::user();

        if (!$user) {
            return [
                'allowed' => false,
                'message' => 'No autenticado',
                'plaza' => null,
                'tienda' => null,
                'plazas_asignadas' => [],
                'tiendas_asignadas' => [],
                'tiendas_por_plaza' => [],
                'acceso_todas_tiendas' => false,
            ];
        }

        // Obtener plazas y tiendas asignadas al usuario
        $plazasAsignadas = [];
        $tiendasAsignadas = [];
        $tiendasPorPlaza = [];

        // Verificar si el usuario tiene PlazaTienda asignada
        if (method_exists($user, 'plazaTiendas')) {
            $userPlazaTiendas = $user->plazaTiendas()->get();
            
            foreach ($userPlazaTiendas as $upt) {
                // Agregar plaza si no existe
                if ($upt->plaza && !in_array($upt->plaza, $plazasAsignadas)) {
                    $plazasAsignadas[] = $upt->plaza;
                    $tiendasPorPlaza[$upt->plaza] = [];
                }
                
                // Agregar tienda específica si existe
                if ($upt->tienda && !in_array($upt->tienda, $tiendasAsignadas)) {
                    $tiendasAsignadas[] = $upt->tienda;
                    if ($upt->plaza) {
                        $tiendasPorPlaza[$upt->plaza][] = $upt->tienda;
                    }
                }
            }
        }

        // Si es super_admin o admin Y tiene asignaciones específicas, usarlas
        // Solo dar acceso completo si NO tiene asignaciones
        $esAdmin = $user->hasRole(['super_admin', 'admin']);
        $tieneAsignaciones = !empty($plazasAsignadas) || !empty($tiendasAsignadas);

        if ($esAdmin && !$tieneAsignaciones) {
            // Admin sin asignaciones = acceso completo
            return [
                'allowed' => true,
                'message' => 'Acceso completo (admin sin asignaciones)',
                'plaza' => null,
                'tienda' => null,
                'plazas_asignadas' => [],
                'tiendas_asignadas' => [],
                'tiendas_por_plaza' => [],
                'acceso_todas_tiendas' => true,
            ];
        }

        // Si no tiene asignaciones y no es admin, denegar acceso
        if (!$tieneAsignaciones) {
            return [
                'allowed' => false,
                'message' => 'No tiene plazas o tiendas asignadas. Contacte al administrador.',
                'plaza' => null,
                'tienda' => null,
                'plazas_asignadas' => [],
                'tiendas_asignadas' => [],
                'tiendas_por_plaza' => [],
                'acceso_todas_tiendas' => false,
            ];
        }

        // Determinar si tiene acceso a todas las tiendas de sus plazas
        // Si tiene plazas pero NO tiene tiendas específicas, tiene acceso a todas las tiendas de esas plazas
        $accesoTodasTiendas = empty($tiendasAsignadas) && !empty($plazasAsignadas);

        return [
            'allowed' => true,
            'message' => $esAdmin ? 'Acceso limitado (admin con asignaciones)' : 'Acceso limitado',
            'plaza' => !empty($plazasAsignadas) ? $plazasAsignadas : null,
            'tienda' => !empty($tiendasAsignadas) ? $tiendasAsignadas : null,
            'plazas_asignadas' => $plazasAsignadas,
            'tiendas_asignadas' => $tiendasAsignadas,
            'tiendas_por_plaza' => $tiendasPorPlaza,
            'acceso_todas_tiendas' => $accesoTodasTiendas,
        ];
    }

    /**
     * Obtener las tiendas a las que el usuario tiene acceso
     * Si tiene plaza sin tienda específica, devuelve todas las tiendas de esa plaza
     */
    public static function getTiendasAcceso()
    {
        $filter = self::getUserFilter();
        
        if (!$filter['allowed']) {
            return [];
        }

        // Admin tiene acceso a todo
        if (empty($filter['plazas_asignadas'])) {
            return DB::table('bi_sys_tiendas')
                ->whereNotNull('clave_tienda')
                ->pluck('clave_tienda')
                ->toArray();
        }

        // Si tiene tiendas específicas, devolverlas
        if (!empty($filter['tiendas_asignadas'])) {
            return $filter['tiendas_asignadas'];
        }

        // Si tiene plazas sin tiendas específicas, obtener todas las tiendas de esas plazas
        if ($filter['acceso_todas_tiendas']) {
            return DB::table('bi_sys_tiendas')
                ->whereIn('id_plaza', $filter['plazas_asignadas'])
                ->whereNotNull('clave_tienda')
                ->pluck('clave_tienda')
                ->toArray();
        }

        return [];
    }

    /**
     * Verificar si el usuario tiene acceso a una plaza específica
     */
    public static function hasAccessToPlaza($plaza)
    {
        $filter = self::getUserFilter();
        
        if (!$filter['allowed']) {
            return false;
        }

        // Admin tiene acceso a todo
        if (empty($filter['plazas_asignadas'])) {
            return true;
        }

        return in_array($plaza, $filter['plazas_asignadas']);
    }

    /**
     * Verificar si el usuario tiene acceso a una tienda específica
     * Considera si tiene acceso a todas las tiendas de la plaza
     */
    public static function hasAccessToTienda($tienda)
    {
        $filter = self::getUserFilter();
        
        if (!$filter['allowed']) {
            return false;
        }

        // Admin tiene acceso a todo
        if (empty($filter['tiendas_asignadas']) && empty($filter['plazas_asignadas'])) {
            return true;
        }

        // Si tiene acceso a todas las tiendas, verificar que la tienda pertenezca a una de sus plazas
        if ($filter['acceso_todas_tiendas']) {
            $tiendaPlaza = DB::table('bi_sys_tiendas')
                ->where('clave_tienda', $tienda)
                ->value('id_plaza');
            
            return $tiendaPlaza && in_array($tiendaPlaza, $filter['plazas_asignadas']);
        }

        // Si tiene tiendas específicas, verificar directamente
        return in_array($tienda, $filter['tiendas_asignadas']);
    }

    /**
     * Obtener todos los permisos del usuario
     */
    public static function getUserPermissions()
    {
        $user = Auth::user();

        if (!$user) {
            return [];
        }

        $permissions = [];

        if (method_exists($user, 'permissions')) {
            $userPermissions = $user->permissions()->pluck('name')->toArray();
            $permissions = array_merge($permissions, $userPermissions);
        }

        if (method_exists($user, 'roles')) {
            $rolePermissions = $user->roles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->pluck('name')
                ->unique()
                ->toArray();
            $permissions = array_merge($permissions, $rolePermissions);
        }

        return array_unique($permissions);
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function hasPermission($permission)
    {
        $permissions = self::getUserPermissions();
        return in_array($permission, $permissions);
    }

    /**
     * Generar filtros para reportes basados en asignaciones del usuario
     * Devuelve arrays con plazas y tiendas permitidas para usar en queries
     */
    public static function getFiltrosReporte()
    {
        $filter = self::getUserFilter();
        
        if (!$filter['allowed']) {
            return [
                'plazas' => [],
                'tiendas' => [],
                'plaza_string' => '',
                'tienda_string' => '',
            ];
        }

        $plazas = $filter['plazas_asignadas'] ?? [];
        $tiendas = self::getTiendasAcceso();

        return [
            'plazas' => $plazas,
            'tiendas' => $tiendas,
            'plaza_string' => !empty($plazas) ? implode(',', $plazas) : '',
            'tienda_string' => !empty($tiendas) ? implode(',', $tiendas) : '',
            'acceso_todas_tiendas' => $filter['acceso_todas_tiendas'] ?? false,
        ];
    }

    /**
     * Obtener listas de plazas y tiendas para los filtros de los reportes
     * Limita las opciones según las asignaciones del usuario
     */
    public static function getListasParaFiltros()
    {
        $filter = self::getUserFilter();
        
        $plazasQuery = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('id_plaza')
            ->orderBy('id_plaza');

        $tiendasQuery = DB::table('bi_sys_tiendas')
            ->distinct()
            ->whereNotNull('clave_tienda')
            ->orderBy('clave_tienda');

        $plazasAsignadas = $filter['plazas_asignadas'] ?? [];
        $tiendasAsignadas = self::getTiendasAcceso();

        if (!empty($plazasAsignadas)) {
            $plazasQuery->whereIn('id_plaza', $plazasAsignadas);
            $tiendasQuery->whereIn('id_plaza', $plazasAsignadas);
        }

        if (!empty($tiendasAsignadas)) {
            $tiendasQuery->whereIn('clave_tienda', $tiendasAsignadas);
        }

        return [
            'plazas' => $plazasQuery->pluck('id_plaza')->filter()->values(),
            'tiendas' => $tiendasQuery->pluck('clave_tienda')->filter()->values(),
            'plazas_asignadas' => $plazasAsignadas,
            'tiendas_asignadas' => $tiendasAsignadas,
        ];
    }
}
