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
    public $period = 'weekly'; // Cambiado a weekly por defecto para mejor visualización

    public function mount() { $this->checkInitialPlan(); }

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

        // Generar etiquetas (fechas)
        $labels = [];
        $temp = clone $start;
        while ($temp <= $end) {
            $labels[] = $temp->format('Y-m-d');
            $temp->addDay();
        }

        // Consultar logs
        $logs = TicketLog::whereIn('router_id', $routers->pluck('id'))
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as fecha'), 'router_id', DB::raw('count(*) as total'))
            ->groupBy('fecha', 'router_id')
            ->get();

        $colores = ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14', '#dc3545'];
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
                'borderRadius' => 5
            ];
        }

        $this->dispatchBrowserEvent('refreshChart', [
            'labels' => $labels,
            'datasets' => $datasets
        ]);

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    public function render()
    {
        $user = Auth::user();
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');
        [$start, $end] = $this->getRange();

        $chartData = $this->refreshChartData();

        return view('livewire.dashboards.aliado-dashboard', [
            'availablePackages' => Package::where('is_active', true)->where('is_visible', true)->get(),
            'activePlans' => $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->get(),
            'pendingPlans' => $user->packages()->wherePivot('status', 'pending')->get(),
            'routers' => $routers,
            'stats' => [
                'total_routers' => $routers->count(),
                'limit_routers' => $user->packages()->wherePivot('status', 'active')->sum('pivot.allowed_routers'),
                'total_tickets' => Ticket::whereIn('router_id', $routerIds)->count(),
                'tickets_activos' => Ticket::whereIn('router_id', $routerIds)->where('estado', 'activo')->count(),
                'conexiones_periodo' => TicketLog::whereIn('router_id', $routerIds)->whereBetween('created_at', [$start, $end])->count(),
            ],
            'labels' => $chartData['labels'],
            'datasets' => $chartData['datasets'],
            'totalGeneral' => TicketLog::whereIn('router_id', $routerIds)->whereBetween('created_at', [$start, $end])->count(),
            'ultimosLogs' => TicketLog::with('router')->whereIn('router_id', $routerIds)->latest()->take(10)->get(),
            'dollarRate' => ExchangeRateService::getBcvRate(),
            // Otros datos omitidos por brevedad...
        ])->layout('layouts.app');
    }

    // Mantener funciones de planes...
    public function checkInitialPlan() { /* ... */ }
    public function selectPlan($id) { /* ... */ }
    public function openModal() { $this->showPlanModal = true; }
    public function closeModal() { $this->showPlanModal = false; }
}