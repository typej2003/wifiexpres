<?php

namespace App\Http\Livewire\Dashboards;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\TicketLog;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboard extends Component
{
    public $period = 'today';

    public function setPeriod($value)
    {
        $this->period = $value;
        $this->emit('updateChart', $this->getChartData());
    }

    private function getQueryRange()
    {
        return match($this->period) {
            'today' => [now()->startOfDay(), now()],
            'weekly' => [now()->startOfWeek(), now()],
            '15days' => [now()->subDays(15), now()],
            'month' => [now()->startOfMonth(), now()],
            default => [now()->startOfDay(), now()],
        };
    }

    private function getChartData()
    {
        [$start, $end] = $this->getQueryRange();
        $format = ($this->period == 'today') ? '%H:00' : '%d/%m';
        
        $query = TicketLog::whereBetween('created_at', [$start, $end])
            ->select(DB::raw("DATE_FORMAT(created_at, '$format') as label"), DB::raw('count(*) as total'))
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return [
            'labels' => $query->pluck('label'),
            'values' => $query->pluck('total'),
        ];
    }

    public function render()
    {
        [$start, $end] = $this->getQueryRange();

        $stats = [
            'total_aliados'    => User::where('role', 'aliado')->count(),
            'total_routers'    => Router::count(),
            'routers_activos'  => Router::where('is_active', true)->count(),
            'conexiones_periodo' => TicketLog::whereBetween('created_at', [$start, $end])->count(),
        ];

        $actividadJerarquica = TicketLog::whereBetween('ticket_logs.created_at', [$start, $end])
            ->join('routers', 'ticket_logs.router_id', '=', 'routers.id')
            ->join('users', 'routers.user_id', '=', 'users.id')
            ->select(
                'users.name as aliado',
                'routers.identity as mikrotik',
                'routers.comercio_nombre',
                DB::raw('count(ticket_logs.id) as total_tickets')
            )
            ->groupBy('aliado', 'mikrotik', 'comercio_nombre')
            ->orderBy('total_tickets', 'desc')
            ->get();

        $chart = $this->getChartData();

        return view('livewire.dashboards.admin-dashboard', [
            'stats' => $stats,
            'actividadJerarquica' => $actividadJerarquica,
            'chartLabels' => $chart['labels'],
            'chartData' => $chart['values'],
            'recientes' => User::where('role', 'aliado')->withCount('routers')->latest()->take(5)->get(),
            'ultimosLogs' => TicketLog::with('router')->latest()->take(10)->get(), // <--- Cambio aquí: Relación cargada
            'dollarRate' => ExchangeRateService::getBcvRate()
        ])->layout('layouts.app');
    }
}