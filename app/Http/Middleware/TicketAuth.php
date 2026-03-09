<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class TicketAuth
{
    /**
     * Maneja una solicitud entrante.
     */
    public function handle(Request $request, Closure $next)
    {
        // Verificamos si la variable 'ticket_id' existe en la sesión
        if (!Session::has('ticket_id')) {
            // Si no existe, redirigimos al login con un mensaje de error
            return redirect()->route('ticket.login')->with('error', 'Debes ingresar tu ticket para ver esta sección.');
        }

        return $next($request);
    }
}