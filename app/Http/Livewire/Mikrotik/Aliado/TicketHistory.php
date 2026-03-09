<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Router;
use App\Models\TicketLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class TicketHistory extends Component
{
    use WithPagination;

    public $router;
    public $filter = 'today'; 
    protected $paginationTheme = 'bootstrap';

    public function mount(Router $router)
    {
        
        // Lógica: Permitir si es Admin O si es el dueño del router
        if (auth()->user()->role !== 'admin' && $router->user_id !== auth()->id()) {
            abort(403, 'No tienes permiso para ver este historial.');
        }
        $this->router = $router;
    }

    public function updatingFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = TicketLog::where('router_id', $this->router->id);

        // Filtros de tiempo
        switch ($this->filter) {
            case 'today':
                $query->whereDate('created_at', Carbon::today());
                break;
            case 'weekly':
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                break;
            case 'monthly':
                $query->whereMonth('created_at', Carbon::now()->month)
                      ->whereYear('created_at', Carbon::now()->year);
                break;
        }

        $logs = $query->latest()->paginate(15);

        // Estadísticas rápidas con query builder para eficiencia
        $stats = [
            'total' => TicketLog::where('router_id', $this->router->id)->count(),
            'today' => TicketLog::where('router_id', $this->router->id)->whereDate('created_at', Carbon::today())->count(),
            'unique' => TicketLog::where('router_id', $this->router->id)->distinct('mac_address')->count('mac_address'),
        ];

        return view('livewire.mikrotik.aliado.ticket-history', [
            'logs' => $logs,
            'stats' => $stats
        ])->layout('layouts.app'); // Asegura que use el layout de aliado
    }
}