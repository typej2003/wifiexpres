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
        $this->emit('updateChart', $this->getChartData());
    }

    public function getChartData()
    {
        $user = Auth::user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');
        
        $labels = [];
        $datasetData = [];
        $bgColors = [];

        if ($this->period === 'today') {
            // LÓGICA POR HORAS (00 a 23)
            $start = now()->startOfDay();
            $end = now()->endOfDay();

            $logs = TicketLog::whereIn('router_id', $routerIds)
                ->whereBetween('created_at', [$start, $end])
                ->select(DB::raw('HOUR(created_at) as hora'), DB::raw('count(*) as total'))
                ->groupBy('hora')
                ->pluck('total', 'hora');

            for ($i = 0; $i < 24; $i++) {
                $labels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $datasetData[] = $logs[$i] ?? 0;
                $bgColors[] = '#0d6efd'; // Color único para todas si es por horas
            }
            $labelName = 'Conexiones por Hora';
        } else {
            // LÓGICA POR ROUTER (Semana/Mes)
            $start = $this->period === 'weekly' ? now()->startOfWeek() : now()->startOfMonth();
            
            $routers = Router::where('user_id', $user->id)->get();
            $logs = TicketLog::whereIn('router_id', $routerIds)
                ->where('created_at', '>=', $start)
                ->select('router_id', DB::raw('count(*) as total'))
                ->groupBy('router_id')
                ->pluck('total', 'router_id');

            foreach ($routers as $index => $router) {
                $labels[] = $router->identity;
                $datasetData[] = $logs[$router->id] ?? 0;
                $bgColors[] = ['#6610f2', '#6f42c1', '#d63384', '#fd7e14', '#ffc107'][$index % 5];
            }
            $labelName = 'Total por Router';
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $labelName,
                    'data' => $datasetData,
                    'backgroundColor' => $bgColors,
                    'borderRadius' => 5,
                    'borderWidth' => 0,
                    'barPercentage' => 0.8
                ]
            ]
        ];
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
    public function closeModal() { $this->showPlanModal = false; }

    public function render()
    {
        $user = Auth::user();
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');
        $activePlans = $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->get();

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