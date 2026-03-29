<?php

namespace App\Http\Livewire\Dashboards;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
// Asegúrate de importar el modelo de tus planes, ejemplo:
// use App\Models\Plan; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AliadoDashboard extends Component
{
    public $period = 'today';
    public $showPlanModal = false;

    protected $listeners = ['chartUpdated' => 'render'];

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

        // 1. Routers del usuario
        $misRouters = Router::where('user_id', $user->id)->get();
        $routerIds = $misRouters->pluck('id');

        // 2. Lógica de la Gráfica (Filtrada por periodo si lo deseas)
        $query = TicketLog::whereIn('router_id', $routerIds);
        
        if($this->period == 'today') {
            $query->whereDate('created_at', today());
        }

        $data = $query->select('router_id', DB::raw('count(*) as total'))
            ->groupBy('router_id')
            ->with('router:id,identity')
            ->get();

        $labels = [];
        $values = [];
        foreach ($data as $item) {
            $labels[] = $item->router->identity ?? 'Router #' . $item->router_id;
            $values[] = $item->total;
        }

        // 3. Consulta de Planes (Corrigiendo el error de BadMethodCall)
        // Ajusta 'plan_user' y los nombres de tablas según tu base de datos
        $activePlans = DB::table('plan_user')
            ->join('plans', 'plan_user.plan_id', '=', 'plans.id')
            ->where('plan_user.user_id', $user->id)
            ->where('plan_user.status', 'active')
            ->select('plans.*', 'plan_user.end_date')
            ->get();

        $pendingPlans = DB::table('plan_user')
            ->join('plans', 'plan_user.plan_id', '=', 'plans.id')
            ->where('plan_user.user_id', $user->id)
            ->where('plan_user.status', 'pending')
            ->get();

        return view('livewire.dashboards.aliado-dashboard', [
            'labels' => $labels,
            'values' => $values,
            'totalGeneral' => array_sum($values),
            'activePlans' => $activePlans,
            'pendingPlans' => $pendingPlans,
            'availablePackages' => DB::table('plans')->get(), // O tu modelo Package
            'stats' => [
                'total_routers' => $misRouters->count(),
                'limit_routers' => $activePlans->sum('limit_routers') ?: 0,
                'total_tickets' => TicketLog::whereIn('router_id', $routerIds)->count(),
                'tickets_activos' => TicketLog::whereIn('router_id', $routerIds)->where('status', 'active')->count(),
                'conexiones_periodo' => array_sum($values)
            ],
            'ultimosLogs' => TicketLog::whereIn('router_id', $routerIds)->latest()->take(10)->get(),
            'dollarRate' => 36.50 // Esto podrías traerlo de una API o tabla
        ])->layout('layouts.app');
    }
}