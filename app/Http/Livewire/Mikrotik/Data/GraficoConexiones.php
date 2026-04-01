<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GraficoConexiones extends Component
{
    public $router_id = ''; // Vacío significa "Todos"
    public $periodo = 'semana'; 
    public $fecha_desde, $fecha_hasta;

    public function mount() {
        // Al iniciar, mostramos por defecto la última semana
        $this->fecha_desde = now()->subDays(7)->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function updatedPeriodo($value) {
        if ($value === 'dia') {
            $this->fecha_desde = now()->format('Y-m-d');
        } elseif ($value === 'semana') {
            $this->fecha_desde = now()->subDays(7)->format('Y-m-d');
        } elseif ($value === 'mes') {
            $this->fecha_desde = now()->subMonth()->format('Y-m-d');
        }
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function render() {
        $user = Auth::user();
        
        // Obtenemos todos los routers que pertenecen al aliado logueado
        $misRouters = Router::where('user_id', $user->id)->get();
        $routerIds = $misRouters->pluck('id');

        $desde = Carbon::parse($this->fecha_desde)->startOfDay();
        $hasta = Carbon::parse($this->fecha_hasta)->endOfDay();

        // 1. Generar etiquetas del eje X (Fechas)
        $labels = [];
        $temp = clone $desde;
        while ($temp <= $hasta) {
            $labels[] = $temp->format('Y-m-d');
            $temp->addDay();
        }

        // 2. Consulta de logs agrupada por fecha y router
        $logs = TicketLog::whereIn('router_id', $routerIds)
            ->whereBetween('created_at', [$desde, $hasta])
            ->select(DB::raw('DATE(created_at) as fecha'), 'router_id', DB::raw('count(*) as total'))
            ->groupBy('fecha', 'router_id')
            ->get();

        // 3. Construir los Datasets
        $colores = ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14', '#dc3545', '#20c997'];
        $datasets = [];

        foreach ($misRouters as $index => $router) {
            // Si el usuario filtró por un router específico, ignoramos los demás
            if (!empty($this->router_id) && $this->router_id != $router->id) {
                continue;
            }

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

        // Emitir evento para refrescar el gráfico en el navegador
        $this->dispatchBrowserEvent('updateMultiChart', [
            'labels' => $labels,
            'datasets' => $datasets,
        ]);

        return view('livewire.mikrotik.data.grafico-conexiones', [
            'routers' => $misRouters,
            'totalPeriodo' => $logs->sum('total'),
            'labels' => $labels,
            'datasets' => $datasets
        ])->layout('layouts.app');
    }
}