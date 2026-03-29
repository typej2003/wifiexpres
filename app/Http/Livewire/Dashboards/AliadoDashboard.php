<?php

namespace App\Http\Livewire\Dashboards;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use App\Models\Package; // Asumiendo el modelo de planes
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AliadoDashboard extends Component
{
    public $period = 'today';
    public $showPlanModal = false;

    public function setPeriod($p)
    {
        $this->period = $p;
        $this->emit('chartUpdated');
    }

    public function openModal() { $this->showPlanModal = true; }
    public function closeModal() { $this->showPlanModal = false; }

    public function render()
    {
        $user = Auth::user();
        
        // Datos para la gráfica y routers
        $misRouters = Router::where('user_id', $user->id)->get();
        $routerIds = $misRouters->pluck('id');

        $data = TicketLog::whereIn('router_id', $routerIds)
            ->select('router_id', DB::raw('count(*) as total'))
            ->groupBy('router_id')
            ->with('router:id,identity')
            ->get();

        $labels = [];
        $values = [];
        foreach ($data as $item) {
            $labels[] = $item->router->identity ?? 'Router #' . $item->router_id;
            $values[] = $item->total;
        }

        // Simulación de datos adicionales (ajusta según tus modelos reales)
        return view('livewire.dashboards.aliado-dashboard', [
            'labels' => $labels,
            'values' => $values,
            'totalGeneral' => array_sum($values),
            'activePlans' => $user->plans()->wherePivot('status', 'active')->get(), // Ejemplo
            'pendingPlans' => $user->plans()->wherePivot('status', 'pending')->get(), // Ejemplo
            'availablePackages' => Package::all(),
            'stats' => [
                'total_routers' => $misRouters->count(),
                'limit_routers' => 10, // Ejemplo
                'total_tickets' => 150,
                'tickets_activos' => 45,
                'conexiones_periodo' => array_sum($values)
            ],
            'ultimosLogs' => TicketLog::whereIn('router_id', $routerIds)->latest()->take(10)->get(),
            'dollarRate' => 36.50 // Ejemplo
        ])->layout('layouts.app');
    }
}