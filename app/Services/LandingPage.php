<?php

namespace App\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

class LandingPage
{
    /**
     * Primera pagina que el usuario tiene permitido ver.
     *
     * Se recorre el menu lateral en orden, que ya declara el permiso de cada
     * opcion con su clave 'can'. Asi el menu y el destino no pueden
     * desincronizarse: si una opcion aparece, es que el usuario puede verla.
     */
    public static function forUser(?Authenticatable $user): string
    {
        if (! $user) {
            return route('login');
        }

        return self::firstAllowed($user) ?? route('sin-accesos');
    }

    public static function firstAllowed(Authenticatable $user): ?string
    {
        $found = null;

        self::walk(config('adminlte.menu', []), $user, $found);

        return $found;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     */
    private static function walk(array $nodes, Authenticatable $user, ?string &$found): void
    {
        foreach ($nodes as $node) {
            if ($found !== null) {
                return;
            }

            $url = $node['url'] ?? null;
            $can = $node['can'] ?? null;

            // Sin 'can' la opcion es visible para cualquiera autenticado, igual
            // que antes de que el Panel de Control fuera un permiso.
            if ($url !== null && ($can === null || Gate::forUser($user)->allows($can))) {
                $found = url('/'.ltrim($url, '/'));

                return;
            }

            if (isset($node['submenu']) && is_array($node['submenu'])) {
                self::walk($node['submenu'], $user, $found);
            }
        }
    }
}
