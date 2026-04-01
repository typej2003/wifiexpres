<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraficoConexiones extends Component
{
    public $days = 7; // Mostramos una semana para no saturar visualmente
    public $router_id = ''; 

    public function render()
    {
        $user = Auth::user();
        $misRouters = Router::where('user_id', $user->id)->get();
        $routerIds = $misRouters->pluck('id');

        // 1. Obtener todas las fechas del rango para el eje X
        $fechas = [];
        for ($i = $this->days - 1; $i >= 0; $i--) {
            $fechas[] = now()->subDays($i)->format('Y-m-d');
        }

        // 2. Obtener logs agrupados por día y router
        $logs = TicketLog::whereIn('router_id', $routerIds)
            ->where('created_at', '>=', now()->subDays($this->days))
            ->select(
                DB::raw('DATE(created_at) as fecha'),
                'router_id',
                DB::raw('count(*) as total')
            )
            ->groupBy('fecha', 'router_id')
            ->get();

        // 3. Preparar Datasets (Una serie de datos por cada Router)
        // Colores predefinidos para diferenciar routers
        $colores = ['#0d6efd', '#198754', '#ffc107', '#0dcaf0', '#6610f2', '#fd7e14', '#20c997', '#dc3545'];
        $datasets = [];

        foreach ($misRouters as $index => $router) {
            // Si hay un filtro de router activo, saltamos los demás
            if (!empty($this->router_id) && $this->router_id != $router->id) continue;

            $dataValues = [];
            foreach ($fechas as $fecha) {
                // Buscamos si este router tuvo conexiones en esta fecha
                $log = $logs->where('fecha', $fecha)->where('router_id', $router->id)->first();
                $dataValues[] = $log ? $log->total : 0;
            }

            $datasets[] = [
                'label' => $router->identity,
                'data' => $dataValues,
                'backgroundColor' => $colores[$index % count($colores)],
                'borderRadius' => 5,
            ];
        }

        // Notificar a la vista
        $this->dispatchBrowserEvent('updateMultiChart', [
            'labels' => $fechas,
            'datasets' => $datasets,
        ]);

        return view('livewire.mikrotik.data.grafico-conexiones', [
            'routers' => $misRouters,
            'labels' => $fechas,
            'datasets' => $datasets,
            'totalGeneral' => $logs->sum('total')
        ])->layout('layouts.app');
    }
}