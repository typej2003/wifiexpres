<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles  <-- Esto permite recibir múltiples roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $rolesCleaned = array_map('trim', $roles);

        // Verificamos si el rol del usuario está dentro de los permitidos ya limpios
        if (in_array($user->role, $rolesCleaned)) {
            return $next($request);
        }

        abort(403, 'No tienes permiso para acceder a esta sección.');
    }
}