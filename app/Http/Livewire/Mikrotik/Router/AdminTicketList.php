<?php

namespace App\Http\Livewire\Mikrotik\Router;

use App\Http\Livewire\Mikrotik\Aliado\ListTicketsAliado;
use Illuminate\Support\Facades\Auth;

class AdminTicketList extends ListTicketsAliado
{
    public function mount($id = null)
    {
        if ($id) {
            $this->selectedRouter = $id;
            $this->loadRouterData();
            $this->loadMikrotikProfiles();
        }
    }

    // Botón de retroceso al panel administrativo
    public function backToRouters()
    {
        return redirect()->route('admin.routers.index');
    }

    public function render()
    {
        // Forzamos que el admin vea todo sin restricciones de user_id
        return parent::render()->layout('layouts.app');
    }
}