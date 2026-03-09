<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SalesReports extends Component
{
    public $period = 'today';
    public $fromDate;
    public $toDate;

    public function mount()
    {
        // Inicializamos las fechas con el día de hoy por defecto
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function setPeriod($val) 
    { 
        $this->period = $val; 
        
        // Al elegir un periodo rápido, reseteamos las fechas visuales para evitar confusión
        [$start, $end] = match($val) {
            'today'  => [now()->startOfDay(), now()],
            'weekly' => [now()->startOfWeek(), now()],
            'month'  => [now()->startOfMonth(), now()],
            default  => [now()->startOfDay(), now()],
        };

        $this->fromDate = $start->format('Y-m-d');
        $this->toDate = $end->format('Y-m-d');
    }

    public function render()
    {
        // Si el periodo es 'custom' o si se han modificado las fechas manualmente
        $start = Carbon::parse($this->fromDate)->startOfDay();
        $end = Carbon::parse($this->toDate)->endOfDay();

        // Query base: Ventas del aliado dentro del rango seleccionado
        $query = Sale::where('user_id', Auth::id())
                     ->whereBetween('created_at', [$start, $end]);

        // Cargamos la relación router
        $sales = (clone $query)->with('router')->latest()->get();

        $stats = [
            'total_usd'    => (clone $query)->sum('amount_usd'),
            'total_bs'     => (clone $query)->sum('amount_bs'),
            'por_ticket'   => (clone $query)->where('type', 'ticket_fisico')->sum('amount_usd'),
            'por_pasarela' => (clone $query)->where('type', 'pasarela')->sum('amount_bs'),
            'conteo'       => (clone $query)->count(),
        ];

        return view('livewire.mikrotik.aliado.sales-reports', [
            'sales' => $sales,
            'stats' => $stats
        ])->layout('layouts.app');
    }
}