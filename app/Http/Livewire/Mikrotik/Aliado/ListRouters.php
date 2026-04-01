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
    
    public $routerStatus = [];

    public function render()
    {
        $user = Auth::user();

        // 1. Obtenemos todos los routers del Aliado
        $routers = Router::where("user_id", $user->id)
                    ->with(['package'])
                    ->latest()
                    ->get();

        // 2. Buscamos los planes ACTIVOS adquiridos por el aliado en la tabla pivote
        // Usamos la relación 'packages' definida en tu modelo User (BelongsToMany)
        $packages = $user->packages()
                        ->wherePivot('status', 'activo')
                        ->wherePivot('end_date', '>=', now())
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

        // Si es un router nuevo, validamos el límite de cupos del contrato (tabla pivote)
        if (!$this->router_id) {
            // Buscamos el contrato específico para este plan del aliado
            $planAdquirido = $user->packages()
                                 ->where('package_id', $this->package_id)
                                 ->wherePivot('status', 'activo')
                                 ->first();

            if (!$planAdquirido) {
                session()->flash("error", "No posees un plan activo para este paquete.");
                return;
            }

            $totalActual = Router::where('user_id', $user->id)
                                ->where('package_id', $this->package_id)
                                ->count();

            // Usamos 'allowed_routers' que viene de la tabla pivot 'package_user'
            if ($totalActual >= $planAdquirido->pivot->allowed_routers) {
                session()->flash("error", "Cupos agotados: Has usado {$totalActual} de {$planAdquirido->pivot->allowed_routers} cupos.");
                return;
            }
        }

        Router::updateOrCreate(["id" => $this->router_id], [
            "user_id"            => Auth::id(),
            "package_id"         => $this->package_id,
            "identity"           => $this->identity,
            "macAddress"         => $this->macAddress,
            "location"           => $this->location,
            "comercio_nombre"    => $this->comercio_nombre,
            // Los siguientes campos no los toca el aliado, se mantienen o se usan defaults del Admin
            "status"             => $this->status ?? 'Habilitado',
            "hotspot_version_id" => $this->hotspot_version_id ?? 1,
        ]);

        session()->flash("message", "Operación exitosa.");
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
        $this->openModal();
    }

    public function create() {
        $this->reset(['router_id', 'identity', 'package_id', 'macAddress', 'location', 'comercio_nombre', 'hotspot_url']);
        $this->openModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }
}