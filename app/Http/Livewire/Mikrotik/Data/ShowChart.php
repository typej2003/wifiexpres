<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;

class ShowChart extends Component
{
    public $selectedAliado = '';
    public $selectedRouter = '';
    public $periodo = 'hoy';
    public $fecha_desde, $fecha_hasta;
    public $isAdmin = false;

    public function mount()
    {
        $user = Auth::user();
        $this->isAdmin = in_array($user->role, [User::ROLE_ADMIN, User::ROLE_ROOT]);
        $this->fecha_desde = now()->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function render()
    {
        $user = Auth::user();

        // Obtener lista de aliados si es admin
        $aliados = $this->isAdmin 
            ? User::whereIn('role', [User::ROLE_ALIADO, User::ROLE_ALIADOSMARTDATA])->orderBy('name')->get() 
            : [];

        // Obtener routers filtrados por el rol o aliado seleccionado
        $routers = Router::query()
            ->when(!$this->isAdmin, fn($q) => $q->where('user_id', $user->id))
            ->when($this->isAdmin && $this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
            ->orderBy('identity')
            ->get();

        return view('livewire.mikrotik.data.show-chart', [
            'aliados' => $aliados,
            'routers' => $routers,
            'totalConexiones' => 0, // Aquí deberás sumar la lógica de conteo de logs después
        ]);
    }
}
