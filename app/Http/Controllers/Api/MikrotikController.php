<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketLog;
use App\Models\Ticket;
use App\Models\Sale;
use App\Models\Router;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\Log;

class MikrotikController extends Controller
{
    public function logConnection(Request $request)
    {
        $username = $request->user;
        $mac      = $request->mac;
        $type     = $request->type; // 'login' o 'logout'
        $identity = $request->identity; // Nombre del MikroTik

        try {
            // Buscamos el router por identity si no viene un ID numérico claro
            $router = Router::where('identity', $identity)->first();
            
            if (!$router) {
                Log::error("LogConnection: No se encontró el router con identity: $identity");
                return response()->json(['error' => 'Router no encontrado'], 404);
            }

            $routerId = $router->id;

            if ($type === 'login') {
                // Registrar el log de conexión
                TicketLog::create([
                    'router_id'   => $routerId,
                    'username'    => $username,
                    'mac_address' => $mac,
                    'created_at'  => now(),
                ]);

                // Buscamos el ticket (puede ser por username/teléfono)
                // Quitamos la restricción estricta de router_id si es un usuario global o de pasarela,
                // pero lo ideal es que el ticket esté asociado al router.
                $ticket = Ticket::where('username', $username)
                    ->where('router_id', $routerId)
                    ->first();

                if ($ticket) {
                    $currentRate = ExchangeRateService::getBcvRate();

                    $montoUsd = round((float)$ticket->costo / $currentRate, 4);

                    // Si el ticket se usa por PRIMERA VEZ
                    if (!$ticket->activado) {
                        

                        $ticket->update([
                            'activado' => true,
                            'estado' => 'activo',
                            'fecha_uso' => now()
                        ]);

                        // REGISTRAMOS LA VENTA
                        Sale::create([
                            'user_id'      => $router->user_id, // El dueño del router
                            'router_id'    => $routerId,
                            'type'         => 'ticket_fisico',
                            'reference_id' => $ticket->id,
                            'description'  => "Activación Ticket: " . $ticket->username . " (" . $ticket->plan . ")",
                            'amount_usd'   => $montoUsd,
                            'amount_bs'    => $ticket->costo,
                            'rate'         => $currentRate,
                        ]);
                    } else {
                        $ticket->update(['estado' => 'activo']);

                        // REGISTRAMOS LA VENTA
                        Sale::create([
                            'user_id'      => $router->user_id, // El dueño del router
                            'router_id'    => $routerId,
                            'type'         => 'ticket_fisico',
                            'reference_id' => $ticket->id,
                            'description'  => "Activación Ticket: " . $ticket->username . " (" . $ticket->plan . ")",
                            'amount_usd'   => $montoUsd,
                            'amount_bs'    => $ticket->costo,
                            'rate'         => $currentRate,
                        ]);
                    }
                }
                return response()->json(['status' => 'logged_in', 'router' => $identity]);
            } 
            
            if ($type === 'logout') {
                $log = TicketLog::where('username', $username)
                    ->where('router_id', $routerId)
                    ->whereNull('disconnected_at')
                    ->latest()
                    ->first();

                if ($log) {
                    $disconnectedAt = now();
                    $duration = $disconnectedAt->diffInSeconds($log->created_at);
                    $log->update(['disconnected_at' => $disconnectedAt, 'duration_seconds' => $duration]);

                    $ticket = Ticket::where('username', $username)->where('router_id', $routerId)->first();
                    if ($ticket) {
                        $ticket->increment('tiempo_consumido', $duration);
                        $ticket->update(['estado' => 'usado']);
                    }
                }
                return response()->json(['status' => 'logged_out']);
            }

        } catch (\Exception $e) {
            Log::error("Error en MikrotikController: " . $e->getMessage());
            return response()->json(['error' => 'fail'], 500);
        }
    }
}