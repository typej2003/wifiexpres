<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TicketLog;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class UserHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedRouter = '';
    public $fromDate;
    public $toDate;
    protected $paginationTheme = 'bootstrap';

    public function mount($username = null)
    {
        if ($username) {
            $this->search = $username;
        }

        $this->fromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->toDate = Carbon::now()->format('Y-m-d');
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingSelectedRouter() { $this->resetPage(); }
    public function updatingFromDate() { $this->resetPage(); }
    public function updatingToDate() { $this->resetPage(); }

    // Genera la descarga del PDF mediante una redirección a una ruta de controlador
    public function exportPDF()
    {
        $params = [
            'search' => $this->search,
            'router' => $this->selectedRouter,
            'from' => $this->fromDate,
            'to' => $this->toDate,
        ];

        return redirect()->route('admin.history.pdf', $params);
    }

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
            ->when($this->fromDate, function($q) {
                $q->whereDate('created_at', '>=', $this->fromDate);
            })
            ->when($this->toDate, function($q) {
                $q->whereDate('created_at', '<=', $this->toDate);
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

        return view('livewire.mikrotik.data.user-history', [
            'logs' => $query->paginate(20),
            'misRouters' => Router::where('user_id', $user->id)->get(),
            'userStats' => $userStats
        ])->layout('layouts.app');
    }
}