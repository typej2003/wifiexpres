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
    public $period = 'weekly'; // Por defecto semanal para ver las barras mejor

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
        $this->refreshChartData();
    }

    private function getRange()
    {
        return match($this->period) {
            'weekly' => [now()->subDays(7)->startOfDay(), now()->endOfDay()],
            'month'  => [now()->startOfMonth(), now()->endOfDay()],
            default  => [now()->startOfDay(), now()->endOfDay()],
        };
    }

    public function refreshChartData()
    {
        $user = Auth::user();
        $routers = Router::where('user_id', $user->id)->get();
        [$start, $end] = $this->getRange();

        // Generar etiquetas (fechas para el eje X)
        $labels = [];
        $temp = clone $start;
        while ($temp <= $end) {
            $labels[] = $temp->format('Y-m-d');
            $temp->addDay();
        }

        // Consultar logs agrupados por fecha y router
        $logs = TicketLog::whereIn('router_id', $routers->pluck('id'))
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as fecha'), 'router_id', DB::raw('count(*) as total'))
            ->groupBy('fecha', 'router_id')
            ->get();

        $colores = ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14', '#dc3545', '#20c997'];
        $datasets = [];

        foreach ($routers as $index => $router) {
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
                'barPercentage' => 0.8,
                'categoryPercentage' => 0.8
            ];
        }

        // Emitir evento para JS
        $this->dispatchBrowserEvent('refreshChart', [
            'labels' => $labels,
            'datasets' => $datasets
        ]);

        return ['labels' => $labels, 'datasets' => $datasets];
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
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');
        [$start, $end] = $this->getRange();

        $activePlans = $user->packages()
            ->wherePivot('status', 'active')
            ->wherePivot('end_date', '>=', now())
            ->get();

        $chartData = $this->refreshChartData();

        return view('livewire.dashboards.aliado-dashboard', [
            'availablePackages' => Package::where('is_active', true)->where('is_visible', true)->get(),
            'activePlans' => $activePlans,
            'pendingPlans' => $user->packages()->wherePivot('status', 'pending')->get(),
            'routers' => $routers,
            'stats' => [
                'total_routers' => $routers->count(),
                'limit_routers' => $activePlans->sum('pivot.allowed_routers'), // Suma sobre colección para evitar SQL Error
                'total_tickets' => Ticket::whereIn('router_id', $routerIds)->count(),
                'tickets_activos' => Ticket::whereIn('router_id', $routerIds)->where('estado', 'activo')->count(),
                'conexiones_periodo' => TicketLog::whereIn('router_id', $routerIds)->whereBetween('created_at', [$start, $end])->count(),
            ],
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'totalGeneral' => TicketLog::whereIn('router_id', $routerIds)->whereBetween('created_at', [$start, $end])->count(),
            'topUsuarios' => TicketLog::whereIn('router_id', $routerIds)
                ->whereBetween('created_at', [$start, $end])
                ->select('username', DB::raw('count(*) as total_conexiones'), DB::raw('sum(duration_seconds) as tiempo_total'))
                ->groupBy('username')->orderBy('total_conexiones', 'desc')->take(5)->get(),
            'ultimosLogs' => TicketLog::with('router')->whereIn('router_id', $routerIds)
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->get(),
            'dollarRate' => ExchangeRateService::getBcvRate()
        ])->layout('layouts.app');
    }
}