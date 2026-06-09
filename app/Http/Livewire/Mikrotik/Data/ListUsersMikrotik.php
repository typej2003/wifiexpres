<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserMikrotik;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ListUsersMikrotik extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedAliado = '';
    public $selectedRouter = '';
    public $isAdmin = false;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $user = Auth::user();
        // Verificamos si es admin o root para habilitar filtros globales
        $this->isAdmin = in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ROOT]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedAliado()
    {
        $this->selectedRouter = '';
        $this->resetPage();
    }

    public function updatingSelectedRouter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = Auth::user();

        // 1. Query principal con relaciones
        $query = UserMikrotik::query()->with(['router.user']);

        // 2. Control de visibilidad por Rol
        if ($this->isAdmin) {
            if ($this->selectedAliado) {
                $query->whereHas('router', function ($q) {
                    $q->where('user_id', $this->selectedAliado);
                });
            }
        } else {
            // Los aliados solo ven usuarios de sus propios routers
            $query->whereHas('router', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // 3. Filtros de búsqueda y router
        if ($this->selectedRouter) {
            $query->where('router_id', $this->selectedRouter);
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('full_name', 'like', '%' . $this->search . '%')
                  ->orWhere('cellphone', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        // 4. Obtener datos para los selects de la vista
        $aliados = $this->isAdmin ? User::whereIn('role', [User::ROLE_ALIADO, User::ROLE_ALIADOSMARTDATA])->orderBy('name')->get() : [];
        
        $routers = Router::when(!$this->isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->when($this->isAdmin && $this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
            ->orderBy('identity')
            ->get();

        return view('livewire.mikrotik.data.list-users-mikrotik', [
            'users' => $query->latest()->paginate(15),
            'aliados' => $aliados,
            'routers' => $routers,
        ])->layout('layouts.app');
    }
}
