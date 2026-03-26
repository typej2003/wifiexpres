<?php

namespace App\Http\Livewire\Dashboards;

use Livewire\Component;
use App\Models\Package;
use App\Models\Router;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AliadoDashboard extends Component
{
    public $showPlanModal = false;
    public $period = 'today';

    protected $listeners = ['refreshDashboard' => '$refresh'];

    public function mount()
    {
        $this->checkInitialPlan();
    }

    public function checkInitialPlan()
    {
        $user = Auth::user();
        $hasActive = $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->exists();
        $hasPending = $user->packages()->wherePivot('status', 'pending')->exists();

        if (!$hasActive && !$hasPending) {
            $this->showPlanModal = true;
        }
    }

    public function setPeriod($value) 
    { 
        $this->period = $value; 
        // Importante: Emitimos el evento para que JS reciba los nuevos datos de las barras
        $this->emit('updateChart', $this->getChartData());
    }

    /**
     * Prepara los datos para el gráfico de barras: Una barra por Router
     */
    public function getChartData()
    {
        $user = Auth::user();
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');
        
        // Definir rango según el filtro
        $start = match($this->period) {
            'weekly' => now()->startOfWeek(),
            'month'  => now()->startOfMonth(),
            default  => now()->startOfDay(),
        };
        $end = now();

        // Agrupamos el conteo de logs por router_id en el periodo seleccionado
        $logs = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$start, $end])
            ->select('router_id', DB::raw('count(*) as total'))
            ->groupBy('router_id')
            ->pluck('total', 'router_id');

        $labels = [];
        $data = [];
        $colors = ['#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#fd7e14', '#ffc107', '#198754'];
        $bgColors = [];

        foreach ($routers as $index => $router) {
            $labels[] = $router->identity; // Nombre del Router abajo
            $data[] = $logs[$router->id] ?? 0; // Cantidad de logs o 0
            $bgColors[] = $colors[$index % count($colors)]; // Color único por barra
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Conexiones',
                    'data' => $data,
                    'backgroundColor' => $bgColors,
                    'borderRadius' => 6,
                    'borderWidth' => 0
                ]
            ]
        ];
    }

    public function selectPlan($packageId)
    {
        $package = Package::findOrFail($packageId);
        $user = Auth::user();
        
        $alreadyPending = $user->packages()->where('package_id', $packageId)->wherePivot('status', 'pending')->exists();
        if($alreadyPending) {
            session()->flash('error', 'Ya tienes una solicitud pendiente para este plan.');
            return;
        }

        $user->packages()->attach($package->id, [
            'start_date' => now(),
            'end_date' => now()->addMonths($package->duration_months),
            'status' => 'pending',
            'allowed_routers' => $package->limit_routers,
            'router_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $this->showPlanModal = false;
        session()->flash('message', '¡Solicitud enviada! Tu plan se activará pronto.');
    }

    public function openModal() { $this->showPlanModal = true; }
    
    public function closeModal() 
    { 
        $user = Auth::user();
        $hasPlan = $user->packages()->wherePivotIn('status', ['active', 'pending'])->exists();
        if ($hasPlan) { $this->showPlanModal = false; }
    }

    public function render()
    {
        $user = Auth::user();
        $activePlans = $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->get();
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');

        $start = match($this->period) {
            'weekly' => now()->startOfWeek(),
            'month'  => now()->startOfMonth(),
            default  => now()->startOfDay(),
        };

        return view('livewire.dashboards.aliado-dashboard', [
            'availablePackages' => Package::where('is_active', true)->where('is_visible', true)->get(),
            'activePlans' => $activePlans,
            'pendingPlans' => $user->packages()->wherePivot('status', 'pending')->get(),
            'routers' => $routers,
            'stats' => [
                'total_routers' => $routers->count(),
                'limit_routers' => $activePlans->sum('pivot.allowed_routers'),
                'total_tickets' => Ticket::whereIn('router_id', $routerIds)->count(),
                'tickets_activos' => TicketLog::whereIn('router_id', $routerIds)->whereNull('disconnected_at')->count(),
                'conexiones_periodo' => TicketLog::whereIn('router_id', $routerIds)->whereBetween('created_at', [$start, now()])->count(),
            ],
            'chartInitialData' => $this->getChartData(),
            'ultimosLogs' => TicketLog::whereIn('router_id', $routerIds)->with('router')->latest()->take(6)->get(),
            'dollarRate' => ExchangeRateService::getBcvRate()
        ])->layout('layouts.app');
    }
}