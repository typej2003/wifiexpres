<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdvertisingCampaign;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ListAdvertisingCampaign extends Component
{
    use WithPagination;

    public $search = '';
    public $filterAliado = ''; // Solo para Admin
    public $isAdmin = false;

    public function mount()
    {
        $this->isAdmin = Auth::user()->role === 'admin';
    }

    public function render()
    {
        $query = AdvertisingCampaign::query()->with('user');

        // Lógica de Seguridad y Roles
        if (!$this->isAdmin) {
            // Si es aliado, solo ve sus propias campañas
            $query->where('user_id', Auth::id());
        } else {
            // Si es admin, puede filtrar por aliado
            if ($this->filterAliado) {
                $query->where('user_id', $this->filterAliado);
            }
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return view('livewire.mikrotik.aliado.list-advertising-campaign', [
            'campaigns' => $query->latest()->paginate(10),
            'aliados' => $this->isAdmin ? User::where('role', 'aliado')->get() : []
        ])->layout('layouts.app');
    }
}