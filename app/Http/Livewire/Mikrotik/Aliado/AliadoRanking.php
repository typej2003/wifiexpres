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
    
    // Filtros y Navegación
    public $search = '';
    public $activeTab = 'ranking'; // 'ranking' o 'locations'
    public $soloTickets = false;   // Filtro global para ocultar MACs
    public $sortDirection = 'desc';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
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

        if ($this->activeTab === 'ranking') {
            // LÓGICA DE RANKING
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

            if ($this->soloTickets) {
                $query->where('ticket_logs.username', 'NOT REGEXP', '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$');
            }

            $data = $query->groupBy('username', 'comercio_nombre', 'identity')
                ->orderBy('total_conexiones', $this->sortDirection)
                ->paginate(15);
        } else {
            // LÓGICA DE RASTREO POR ANTENA
            $query = TicketLog::with('router')
                ->whereIn('router_id', $routerIds)
                ->where(function($q) {
                    $q->where('username', 'like', '%' . $this->search . '%')
                      ->orWhere('mac_address', 'like', '%' . $this->search . '%');
                });

            if ($this->soloTickets) {
                $query->where('username', 'NOT REGEXP', '^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$');
            }

            $data = $query->latest()->paginate(20);
        }

        return view('livewire.mikrotik.aliado.aliado-ranking', [
            'results' => $data
        ])->layout('layouts.app');
    }
}