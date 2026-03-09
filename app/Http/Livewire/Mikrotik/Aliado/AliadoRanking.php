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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');

        $rankings = TicketLog::whereIn('router_id', $routerIds)
            ->join('routers', 'ticket_logs.router_id', '=', 'routers.id')
            ->select(
                'ticket_logs.username',
                'routers.comercio_nombre',
                'routers.identity',
                DB::raw('count(*) as total_conexiones'),
                DB::raw('sum(duration_seconds) as tiempo_total'),
                DB::raw('max(ticket_logs.created_at) as ultima_conexion')
            )
            ->where('ticket_logs.username', 'like', '%' . $this->search . '%')
            ->groupBy('username', 'comercio_nombre', 'identity')
            ->orderBy('total_conexiones', 'desc')
            ->paginate(15);

        return view('livewire.mikrotik.aliado.aliado-ranking', [
            'rankings' => $rankings
        ])->layout('layouts.app');
    }
}