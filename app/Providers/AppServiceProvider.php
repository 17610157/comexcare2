<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::if('role', function ($role) {
            $user = Auth::user();
            if (! $user) {
                return false;
            }

            return $user->hasRole($role);
        });

        Blade::if('hasPermission', function ($permission) {
            $user = Auth::user();
            if (! $user || ! $permission) {
                return false;
            }

            return $user->hasPermissionTo($permission);
        });

        Blade::if('hasAnyPermission', function ($permissions) {
            $user = Auth::user();
            if (! $user || empty($permissions)) {
                return false;
            }
            foreach ($permissions as $permission) {
                if ($user->hasPermissionTo($permission)) {
                    return true;
                }
            }

            return false;
        });

        Blade::if('isSuperAdmin', function () {
            $user = Auth::user();
            if (! $user) {
                return false;
            }

            return $user->hasRole('super_admin');
        });

        Blade::if('isAdmin', function () {
            $user = Auth::user();
            if (! $user) {
                return false;
            }

            return in_array($user->getRoleNames()->first(), ['super_admin', 'administrativo']);
        });
    }
}
