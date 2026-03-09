<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use Illuminate\Support\Facades\Session;

class TicketAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // Buscamos el ticket que coincida con usuario y contraseña
        $ticket = Ticket::where('username', $request->username)
                        ->where('password', $request->password)
                        ->first();

        if ($ticket) {
            // Guardamos el ID en la sesión del navegador
            Session::put('ticket_id', $ticket->id);
            Session::put('router_id', $ticket->router_id);
            
            return redirect()->route('ticket.dashboard');
        }

        return back()->with('error', 'Credenciales de ticket inválidas o expiradas.');
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('ticket.login');
    }
}