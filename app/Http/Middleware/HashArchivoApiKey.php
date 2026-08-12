<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HashArchivoApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('X-API-Key');
        $expected = config('services.conciliacion.hash_archivos_api_key');

        if ($expected === null || $expected === '' || $key === null) {
            return response()->json([
                'error' => 'No Autorizado',
                'message' => 'Se requiere el header X-API-Key',
            ], 401);
        }

        if (! hash_equals($expected, $key)) {
            return response()->json([
                'error' => 'No Autorizado',
                'message' => 'X-API-Key inválida',
            ], 401);
        }

        $request->attributes->set('hash_client', $this->maskKey($key));

        return $next($request);
    }

    protected function maskKey(string $key): string
    {
        if (strlen($key) <= 8) {
            return '***';
        }

        return substr($key, 0, 4).'***'.substr($key, -4);
    }
}
