<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\TicketLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShowChart extends Component
{
    public $selectedAliado = '';
    public $selectedRouter = '';
    public $periodo = 'semana';
    public $fecha_desde, $fecha_hasta;
    public $isAdmin = false;

    public function mount()
    {
        $user = Auth::user();
        $this->isAdmin = in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ROOT]);
        
        $this->fecha_desde = now()->subDays(7)->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function updatedPeriodo($value)
    {
        if ($value === 'hoy') {
            $this->fecha_desde = now()->format('Y-m-d');
        } elseif ($value === 'semana') {
            $this->fecha_desde = now()->subDays(7)->format('Y-m-d');
        } elseif ($value === 'mes') {
            $this->fecha_desde = now()->subMonth()->format('Y-m-d');
        }
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function updatedSelectedAliado()
    {
        $this->selectedRouter = '';
    }

    public function render()
    {
        $user = Auth::user();
        
        // 1. Obtener Aliados si es Admin
        $aliados = $this->isAdmin ? User::whereIn('role', [User::ROLE_ALIADO, User::ROLE_ALIADOSMARTDATA])->orderBy('name')->get() : [];

        // 2. Obtener Routers según contexto
        $routers = Router::query()
            ->when(!$this->isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->when($this->isAdmin && $this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
            ->orderBy('identity')
            ->get();

        $routerIds = $routers->pluck('id');
        $desde = Carbon::parse($this->fecha_desde)->startOfDay();
        $hasta = Carbon::parse($this->fecha_hasta)->endOfDay();

        // 3. Labels Temporales (Eje X)
        $labels = [];
        $temp = clone $desde;
        while ($temp <= $hasta) {
            $labels[] = $temp->format('d/m');
            $temp->addDay();
        }

        // 4. Consulta de Conexiones
        $logs = TicketLog::whereIn('ticket_logs.router_id', $routerIds)
            ->whereBetween('ticket_logs.created_at', [$desde, $hasta])
            ->when($this->selectedRouter, fn($q) => $q->where('ticket_logs.router_id', $this->selectedRouter))
            ->select(DB::raw('DATE(ticket_logs.created_at) as fecha'), 'ticket_logs.router_id', DB::raw('count(*) as total'))
            ->groupBy('fecha', 'ticket_logs.router_id')
            ->get();

        // 5. Construcción de Datasets para Conexiones
        $colores = ['#4F46E5', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4'];
        $datasets = [];

        foreach ($routers as $index => $router) {
            if ($this->selectedRouter && $this->selectedRouter != $router->id) continue;

            $dataValues = [];
            $temp = clone $desde;
            while ($temp <= $hasta) {
                $fechaStr = $temp->format('Y-m-d');
                $val = $logs->where('fecha', $fechaStr)->where('router_id', $router->id)->first();
                $dataValues[] = $val ? $val->total : 0;
                $temp->addDay();
            }

            if (array_sum($dataValues) > 0 || $this->selectedRouter) {
                $datasets[] = [
                    'label' => $router->identity,
                    'data' => $dataValues,
                    'backgroundColor' => $colores[$index % count($colores)],
                    'borderRadius' => 5,
                ];
            }
        }

        // 6. Estadísticas de Género (Uso de UserMikrotik)
        $genderData = TicketLog::whereIn('ticket_logs.router_id', $routerIds)
            ->whereBetween('ticket_logs.created_at', [$desde, $hasta])
            ->when($this->selectedRouter, fn($q) => $q->where('ticket_logs.router_id', $this->selectedRouter))
            ->leftJoin('user_mikrotiks', function($join) {
                $join->on('user_mikrotiks.router_id', '=', 'ticket_logs.router_id')
                     ->on('user_mikrotiks.name', '=', DB::raw("REPLACE(ticket_logs.username, 'T-', '')"));
            })
            ->select('user_mikrotiks.gender', DB::raw('count(*) as count'))
            ->groupBy('user_mikrotiks.gender')
            ->get();

        $this->dispatchBrowserEvent('updateCharts', [
            'labels' => $labels,
            'datasets' => $datasets,
            'genderLabels' => $genderData->pluck('gender')->map(fn($g) => $g ?? 'S/R'),
            'genderValues' => $genderData->pluck('count'),
        ]);

        return view('livewire.mikrotik.data.show-chart', [
            'aliados' => $aliados,
            'routers' => $routers,
            'totalConexiones' => $logs->sum('total')
        ])->layout('layouts.app');
    }
}