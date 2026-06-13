<?php

namespace App\Http\Livewire\Mikrotik\Smartdata;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use App\Models\UserMikrotik;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Permanencia extends Component
{
    public $fromDate;
    public $toDate;
    public $selectedRouter = '';
    public $clientType = 'todos'; // todos, nuevos, recurrentes

    public $results = [];

    public function mount()
    {
        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->format('Y-m-d');
        $this->consultar();
    }

    public function consultar()
    {
        $start = Carbon::parse($this->fromDate)->startOfDay();
        $end = Carbon::parse($this->toDate)->endOfDay();

        // Query base de logs en el rango
        $query = TicketLog::whereBetween('created_at', [$start, $end]);

        if ($this->selectedRouter) {
            $query->where('router_id', $this->selectedRouter);
        }

        $logs = $query->get();

        // Agrupamos por Router para mostrar "Locales/Zonas"
        $grouped = $logs->groupBy('router_id');
        $this->results = [];

        foreach ($grouped as $routerId => $routerLogs) {
            $router = Router::find($routerId);
            $uniqueUsernames = $routerLogs->pluck('username')->unique();
            
            $nuevos = 0;
            $totales = 0;
            $duracionTotal = 0;
            $conteoValidoDuracion = 0;

            foreach ($uniqueUsernames as $username) {
                // Quitar prefijo T- para buscar en UserMikrotik si es necesario, 
                // pero para saber si es nuevo, miramos si tiene logs ANTES del rango
                $esRecurrente = TicketLog::where('username', $username)
                    ->where('created_at', '<', $start)
                    ->exists();

                $esNuevo = !$esRecurrente;

                // Filtrado por tipo de cliente según el botón seleccionado
                if ($this->clientType == 'nuevos' && !$esNuevo) continue;
                if ($this->clientType == 'recurrentes' && $esNuevo) continue;

                $userLogsInRange = $routerLogs->where('username', $username);
                
                if ($esNuevo) $nuevos++;
                $totales++;
                
                $duracionTotal += $userLogsInRange->sum('duration_seconds');
                $conteoValidoDuracion += $userLogsInRange->where('duration_seconds', '>', 0)->count();
            }

            if ($totales > 0) {
                $promedioSegundos = $conteoValidoDuracion > 0 ? ($duracionTotal / $conteoValidoDuracion) : 0;
                
                $this->results[] = [
                    'zona' => $router->identity ?? $router->location ?? 'Local Desconocido',
                    'nuevos' => $nuevos,
                    'totales' => $totales,
                    'promedio' => $this->formatSeconds($promedioSegundos)
                ];
            }
        }
    }

    private function formatSeconds($seconds)
    {
        $minutes = floor($seconds / 60);
        return $minutes > 0 ? $minutes . " min" : ($seconds > 0 ? "Menos de 1 min" : "N/A");
    }

    public function render()
    {
        $routers = Router::where('is_active', true)
            ->when(auth()->user()->role !== 'admin', function($q) {
                return $q->where('user_id', auth()->id());
            })->get();

        return view('livewire.mikrotik.smartdata.permanencia', compact('routers'));
    }
}
