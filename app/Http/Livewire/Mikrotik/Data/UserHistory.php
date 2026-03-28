<?php

namespace App\Http\Livewire\Mikrotik;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;

class DataUserHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedRouter = '';
    protected $paginationTheme = 'bootstrap';

    // El mount recibe el username opcional desde la URL
    public function mount($username = null)
    {
        if ($username) {
            $this->search = $username;
        }
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingSelectedRouter() { $this->resetPage(); }

    public function render()
    {
        $user = Auth::user();
        $routerIds = Router::where('user_id', $user->id)->pluck('id');

        $query = TicketLog::whereIn('router_id', $routerIds)
            ->with('router')
            ->when($this->search, function($q) {
                $q->where(function($sub) {
                    $sub->where('username', 'like', '%' . $this->search . '%')
                        ->orWhere('mac_address', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->selectedRouter, function($q) {
                $q->where('router_id', $this->selectedRouter);
            })
            ->latest();

        $userStats = null;
        if (!empty($this->search)) {
            $userStats = [
                'total_conexiones' => (clone $query)->count(),
                'tiempo_total' => (clone $query)->sum('duration_seconds'),
                'nodos_visitados' => (clone $query)->distinct('router_id')->count('router_id')
            ];
        }

        return view('livewire.mikrotik.data-user-history', [
            'logs' => $query->paginate(20),
            'misRouters' => Router::where('user_id', $user->id)->get(),
            'userStats' => $userStats
        ])->layout('layouts.app');
    }
}