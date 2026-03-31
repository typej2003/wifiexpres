<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GraficoConexiones extends Component
{
    public $router_id = ''; // Filtro seleccionado
    public $days = 10;      // Rango de días a mostrar

    public function render()
    {
        $user = Auth::user();
        
        // 1. Routers disponibles para el selector
        $misRouters = Router::where('user_id', $user->id)->get();

        // 2. Consulta de logs
        $query = TicketLog::whereIn('router_id', $misRouters->pluck('id'))
            ->select(
                DB::raw('DATE(created_at) as fecha'),
                DB::raw('count(*) as total')
            )
            ->where('created_at', '>=', now()->subDays($this->days))
            ->groupBy('fecha')
            ->orderBy('fecha', 'ASC');

        // Filtrar por router específico si se selecciona uno
        if (!empty($this->router_id)) {
            $query->where('router_id', $this->router_id);
        }

        $resultados = $query->get();

        // 3. Formatear datos para el gráfico
        $labels = $resultados->pluck('fecha')->toArray();
        $values = $resultados->pluck('total')->toArray();

        // Emitir evento para que JS actualice el gráfico
        $this->dispatchBrowserEvent('updateChart', [
            'labels' => $labels,
            'values' => $values,
        ]);

        return view('livewire.mikrotik.data.grafico-conexiones', [
            'routers' => $misRouters,
            'labels' => $labels,
            'values' => $values,
            'maxConexiones' => !empty($values) ? max($values) : 0
        ])->layout('layouts.app');
    }
}