<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\AntennaMapping;
use Illuminate\Support\Facades\Auth;

class QrRouter extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    public $ssid = null;
    public $antenna_id = null; // Nuevo: ID de la antena seleccionada
    public $antennas = [];     // Nuevo: Lista de antenas del router
    public $selected_ip = null; // Nuevo: IP que se muestra en la vista
    public $comercio_nombre = null;

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

    public function back()
    {
        return redirect()->route('aliado.routers');
    }

    public function downloadQr()
    {
        if (!$this->ssid) return;

        // Generamos el QR en formato PNG (máxima calidad para la conversión)
        $pngData = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
            ->size(1000)
            ->margin(2)
            ->generate("WIFI:S:{$this->ssid};;");

        // Convertimos a JPG mediante GD para cumplir el requerimiento
        $image = imagecreatefromstring($pngData);
        
        return response()->streamDownload(function () use ($image) {
            imagejpeg($image, null, 90); // Calidad 90%
            imagedestroy($image);
        }, 'QR_' . str_replace(' ', '_', $this->comercio_nombre ?? $this->ssid) . '.jpg', [
            'Content-Type' => 'image/jpeg',
        ]);
    }

    public function updatedSelectedAliado()
    {
        $this->router_id = null;
        $this->reset(['ssid', 'comercio_nombre', 'antennas', 'antenna_id', 'selected_ip']);
    }

    public function updatedRouterId($value)
    {
        $this->reset(['ssid', 'comercio_nombre', 'antennas', 'antenna_id', 'selected_ip']);

        if ($value) {
            $router = Router::find($value);
            if ($router) {
                $this->ssid = $router->hotspot_url;
                $this->comercio_nombre = $router->comercio_nombre;
                $this->selected_ip = $router->ip;
                // Cargamos las antenas asociadas
                $this->antennas = AntennaMapping::where('router_id', $value)->get();
            }
        }
    }

    public function updatedAntennaId($value)
    {
        $router = Router::find($this->router_id);
        
        if ($value === 'main' || !$value) {
            $this->ssid = $router->hotspot_url;
            $this->selected_ip = $router->ip;
        } else {
            $antenna = AntennaMapping::find($value);
            if ($antenna) {
                $this->ssid = $antenna->hotspot_url;
                $this->selected_ip = $antenna->ip_address;
            }
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
