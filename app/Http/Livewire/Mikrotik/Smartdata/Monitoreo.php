<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use App\Models\UserMikrotik;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Monitoreo extends Component
{
    public function render()
    {
        $user = auth()->user();
        
        // Obtener los IDs de routers permitidos para el usuario actual
        $allowedRouterIds = Router::where('is_active', true)
            ->when($user->role !== 'admin', function($q) use ($user) {
                return $q->where('user_id', $user->id);
            })->pluck('id');

        $todayStart = Carbon::now()->startOfDay();
        $todayEnd = Carbon::now()->endOfDay();

        // 1. Estadísticas de hoy
        // Conectados Ahora: Registros en TicketLog que no tienen fecha de desconexión
        $connectedNow = TicketLog::whereIn('router_id', $allowedRouterIds)
            ->whereNull('disconnected_at')
            ->count();

        // Entradas Hoy: Registros de TicketLog creados hoy
        $entriesToday = TicketLog::whereIn('router_id', $allowedRouterIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->count();

        // Salidas Hoy: Registros de TicketLog que terminaron (tienen duración) hoy
        $exitsToday = TicketLog::whereIn('router_id', $allowedRouterIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('duration_seconds', '>', 0)
            ->count();

        // 2. Lista de Movimientos (últimos 20 eventos)
        $logs = TicketLog::with('router')
            ->whereIn('router_id', $allowedRouterIds)
            ->latest()
            ->take(20)
            ->get();

        // Mapear nombres de usuario para evitar N+1 consultas
        $macs = $logs->pluck('username')->map(fn($u) => Str::after($u, 'T-'))->unique();
        $mikrotikUsers = UserMikrotik::whereIn('name', $macs)
            ->whereIn('router_id', $allowedRouterIds)
            ->get()
            ->keyBy('name');

        $movements = $logs->map(function($log) use ($mikrotikUsers) {
            $mac = Str::after($log->username, 'T-');
            $u = $mikrotikUsers->get($mac);
            
            $clientName = $u ? ($u->full_name ?? $u->name) : $mac;
            
            $action = $log->disconnected_at 
                ? "se desconectó (salió del local)" 
                : "se acaba de conectar";

            return [
                'time' => $log->created_at->format('H:i'),
                'user' => $clientName,
                'action_type' => $log->disconnected_at ? 'disconnect' : 'connect',
                'action' => $action,
                'location' => $log->ubicacion_fisica
            ];
        });

        return view('livewire.mikrotik.smartdata.monitoreo', [
            'connectedNow' => $connectedNow,
            'entriesToday' => $entriesToday,
            'exitsToday' => $exitsToday,
            'movements' => $movements
        ]);
    }
}
