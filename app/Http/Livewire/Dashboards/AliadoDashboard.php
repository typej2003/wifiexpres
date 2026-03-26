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
        $data = [];

        if ($this->period === 'today') {
            // Agrupación por HORA para el día de hoy
            $logs = TicketLog::whereIn('router_id', $routerIds)
                ->whereDate('created_at', Carbon::today())
                ->select(DB::raw('HOUR(created_at) as hora'), DB::raw('count(*) as total'))
                ->groupBy('hora')
                ->pluck('total', 'hora');

            for ($i = 0; $i < 24; $i++) {
                $labels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $data[] = $logs[$i] ?? 0;
            }
        } else {
            // Agrupación por ROUTER para Semana/Mes
            $start = $this->period === 'weekly' ? now()->startOfWeek() : now()->startOfMonth();
            $routers = Router::where('user_id', $user->id)->get();
            
            $logs = TicketLog::whereIn('router_id', $routerIds)
                ->where('created_at', '>=', $start)
                ->select('router_id', DB::raw('count(*) as total'))
                ->groupBy('router_id')
                ->pluck('total', 'router_id');

            foreach ($routers as $router) {
                $labels[] = $router->identity;
                $data[] = $logs[$router->id] ?? 0;
            }
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Conexiones',
                'data' => $data,
                'backgroundColor' => '#0d6efd',
                'borderRadius' => 5
            ]]
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
        session()->flash('message', '¡Solicitud enviada!');
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