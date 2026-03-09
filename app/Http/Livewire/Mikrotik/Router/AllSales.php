<?php

namespace App\Http\Livewire\Mikrotik\Router;

use Livewire\Component;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;

class AllSales extends Component
{
    public $period = 'today';
    public $fromDate;
    public $toDate;
    public $selectedAliado = ''; // Filtro para administrador

    public function mount()
    {
        $this->fromDate = now()->format('Y-m-d');
        $this->toDate = now()->format('Y-m-d');
    }

    public function setPeriod($val) 
    { 
        $this->period = $val; 
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
        $start = Carbon::parse($this->fromDate)->startOfDay();
        $end = Carbon::parse($this->toDate)->endOfDay();

        // Query base para el administrador (ve todo)
        $query = Sale::whereBetween('created_at', [$start, $end]);

        // Filtro opcional por Aliado
        if ($this->selectedAliado != '') {
            $query->where('user_id', $this->selectedAliado);
        }

        $sales = (clone $query)->with(['router', 'user'])->latest()->get();

        $stats = [
            'total_usd'       => (clone $query)->sum('amount_usd'),
            'total_bs'        => (clone $query)->sum('amount_bs'),
            'conteo'          => (clone $query)->count(),
            'aliados_activos' => (clone $query)->distinct('user_id')->count('user_id'),
        ];

        // Obtener lista de usuarios que tienen ventas para el select
        $aliados = User::whereHas('sales')->get();

        return view('livewire.mikrotik.router.all-sales', [
            'sales'   => $sales,
            'stats'   => $stats,
            'aliados' => $aliados
        ])->layout('layouts.app');
    }
}