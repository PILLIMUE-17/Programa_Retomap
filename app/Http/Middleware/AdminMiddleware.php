<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    // Verifica que el usuario autenticado tenga permisos de administrador
    public function handle(Request $request, Closure $next): Response
    {
        if(!$request->user() || !$request->user()->es_admin) {
            return response()->json(['error' => 'Acceso denegado requiere permisos de administrador'], 403);
        }
        return $next($request);
        
    }
}
