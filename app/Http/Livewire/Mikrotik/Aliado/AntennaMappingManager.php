<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\Router;
use App\Models\AntennaMapping;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AntennaMappingManager extends Component
{
    public $aliadoId;
    public $router_id;
    public $ip_address;
    public $location_name;
    public $hotspot_url; // Nuevo campo
    public $description;
    public $mapping_id; // Para edición

    public $isEditing = false;
    public $aliados = [];
    // Initialize routers as a collection to prevent errors with pluck if it's empty
    public $routers = [];

    protected $rules = [
        'router_id' => 'required',
        'ip_address' => 'required|ip',
        'location_name' => 'required|min:3',
        'hotspot_url' => 'nullable|url', // Nueva regla de validación
    ];

    public function mount($router_id = 0)
    {
        // Inicializamos routers como una colección vacía para evitar errores de pluck
        $this->routers = collect();

        // Si es admin ve todos, si es aliado solo se ve a sí mismo
        if (Auth::user()->role === 'admin') {
            $this->aliados = User::where('role', 'aliado')->orwhere('role', 'aliado')->orderBy('name')->get();

            // Si se pasa un router_id específico, cargamos el contexto de ese equipo
            if ($router_id != 0) {
                $router = Router::find($router_id);
                if ($router) {
                    $this->aliadoId = $router->user_id;
                    $this->updatedAliadoId($this->aliadoId);
                    $this->router_id = $router_id;
                }
            }
        } else {
            $this->aliadoId = Auth::id();
            $this->updatedAliadoId($this->aliadoId);
            if ($router_id != 0) {
                $this->router_id = $router_id;
            }
        }
    }

    public function updatedAliadoId($value)
    {
        if ($value) {
            $this->routers = Router::where('user_id', $value)->get();
        } else {
            $this->routers = collect();
        }
        $this->router_id = null;
    }

    public function save()
    {
        $this->validate();

        AntennaMapping::updateOrCreate(
            ['id' => $this->mapping_id],
            [
                'router_id' => $this->router_id,
                'ip_address' => $this->ip_address,
                'location_name' => $this->location_name,
                'description' => $this->description,
                'hotspot_url' => $this->hotspot_url, // Guardar el nuevo campo
            ]
        );

        $this->resetInput();
        session()->flash('message', $this->mapping_id ? 'Mapeo actualizado.' : 'Antena registrada.');
    }

    public function edit($id)
    {
        $mapping = AntennaMapping::findOrFail($id);
        $this->mapping_id = $id;
        $this->router_id = $mapping->router_id;
        $this->ip_address = $mapping->ip_address;
        $this->location_name = $mapping->location_name;
        $this->hotspot_url = $mapping->hotspot_url; // Cargar el nuevo campo
        $this->description = $mapping->description;
        $this->isEditing = true;

        // Aseguramos que los routers del dueño del mapeo estén cargados
        $router = Router::find($this->router_id);
        if ($router) {
            $this->aliadoId = $router->user_id;
            $this->routers = Router::where('user_id', $this->aliadoId)->get();
        }
    }

    public function delete($id)
    {
        AntennaMapping::find($id)->delete();
        session()->flash('message', 'Mapeo eliminado.');
    }

    public function resetInput()
    {
        $this->reset(['ip_address', 'location_name', 'hotspot_url', 'description', 'mapping_id', 'isEditing']);
    }

    public function render()
    {
        // Convertimos $this->routers a colección por si Livewire lo devolvió como array
        $routerIds = collect($this->routers)->pluck('id')->toArray();

        $mappings = AntennaMapping::whereIn('router_id', $routerIds)
                    ->with('router')
                    ->orderBy('created_at', 'desc')
                    ->get();

        return view('livewire.mikrotik.aliado.antenna-mapping-manager', [
            'mappings' => $mappings
        ]);
    }
}