<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\UserMikrotik;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\UsersMikrotikExport;
use Carbon\Carbon;

class ListUsersMikrotik extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedAliado = '';
    public $selectedRouter = '';
    public $isAdmin = false;
    public $periodo = 'hoy';
    public $fecha_desde;
    public $fecha_hasta;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $user = Auth::user();
        // Verificamos si es admin o root para habilitar filtros globales
        $this->isAdmin = in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ROOT]);
        $this->fecha_desde = now()->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
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

    public function updatedPeriodo($value)
    {
        if ($value === 'hoy') {
            $this->fecha_desde = now()->format('Y-m-d');
            $this->fecha_hasta = now()->format('Y-m-d');
        } elseif ($value === 'semana') {
            $this->fecha_desde = now()->startOfWeek()->format('Y-m-d');
            $this->fecha_hasta = now()->format('Y-m-d');
        } elseif ($value === 'mes') {
            $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
            $this->fecha_hasta = now()->format('Y-m-d');
        } elseif ($value === 'ultimos_50') {
            $this->fecha_desde = null;
            $this->fecha_hasta = null;
        }
        $this->resetPage();
    }

    public function exportExcel()
    {
        return Excel::download(new UsersMikrotikExport(
            $this->search,
            $this->selectedAliado,
            $this->selectedRouter,
            $this->isAdmin,
            $this->periodo,
            $this->fecha_desde,
            $this->fecha_hasta
        ), 'usuarios_hotspot_' . now()->format('Y-m-d') . '.xlsx');
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

        // 3. Filtros adicionales de Router, Periodo y Búsqueda
        if ($this->selectedRouter) {
            $query->where('router_id', $this->selectedRouter);
        }

        if ($this->periodo === 'ultimos_50') {
            $query->limit(50);
        } else {
            if ($this->fecha_desde) {
                $query->whereDate('created_at', '>=', $this->fecha_desde);
            }
            if ($this->fecha_hasta) {
                $query->whereDate('created_at', '<=', $this->fecha_hasta);
            }
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
