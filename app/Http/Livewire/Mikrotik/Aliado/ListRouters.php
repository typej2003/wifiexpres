<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\Router;
use App\Models\Package;
use App\Models\HotspotVersion;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ListRouters extends Component
{
    public $isModalOpen = false;
    public $router_id, $identity, $macAddress, $location;
    public $comercio_nombre, $hotspot_url, $hotspot_version_id, $status = 'Habilitado', $package_id;
    
    public $routerStatus = [];
    public $connectionMode;

    public function mount()
    {
        $setting = Setting::where('user_id', Auth::id())->first();
        $this->connectionMode = $setting ? (int)$setting->mikrotik_connection_mode : 0;
    }

    public function render()
    {
        $aliado = Auth::user();
        $routers = Router::where("user_id", $aliado->id)
                    ->with(['package', 'hotspotVersion'])
                    ->latest()
                    ->get();

        $hotspotVersions = HotspotVersion::all();
        
        // Filtramos planes activos para el aliado
        $planesDisponibles = Package::where('is_active', true)->get();

        $this->refreshStatus();

        return view("livewire.mikrotik.aliado.list-routers", [
            "routers" => $routers,
            "hotspotVersions" => $hotspotVersions,
            "planesDisponibles" => $planesDisponibles
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
        } catch (\Exception $e) {
            // Silencioso para evitar interrupciones en la UI
        }
    }

    public function store()
    {
        $this->validate([
            "identity" => "required",
            "macAddress" => "required",
            "comercio_nombre" => "required",
            "package_id" => "required",
            "hotspot_version_id" => "required"
        ]);

        if (!$this->router_id) {
            $plan = Package::find($this->package_id);
            $totalActual = Router::where('user_id', Auth::id())
                                ->where('package_id', $this->package_id)
                                ->count();

            if ($totalActual >= $plan->limit_routers) {
                session()->flash("test_error", "Límite alcanzado: Este plan solo permite {$plan->limit_routers} routers.");
                return;
            }
        }

        Router::updateOrCreate(["id" => $this->router_id], [
            "user_id"            => Auth::id(),
            "package_id"         => $this->package_id,
            "identity"           => $this->identity,
            "macAddress"         => $this->macAddress,
            "location"           => $this->location,
            "status"             => $this->status,
            "comercio_nombre"    => $this->comercio_nombre,
            "hotspot_url"        => $this->hotspot_url,
            "hotspot_version_id" => $this->hotspot_version_id,
        ]);

        session()->flash("message", "Router guardado con éxito.");
        $this->closeModal();
    }

    public function edit(Router $router)
    {
        $this->router_id = $router->id;
        $this->identity = $router->identity;
        $this->macAddress = $router->macAddress;
        $this->location = $router->location;
        $this->status = $router->status;
        $this->package_id = $router->package_id;
        $this->comercio_nombre = $router->comercio_nombre;
        $this->hotspot_url = $router->hotspot_url;
        $this->hotspot_version_id = $router->hotspot_version_id;
        $this->openModal();
    }

    public function create() {
        $this->reset(['router_id', 'identity', 'package_id', 'macAddress', 'location', 'comercio_nombre', 'hotspot_url', 'hotspot_version_id']);
        $this->status = 'Habilitado';
        $this->openModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; }
}