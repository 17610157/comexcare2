<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReleaseDatabaseConnection
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        try {
            DB::disconnect();
        } catch (\Exception $e) {
        }

        return $response;
    }
}