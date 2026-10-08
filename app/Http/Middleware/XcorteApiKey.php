<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class XcorteApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key');

        if ($key === null || $key === '') {
            return response()->json([
                'error' => 'No Autorizado',
                'message' => 'Se requiere el header X-API-Key',
            ], 401);
        }

        $agenteKey = config('services.cortes.api_key_agente');
        $appKey = config('services.cortes.api_key_app');

        if (is_string($agenteKey) && $agenteKey !== '' && hash_equals($agenteKey, $key)) {
            $request->attributes->set('corte_origen', 'agente');
        } elseif (is_string($appKey) && $appKey !== '' && hash_equals($appKey, $key)) {
            $request->attributes->set('corte_origen', 'aplicacion');
        } else {
            return response()->json([
                'error' => 'No Autorizado',
                'message' => 'X-API-Key inválida',
            ], 401);
        }

        return $next($request);
    }
}
