<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\Package;
use App\Models\HotspotVersion;
use App\Models\Setting;
use RouterOS\Client;
use RouterOS\Query;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ListRouters extends Component
{
    public $isModalOpen = false;
    public $showPassword = false;
    public $router_id, $identity, $ip, $macAddress, $admin, $password, $location, $dns, $api_port;
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

        // Obtener versiones
        $hotspotVersions = HotspotVersion::all();
        
        /** * PLANES DISPONIBLES: 
         * Aquí podrías filtrar para que el aliado solo vea los planes que HA COMPRADO 
         * o que están activos en su tabla 'package_user'. 
         * Por ahora, mostramos los activos globalmente para la prueba.
         */
        $planesDisponibles = Package::where('is_active', true)->get();

        // Sincronizamos estados online (usando tu lógica de Node.js si la prefieres o la de RouterOS)
        $this->refreshStatus();

        return view("livewire.mikrotik.aliado.list-routers", [
            "routers" => $routers,
            "hotspotVersions" => $hotspotVersions,
            "planesDisponibles" => $planesDisponibles
        ]);
    }

    public function refreshStatus()
    {
        // Usamos la API de Node para mayor velocidad como en la vista de Admin
        try {
            $response = Http::timeout(2)->get('http://188.95.113.44:3000/api/routers-online');
            $activeMacs = $response->successful() ? collect($response->json())->pluck('mac')->toArray() : [];

            $routers = Router::where("user_id", Auth::id())->get();
            foreach ($routers as $r) {
                $this->routerStatus[$r->id] = in_array($r->macAddress, $activeMacs);
            }
        } catch (\Exception $e) {
            // Fallback: Si el servicio Node falla, no marcamos nada para no dar falsos negativos
        }
    }

    public function testConnection()
    {
        $this->validate([
            "ip" => "required",
            "admin" => "required",
            "password" => "required",
            "api_port" => "required|numeric",
        ]);

        try {
            $client = new Client([
                'host' => $this->ip,
                'user' => $this->admin,
                'pass' => $this->password,
                'port' => (int)$this->api_port,
                'timeout' => 3
            ]);
            
            $query = new Query('/system/identity/print');
            $response = $client->query($query)->read();
            $this->identity = $response[0]['name'] ?? 'MikroTik';
            
            session()->flash("test_message", "¡Conexión exitosa! Identity: " . $this->identity);
        } catch (\Exception $e) {
            session()->flash("test_error", "Error: " . $e->getMessage());
        }
    }

    public function store()
    {
        $this->validate([
            "ip" => "required",
            "admin" => "required",
            "password" => "required",
            "comercio_nombre" => "required",
            "api_port" => "required|numeric",
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
            "identity"           => $this->identity ?? "MikroTik",
            "ip"                 => $this->ip,
            "api_port"           => $this->api_port,
            "macAddress"         => $this->macAddress,
            "admin"              => $this->admin,
            "password"           => $this->password,
            "location"           => $this->location,
            "dns"                => $this->dns,
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
        $this->ip = $router->ip;
        $this->api_port = $router->api_port;
        $this->macAddress = $router->macAddress;
        $this->admin = $router->admin;
        $this->password = $router->password;
        $this->location = $router->location;
        $this->dns = $router->dns;
        $this->status = $router->status;
        $this->package_id = $router->package_id;
        $this->comercio_nombre = $router->comercio_nombre;
        $this->hotspot_url = $router->hotspot_url;
        $this->hotspot_version_id = $router->hotspot_version_id;
        $this->openModal();
    }

    public function create() {
        $this->reset(['router_id', 'identity', 'ip', 'package_id', 'macAddress', 'admin', 'password', 'location', 'dns', 'comercio_nombre', 'hotspot_url', 'hotspot_version_id']);
        $this->api_port = 8728;
        $this->status = 'Habilitado';
        $this->openModal();
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; $this->showPassword = false; }
    public function togglePassword() { $this->showPassword = !$this->showPassword; }
}