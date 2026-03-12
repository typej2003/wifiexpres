<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\Router;
use App\Models\TicketLog;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

class AliadoRanking extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'bootstrap';
    public $search = '';
    public $sortDirection = 'desc';
    public $soloTickets = false; // Estado inicial desactivado (mostrar todos)

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function toggleSort()
    {
        $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
    }

    public function render()
    {
        $user = auth()->user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');

        $query = TicketLog::whereIn('router_id', $routerIds)
            ->join('routers', 'ticket_logs.router_id', '=', 'routers.id')
            ->select(
                'ticket_logs.username',
                'routers.comercio_nombre',
                'routers.identity',
                DB::raw('count(*) as total_conexiones'),
                DB::raw('sum(duration_seconds) as tiempo_total'),
                DB::raw('max(ticket_logs.created_at) as ultima_conexion')
            )
            ->where('ticket_logs.username', 'like', '%' . $this->search . '%');

        // Lógica para filtrar MACs si el checkbox está activado
        if ($this->soloTickets) {
            // Excluye registros que parezcan una dirección MAC (separada por : o -)
            $query->where('ticket_logs.username', 'NOT REGEXP', '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$');
        }

        $rankings = $query->groupBy('username', 'comercio_nombre', 'identity')
            ->orderBy('total_conexiones', $this->sortDirection)
            ->paginate(15);

        return view('livewire.mikrotik.aliado.aliado-ranking', [
            'rankings' => $rankings
        ])->layout('layouts.app');
    }
}