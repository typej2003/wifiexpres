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
        $routers = Router::where('user_id', $user->id)->get();
        
        [$start, $end] = match($this->period) {
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };

        $labels = [];
        $sqlFormat = '';
        
        // Configuración de Ejes y Formatos
        if ($this->period == 'today') {
            $sqlFormat = '%H:00';
            for ($i = 0; $i < 24; $i++) { $labels[] = sprintf("%02d:00", $i); }
        } elseif ($this->period == 'weekly') {
            $sqlFormat = '%d/%m';
            $temp = $start->copy();
            for ($i = 0; $i < 7; $i++) { 
                $labels[] = $temp->format('d/m'); 
                $temp->addDay(); 
            }
        } else {
            $sqlFormat = '%d'; 
            $daysInMonth = now()->daysInMonth;
            for ($i = 1; $i <= $daysInMonth; $i++) { $labels[] = sprintf("%02d", $i); }
        }

        $datasets = [];
        $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#5a5c69'];

        foreach ($routers as $index => $router) {
            // Obtenemos conteo agrupado por el formato de fecha elegido
            $dataQuery = TicketLog::where('router_id', $router->id)
                ->whereBetween('created_at', [$start, $end])
                ->select(DB::raw("DATE_FORMAT(created_at, '$sqlFormat') as time_label"), DB::raw('count(*) as total'))
                ->groupBy('time_label')
                ->pluck('total', 'time_label');

            $datasetData = [];
            foreach ($labels as $l) {
                // Si no hay datos en esa hora/día, ponemos 0 para que la barra no se rompa
                $datasetData[] = $dataQuery[$l] ?? 0;
            }

            $datasets[] = [
                'label' => $router->identity,
                'data' => $datasetData,
                'backgroundColor' => $colors[$index % count($colors)],
                'borderRadius' => 5, // Barras redondeadas arriba
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets
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
        $activePlans = $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->get();
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');

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
                'conexiones_periodo' => TicketLog::whereIn('router_id', $routerIds)
                                        ->whereBetween('created_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            ],
            'chartInitialData' => $this->getChartData(),
            'ultimosLogs' => TicketLog::whereIn('router_id', $routerIds)->with('router')->latest()->take(6)->get(),
            'dollarRate' => ExchangeRateService::getBcvRate()
        ])->layout('layouts.app');
    }
}