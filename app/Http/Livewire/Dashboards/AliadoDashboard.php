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
    public $periodo = 'semana'; 
    public $fecha_desde, $fecha_hasta;
    public $router_id = ''; 

    public function mount()
    {
        $this->fecha_desde = now()->subDays(7)->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
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

    public function updatedPeriodo($value)
    {
        if ($value === 'dia') {
            $this->fecha_desde = now()->format('Y-m-d');
        } elseif ($value === 'semana') {
            $this->fecha_desde = now()->subDays(7)->format('Y-m-d');
        } elseif ($value === 'mes') {
            $this->fecha_desde = now()->subMonth()->format('Y-m-d');
        }
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function selectPlan($packageId)
    {
        $package = Package::findOrFail($packageId);
        $user = Auth::user();
        
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
    public function closeModal() { $this->closeModal(); }

    public function render()
    {
        $user = Auth::user();
        $misRouters = Router::where('user_id', $user->id)->get();
        $routerIds = $misRouters->pluck('id');

        $desde = Carbon::parse($this->fecha_desde)->startOfDay();
        $hasta = Carbon::parse($this->fecha_hasta)->endOfDay();

        // 1. Eje X
        $labels = [];
        $temp = clone $desde;
        while ($temp <= $hasta) {
            $labels[] = $temp->format('Y-m-d');
            $temp->addDay();
        }

        // 2. Datos
        $logsQuery = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$desde, $hasta]);
        
        $logs = (clone $logsQuery)
            ->select(DB::raw('DATE(created_at) as fecha'), 'router_id', DB::raw('count(*) as total'))
            ->groupBy('fecha', 'router_id')
            ->get();

        // 3. Datasets
        $colores = ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14', '#dc3545', '#20c997'];
        $datasets = [];

        foreach ($misRouters as $index => $router) {
            if (!empty($this->router_id) && $this->router_id != $router->id) continue;

            $dataValues = [];
            foreach ($labels as $label) {
                $val = $logs->where('fecha', $label)->where('router_id', $router->id)->first();
                $dataValues[] = $val ? $val->total : 0;
            }
            $datasets[] = [
                'label' => $router->identity,
                'data' => $dataValues,
                'backgroundColor' => $colores[$index % count($colores)],
                'borderRadius' => 5,
            ];
        }

        $this->dispatchBrowserEvent('updateMultiChart', ['labels' => $labels, 'datasets' => $datasets]);

        $activePlans = $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->get();

        // Cálculo de Routers Online (Actividad en los últimos 5 minutos)
        $routersOnlineCount = Router::where('user_id', $user->id)
            ->whereHas('logs', function($q) {
                $q->where('created_at', '>=', now()->subMinutes(5));
            })->count();

        return view('livewire.dashboards.aliado-dashboard', [
            'availablePackages' => Package::where('is_active', true)->where('is_visible', true)->get(),
            'activePlans' => $activePlans,
            'pendingPlans' => $user->packages()->wherePivot('status', 'pending')->get(),
            'routers' => $misRouters,
            'stats' => [
                'total_routers' => $misRouters->count(),
                'routers_online' => $routersOnlineCount,
                'limit_routers' => $activePlans->sum('pivot.allowed_routers'),
                'total_tickets' => Ticket::whereIn('router_id', $routerIds)->count(),
                'tickets_activos' => Ticket::whereIn('router_id', $routerIds)->where('estado', 'activo')->count(),
                'conexiones_periodo' => $logs->sum('total'),
            ],
            'topUsuarios' => (clone $logsQuery)
                ->select('username', DB::raw('count(*) as total_conexiones'), DB::raw('sum(duration_seconds) as tiempo_total'))
                ->groupBy('username')->orderBy('total_conexiones', 'desc')->take(5)->get(),
            'ultimosLogs' => TicketLog::with('router')->whereIn('router_id', $routerIds)->latest()->take(8)->get(),
            'dollarRate' => ExchangeRateService::getBcvRate(),
            'labels' => $labels,
            'datasets' => $datasets
        ])->layout('layouts.app');
    }
}