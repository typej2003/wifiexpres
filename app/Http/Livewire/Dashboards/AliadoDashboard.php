<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraficoRouters extends Component
{
    public function render()
    {
        $user = Auth::user();

        // 1. Obtenemos los routers del usuario
        $misRouters = Router::where('user_id', $user->id)->get();
        $routerIds = $misRouters->pluck('id');

        // 2. Agrupamos los logs por router para contar conexiones totales
        $data = TicketLog::whereIn('router_id', $routerIds)
            ->select('router_id', DB::raw('count(*) as total'))
            ->groupBy('router_id')
            ->with('router:id,identity')
            ->get();

        // 3. Preparamos las etiquetas (labels) y los valores (data)
        $labels = [];
        $values = [];

        foreach ($data as $item) {
            $labels[] = $item->router->identity ?? 'Router #' . $item->router_id;
            $values[] = $item->total;
        }

        return view('livewire.mikrotik.data.grafico-routers', [
            'labels' => $labels,
            'values' => $values,
            'totalGeneral' => array_sum($values)
        ])->layout('layouts.app');
    }
}