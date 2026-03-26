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
        // IMPORTANTE: Enviamos los nuevos datos al frontend inmediatamente
        $data = $this->getChartQueryData();
        $this->emit('updateChart', $data['labels'], $data['data']);
    }

    // Centralizamos el cálculo para que render y setPeriod usen lo mismo
    private function getChartQueryData()
    {
        $user = Auth::user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');

        [$start, $end] = match($this->period) {
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [now()->startOfDay(), now()->endOfDay()],
        };

        $format = ($this->period == 'today') ? '%H:00' : '%d/%m';
        $query = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw("DATE_FORMAT(created_at, '$format') as label"), DB::raw('count(*) as total'))
            ->groupBy('label')
            ->pluck('total', 'label');

        $labels = [];
        $data = [];

        if ($this->period == 'today') {
            for ($i = 0; $i < 24; $i++) {
                $h = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $labels[] = $h;
                $data[] = $query[$h] ?? 0;
            }
        } else {
            $current = $start->copy();
            while ($current <= $end && $current <= now()) {
                $l = $current->format('d/m');
                $labels[] = $l;
                $data[] = $query[$l] ?? 0;
                $current->addDay();
            }
        }

        return ['labels' => $labels, 'data' => $data];
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

        $chart = $this->getChartQueryData();

        return view('livewire.dashboards.aliado-dashboard', [
            'availablePackages' => Package::where('is_active', true)->where('is_visible', true)->get(),
            'activePlans' => $activePlans,
            'pendingPlans' => $user->packages()->wherePivot('status', 'pending')->get(),
            'routers' => $routers,
            'stats' => [
                'total_routers' => $routers->count(),
                'limit_routers' => $activePlans->sum('pivot.allowed_routers'),
                'total_tickets' => Ticket::whereIn('router_id', $routerIds)->count(),
                'tickets_activos' => Ticket::whereIn('router_id', $routerIds)->where('estado', 'activo')->count(),
                'conexiones_periodo' => array_sum($chart['data']),
            ],
            'chartLabels' => $chart['labels'],
            'chartData' => $chart['data'],
            'ultimosLogs' => TicketLog::whereIn('router_id', $routerIds)->latest()->take(6)->get(),
            'dollarRate' => ExchangeRateService::getBcvRate()
        ])->layout('layouts.app');
    }
}