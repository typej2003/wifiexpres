<?php

namespace App\Http\Livewire\Mikrotik\Router;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use App\Models\Setting;
use App\Models\HotspotVersion;
use App\Models\Package;
use App\Models\PackageUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class ListRouters extends Component
{
    public $selectedAliado = null; 
    public $isModalOpen = false;
    public $showPassword = false;
    public $routerStatus = []; 
    
    // Propiedades del formulario
    public $router_id, $user_id, $package_id, $identity, $ip, $api_port, $macAddress, $admin, $password, $location, $dns, $comercio_nombre, $hotspot_url;
    public $status, $is_active, $hotspot_version_id;

    public function render()
    {
        $aliados = User::where('role', 'aliado')->orwhere('role', 'aliadoSmartData')->get();
        $hotspotVersions = HotspotVersion::all();
        $setting = Setting::where('user_id', Auth::id())->first();
        $connectionMode = $setting ? (int)$setting->mikrotik_connection_mode : 0;

        $packages = collect();
        $ownerId = $this->user_id ?: $this->selectedAliado;
        if ($ownerId) {
            $userOwner = User::find($ownerId);
            if ($userOwner) {
                $packages = $userOwner->packages()
                    ->wherePivot('status', 'active')
                    ->wherePivot('end_date', '>=', now())
                    ->get();
            }
        }

        $routers = Router::query()
            ->when($this->selectedAliado, function($query) {
                $query->where('user_id', $this->selectedAliado);
            })
            ->with(['user', 'hotspotVersion', 'package'])
            ->latest()
            ->get();

        return view('livewire.mikrotik.router.list-routers', [
            'aliados' => $aliados,
            'routers' => $routers,
            'connectionMode' => $connectionMode,
            'hotspotVersions' => $hotspotVersions,
            'packages' => $packages
        ]);
    }

    public function refreshStatus()
    {
        try {
            // Consultamos al Bridge
            $response = Http::timeout(3)->get('http://188.95.113.44:3000/api/routers-online');
            
            if ($response->successful()) {
                $onlineRouters = $response->json();
                
                // Extraemos las MACs y las ponemos en Mayúsculas y sin espacios
                $activeMacs = collect($onlineRouters)->map(function($item) {
                    return strtoupper(trim($item['mac']));
                })->toArray();

                // Obtenemos los routers actuales de la vista
                $routers = Router::when($this->selectedAliado, function($query) {
                    $query->where('user_id', $this->selectedAliado);
                })->get();

                foreach ($routers as $r) {
                    $macLimpia = strtoupper(trim($r->macAddress));
                    $this->routerStatus[$r->id] = in_array($macLimpia, $activeMacs);
                }
            }
        } catch (\Exception $e) { 
            // Si falla la conexión al Bridge, reseteamos estados
            $this->routerStatus = []; 
        }
    }

    public function edit(Router $router) 
    {
        $this->router_id = $router->id;
        $this->user_id = $router->user_id; 
        $this->package_id = $router->package_id;
        $this->identity = $router->identity;
        $this->ip = $router->ip;
        $this->api_port = $router->api_port;
        $this->macAddress = $router->macAddress;
        $this->admin = $router->admin;
        $this->password = $router->password;
        $this->location = $router->location;
        $this->dns = $router->dns;
        $this->comercio_nombre = $router->comercio_nombre;
        $this->hotspot_url = $router->hotspot_url;
        $this->status = $router->status ?? 'Habilitado';
        $this->hotspot_version_id = $router->hotspot_version_id;

        $userOwner = User::find($this->user_id);
        $planesAliado = $userOwner ? $userOwner->packages()
            ->wherePivot('status', 'active')
            ->wherePivot('end_date', '>=', now())
            ->get() : collect();

        $this->emit('updatePackageList', [
            'packages' => $planesAliado,
            'selected' => $this->package_id
        ]);

        $this->openModal();
    }

    public function removePackage()
    {
        if (!$this->router_id || !$this->package_id) return;

        try {
            DB::beginTransaction();
            PackageUser::where('user_id', $this->user_id)
                        ->where('package_id', $this->package_id)
                        ->where('status', 'active')
                        ->decrement('router_quantity');

            Router::where('id', $this->router_id)->update(['package_id' => null]);
            $this->package_id = null;
            DB::commit();
            session()->flash('message', 'Plan desvinculado.');
            $this->edit(Router::find($this->router_id));
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function create() 
    {
        $this->reset(['identity', 'ip', 'macAddress', 'api_port', 'admin', 'password', 'location', 'router_id', 'dns', 'comercio_nombre', 'hotspot_url', 'status', 'is_active', 'hotspot_version_id', 'package_id']);
        $this->user_id = $this->selectedAliado; 
        $this->api_port = 49152; 
        $this->status = 'Habilitado';

        if($this->user_id) {
            $userOwner = User::find($this->user_id);
            $planesAliado = $userOwner ? $userOwner->packages()
                ->wherePivot('status', 'active')
                ->wherePivot('end_date', '>=', now())
                ->get() : collect();

            $this->emit('updatePackageList', [
                'packages' => $planesAliado,
                'selected' => null
            ]);
        }
        $this->openModal();
    }

    public function store()
    {
        $this->validate([
            'user_id' => 'required',
            'package_id' => 'required',
            'identity' => 'required',
            'ip' => 'required',
            'macAddress' => 'required',
            'api_port' => 'required|numeric',
            'admin' => 'required',
            'password' => 'required',
            'comercio_nombre' => 'required',
            'hotspot_version_id' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $newPackagePivot = PackageUser::where('user_id', $this->user_id)
                                        ->where('package_id', $this->package_id)
                                        ->where('status', 'active')
                                        ->where('end_date', '>=', now())
                                        ->first();

            if (!$newPackagePivot) {
                session()->flash('error', 'Plan no activo.');
                return;
            }

            $oldRouter = $this->router_id ? Router::find($this->router_id) : null;
            $oldPackageId = $oldRouter ? $oldRouter->package_id : null;

            if (!$oldRouter || ($oldPackageId != $this->package_id)) {
                if ($newPackagePivot->router_quantity >= $newPackagePivot->allowed_routers) {
                    session()->flash('error', 'Límite alcanzado.');
                    return;
                }
            }

            if ($oldRouter && $oldPackageId && $oldPackageId != $this->package_id) {
                PackageUser::where('user_id', $this->user_id)
                            ->where('package_id', $oldPackageId)
                            ->where('status', 'active')
                            ->decrement('router_quantity');
                $newPackagePivot->increment('router_quantity');
            } elseif (!$oldRouter || ($oldRouter && !$oldPackageId)) {
                $newPackagePivot->increment('router_quantity');
            }

            Router::updateOrCreate(['id' => $this->router_id], [
                'user_id' => $this->user_id,
                'package_id' => $this->package_id,
                'identity' => $this->identity,
                'ip' => $this->ip,
                'api_port' => $this->api_port,
                'macAddress' => $this->macAddress,
                'admin' => $this->admin,
                'password' => $this->password,
                'location' => $this->location,
                'dns' => $this->dns,
                'comercio_nombre' => $this->comercio_nombre,
                'hotspot_url' => $this->hotspot_url,
                'status' => $this->status,
                'is_active' => ($this->status === 'Habilitado'),
                'hotspot_version_id' => $this->hotspot_version_id,
            ]);

            DB::commit();
            $this->closeModal();
            session()->flash('message', 'Router guardado.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; $this->showPassword = false; }
    public function togglePassword() { $this->showPassword = !$this->showPassword; }
}