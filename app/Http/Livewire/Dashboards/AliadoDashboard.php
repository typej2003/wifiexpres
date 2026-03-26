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

class AliadoDashboard extends Component
{
    public $showPlanModal = false;
    public $period = 'today';

    protected $listeners = ['refreshChart' => '$refresh'];

    public function mount()
    {
        $user = Auth::user();
        $hasPlan = $user->packages()->wherePivotIn('status', ['active', 'pending'])->exists();
        if (!$hasPlan) { $this->showPlanModal = true; }
    }

    public function setPeriod($value) 
    { 
        $this->period = $value; 
        $data = $this->getChartData();
        $this->emit('updateChartData', ['labels' => $data['labels'], 'data' => $data['data']]);
    }

    private function getChartData()
    {
        $user = Auth::user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');

        $start = ($this->period == 'today') ? now()->startOfDay() : ($this->period == 'weekly' ? now()->startOfWeek() : now()->startOfMonth());
        $format = ($this->period == 'today') ? '%H:00' : '%d/%m';

        $query = TicketLog::whereIn('router_id', $routerIds)
            ->where('created_at', '>=', $start)
            ->select(DB::raw("DATE_FORMAT(created_at, '$format') as label"), DB::raw('count(*) as total'))
            ->groupBy('label')
            ->pluck('total', 'label')->toArray();

        $labels = []; $data = [];

        if ($this->period == 'today') {
            for ($i = 0; $i < 24; $i++) {
                $h = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
                $labels[] = $h;
                $data[] = $query[$h] ?? 0;
            }
        } else {
            $labels = array_keys($query);
            $data = array_values($query);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function openModal() { $this->showPlanModal = true; }
    public function closeModal() { $this->showPlanModal = false; }

    public function render()
    {
        $user = Auth::user();
        $routers = Router::where('user_id', $user->id)->get();
        $routerIds = $routers->pluck('id');
        $activePlans = $user->packages()->wherePivot('status', 'active')->wherePivot('end_date', '>=', now())->get();
        $chart = $this->getChartData();

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
            'ultimosLogs' => TicketLog::whereIn('router_id', $routerIds)->with('router')->latest()->take(15)->get(),
            'dollarRate' => ExchangeRateService::getBcvRate()
        ])->layout('layouts.app');
    }
}