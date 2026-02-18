<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReporteCacheMiddleware
{
    /**
     * Handle an incoming request para caché de reportes pesados
     */
    public function handle(Request $request, Closure $next, ...$reportTypes)
    {
        // Solo aplicar caché a métodos GET para reportes específicos
        if (!$request->isMethod('GET') || empty($reportTypes)) {
            return $next($request);
        }

        $currentReportType = $this->getCurrentReportType($request);
        
        // Verificar si el reporte actual está en la lista de tipos a cachear
        if (!in_array($currentReportType, $reportTypes)) {
            return $next($request);
        }

        // Crear clave de caché específica
        $cacheKey = $this->generateCacheKey($request, $currentReportType);
        
        // Verificar si ya está en caché
        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            
            // Agregar header para indicar que viene de caché
            return response($cachedData['content'])
                ->header('X-Cache-Hit', 'true')
                ->header('X-Cache-Key', $cacheKey)
                ->header('X-Cache-Time', $cachedData['timestamp']);
        }

        // Ejecutar la solicitud
        $response = $next($request);

        // Solo cachear respuestas exitosas
        if ($response->getStatusCode() === 200 && !$this->isExcludedRoute($request)) {
            $this->storeInCache($cacheKey, $response, $currentReportType);
        }

        return $response;
    }

    /**
     * Determinar el tipo de reporte basado en la ruta
     */
    private function getCurrentReportType(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, 'cartera-abonos')) {
            return 'cartera_abonos';
        } elseif (str_contains($path, 'vendedores')) {
            return 'vendedores';
        } elseif (str_contains($path, 'metas-ventas')) {
            return 'metas_ventas';
        } elseif (str_contains($path, 'metas-matricial')) {
            return 'metas_matricial';
        } elseif (str_contains($path, 'compras-directo')) {
            return 'compras_directo';
        }
        
        return 'general';
    }

    /**
     * Generar clave de caché única para la solicitud
     */
    private function generateCacheKey(Request $request, string $reportType): string
    {
        $params = $request->all();
        
        // Ordenar parámetros para consistencia
        ksort($params);
        
        // Excluir parámetros que no afectan el resultado
        $excludeParams = ['_', 'XDEBUG_SESSION_START'];
        foreach ($excludeParams as $param) {
            unset($params[$param]);
        }
        
        $paramString = http_build_query($params);
        
        return "reporte_{$reportType}_" . md5($paramString);
    }

    /**
     * Almacenar respuesta en caché
     */
    private function storeInCache(string $cacheKey, $response, string $reportType): void
    {
        try {
            $content = $response->getContent();
            
            // Diferentes tiempos de caché según el tipo de reporte
            $cacheTimes = [
                'cartera_abonos' => 1800, // 30 minutos - reporte pesado
                'vendedores' => 3600,      // 1 hora
                'metas_ventas' => 1800,   // 30 minutos
                'metas_matricial' => 3600,  // 1 hora
                'compras_directo' => 1800,  // 30 minutos
                'general' => 900           // 15 minutos
            ];
            
            $cacheTime = $cacheTimes[$reportType] ?? $cacheTimes['general'];
            
            $cacheData = [
                'content' => $content,
                'timestamp' => now()->toISOString(),
                'cache_time' => $cacheTime
            ];
            
            Cache::put($cacheKey, $cacheData, $cacheTime);
            
            Log::info("Reporte cacheado: {$reportType}", [
                'cache_key' => $cacheKey,
                'cache_time' => $cacheTime,
                'content_size' => strlen($content)
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error al cachear reporte: {$e->getMessage()}");
        }
    }

    /**
     * Verificar si la ruta debe excluirse de caché
     */
    private function isExcludedRoute(Request $request): bool
    {
        $excludedRoutes = [
            'admin/*',
            'logout',
            'login',
            'register'
        ];
        
        $path = $request->path();
        
        foreach ($excludedRoutes as $excluded) {
            if (fnmatch($excluded, $path)) {
                return true;
            }
        }
        
        return false;
    }
}