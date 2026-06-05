<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;

class QrRouter extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    public $ssid = null;

    public function mount($router_id = null)
    {
        // Si el usuario logueado es un Aliado, fijamos automáticamente su ID
        if (Auth::user()->role !== 'admin') {
            $this->selectedAliado = Auth::id();
        }

        if ($router_id) {
            $this->router_id = $router_id;
            $this->updatedRouterId($router_id);
            
            if (Auth::user()->role === 'admin') {
                $router = Router::find($router_id);
                if ($router) {
                    $this->selectedAliado = $router->user_id;
                }
            }
        }
    }

    public function updatedSelectedAliado()
    {
        $this->router_id = null;
        $this->ssid = null;
    }

    public function updatedRouterId($value)
    {
        if ($value) {
            $router = Router::find($value);
            // Según tu requerimiento, el campo hotspot_url contiene el SSID de la red
            $this->ssid = $router ? $router->hotspot_url : null;
        } else {
            $this->ssid = null;
        }
    }

    public function render()
    {
        $aliados = Auth::user()->role === 'admin' ? User::where('role', 'aliado')->get() : [];
        $routers = $this->selectedAliado ? Router::where('user_id', $this->selectedAliado)->get() : [];

        return view('livewire.mikrotik.herramientas.qr-router', [
            'aliados' => $aliados,
            'routers' => $routers,
        ])->layout('layouts.app');
    }
}
