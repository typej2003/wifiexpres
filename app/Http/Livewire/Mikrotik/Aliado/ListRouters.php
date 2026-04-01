<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\Router;
use App\Models\Package;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ListRouters extends Component
{
    public $isModalOpen = false;
    public $router_id, $identity, $macAddress, $location, $comercio_nombre;
    public $hotspot_url, $package_id;
    
    // Propiedades heredadas para mantener la integridad del registro
    public $status, $hotspot_version_id;
    
    public $routerStatus = [];

    public function render()
    {
        $user = Auth::user();
        
        $routers = Router::where("user_id", $user->id)
                    ->with(['package'])
                    ->latest()
                    ->get();

        // IMPORTANTE: Ajustado a 'active' según el estándar de tu modelo User
        $packages = $user->packages()
                        ->wherePivot('status', 'active')
                        ->get();

        $this->refreshStatus();

        return view("livewire.mikrotik.aliado.list-routers", [
            "routers" => $routers,
            "packages" => $packages
        ]);
    }

    public function refreshStatus()
    {
        try {
            $response = Http::timeout(2)->get('http://188.95.113.44:3000/api/routers-online');
            $activeMacs = $response->successful() ? collect($response->json())->pluck('mac')->toArray() : [];

            $routers = Router::where("user_id", Auth::id())->get();
            foreach ($routers as $r) {
                $this->routerStatus[$r->id] = in_array($r->macAddress, $activeMacs);
            }
        } catch (\Exception $e) {}
    }

    public function store()
    {
        $this->validate([
            "identity" => "required",
            "macAddress" => "required",
            "comercio_nombre" => "required",
            "package_id" => "required",
        ]);

        $user = Auth::user();

        if (!$this->router_id) {
            $planContratado = $user->packages()
                                  ->where('package_id', $this->package_id)
                                  ->wherePivot('status', 'active')
                                  ->first();
            
            if (!$planContratado) {
                session()->flash("error", "No posees este plan activo en tu suscripción.");
                return;
            }

            $totalActual = Router::where('user_id', $user->id)
                                ->where('package_id', $this->package_id)
                                ->count();

            if ($totalActual >= $planContratado->pivot->allowed_routers) {
                session()->flash("error", "Cupos agotados. Límite: {$planContratado->pivot->allowed_routers} equipos.");
                return;
            }
        }

        Router::updateOrCreate(["id" => $this->router_id], [
            "user_id"            => $user->id,
            "package_id"         => $this->package_id,
            "identity"           => $this->identity,
            "macAddress"         => $this->macAddress,
            "location"           => $this->location,
            "comercio_nombre"    => $this->comercio_nombre,
            "status"             => $this->status ?? 'Habilitado',
            "hotspot_version_id" => $this->hotspot_version_id ?? 1,
        ]);

        session()->flash("message", "Operación realizada con éxito.");
        $this->closeModal();
    }

    public function edit(Router $router)
    {
        $this->router_id = $router->id;
        $this->identity = $router->identity;
        $this->macAddress = $router->macAddress;
        $this->location = $router->location;
        $this->package_id = $router->package_id;
        $this->comercio_nombre = $router->comercio_nombre;
        $this->hotspot_url = $router->hotspot_url;
        $this->status = $router->status;
        $this->hotspot_version_id = $router->hotspot_version_id;
        $this->openModal();
    }

    public function create() {
        $this->reset(['router_id', 'identity', 'package_id', 'macAddress', 'location', 'comercio_nombre', 'hotspot_url']);
        $this->status = 'Habilitado';
        $this->openModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }
}