<?php

namespace App\Http\Livewire\Mikrotik\Ticket;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Router;
use Illuminate\Support\Facades\Storage;

class ConfigTicket extends Component
{
    use WithFileUploads;

    public $router_id;
    public $comercio_nombre, $hotspot_url, $logo_actual, $nuevo_logo;

    public function mount($router_id)
    {
        $this->router_id = $router_id;
        $router = Router::findOrFail($router_id);
        $this->comercio_nombre = $router->comercio_nombre;
        $this->hotspot_url = $router->hotspot_url;
        $this->logo_actual = $router->comercio_logo;
    }

    public function save()
    {
        $this->validate([
            'comercio_nombre' => 'required|string|max:50',
            'hotspot_url' => 'nullable|string',
            'nuevo_logo' => 'nullable|image|max:1024', // Max 1MB
        ]);

        $router = Router::find($this->router_id);
        
        $data = [
            'comercio_nombre' => $this->comercio_nombre,
            'hotspot_url' => $this->hotspot_url,
        ];

        if ($this->nuevo_logo) {
            // Borrar logo anterior si existe
            if ($router->comercio_logo) {
                Storage::disk('public')->delete($router->comercio_logo);
            }
            $path = $this->nuevo_logo->store('logos', 'public');
            $data['comercio_logo'] = $path;
            $this->logo_actual = $path;
        }

        $router->update($data);
        session()->flash('message', 'Configuración guardada correctamente.');
    }

    public function render()
    {
        return view('livewire.mikrotik.ticket.config-ticket');
    }
}