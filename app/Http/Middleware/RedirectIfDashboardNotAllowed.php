<?php

namespace App\Http\Middleware;

use App\Services\LandingPage;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfDashboardNotAllowed
{
    /**
     * Quien no tenga el permiso del Panel de Control no recibe un 403: se le
     * lleva a la primera pagina que su rol si le permite, que es lo unico que
     * el menu lateral le va a mostrar.
     */
    public function handle(Request $request, Closure $next, string $permission = 'home.ver'): Response
    {
        $user = $request->user();

        if (! $user || $user->can($permission)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(403, 'No tiene permiso para ver el panel de control.');
        }

        $target = LandingPage::forUser($user);

        if (parse_url($target, PHP_URL_PATH) !== $request->path()) {
            return redirect()->to($target);
        }

        return $next($request);
    }
}
