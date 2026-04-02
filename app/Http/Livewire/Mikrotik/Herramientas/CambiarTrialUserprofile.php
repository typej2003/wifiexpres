<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class CambiarTrialUserprofile extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    public $routerStatus = [];
    public $perfiles = [];
    public $perfil_seleccionado = null;
    public $perfil_actual = null;
    public $loading = false;
    public $message = null;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        $this->refreshStatus();
    }

    public function refreshStatus()
    {
        try {
            $response = Http::timeout(5)->get("{$this->bridgeUrl}/api/routers-online");
            if ($response->successful()) {
                $onlineRouters = $response->json();
                $activeMacs = collect($onlineRouters)->map(fn($item) => strtoupper(trim($item['mac'])))->toArray();
                $routers = Router::all();
                $this->routerStatus = [];
                foreach ($routers as $r) {
                    $macLimpia = strtoupper(trim($r->macAddress));
                    $this->routerStatus[$r->id] = in_array($macLimpia, $activeMacs);
                }
            }
        } catch (\Exception $e) { $this->routerStatus = []; }
    }

    public function updatedRouterId($value)
    {
        if ($value && ($this->routerStatus[$value] ?? false)) {
            $this->obtenerDatosCompletos();
        } else {
            $this->reset(['perfiles', 'perfil_actual', 'perfil_seleccionado']);
        }
    }

    /**
     * UNIFICADO: Obtiene el perfil actual y la lista de perfiles en un solo comando
     */
    public function obtenerDatosCompletos()
    {
        if (!$this->router_id) return;

        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "DATA_" . time();

        // Script unificado: 
        // 1. Obtiene el perfil actual de hsprof1
        // 2. Recorre los perfiles y los concatena
        // 3. Envía todo en un solo string separado por un delimitador '|'
        $comando = ":local actual [/ip hotspot profile get [find name=\"hsprof1\"] trial-user-profile]; " .
                   ":local lista \"\"; :foreach i in=[/ip hotspot user profile find] do={ " .
                   ":set lista (\$lista . [/ip hotspot user profile get \$i name] . \",\"); }; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid&data=\$actual|\$lista\" keep-result=no;";

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comando, 'text/plain')->post("{$this->bridgeUrl}/set-command");

            for ($i = 0; $i < 12; $i++) {
                usleep(800000);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                
                if ($res->successful() && $res->json('status') === 'ready') {
                    $payload = $res->json('data');
                    // Separamos el actual de la lista usando el pipe |
                    $parts = explode('|', $payload);
                    
                    $this->perfil_actual = $parts[0] ?? 'No definido';
                    $rawLista = $parts[1] ?? '';
                    $this->perfiles = array_filter(explode(',', trim($rawLista, ",")));
                    
                    $this->loading = false;
                    return;
                }
            }
        } catch (\Exception $e) { }
        
        $this->loading = false;
        $this->message = "⚠️ No se recibió respuesta del Router.";
    }

    public function aplicarCambio()
    {
        $this->validate(['router_id' => 'required', 'perfil_seleccionado' => 'required']);

        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "SETTRIAL_" . time();

        $comando = ":do { /ip hotspot profile set [find name=\"hsprof1\"] trial-user-profile=\"{$this->perfil_seleccionado}\"; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid&data=OK\" keep-result=no; " .
                   "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid&data=ERROR\" keep-result=no; }";

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comando, 'text/plain')->post("{$this->bridgeUrl}/set-command");

            for ($i = 0; $i < 10; $i++) {
                usleep(800000);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    if($res->json('data') == 'OK') {
                        $this->message = "✅ Perfil Trial actualizado correctamente.";
                        $this->perfil_actual = $this->perfil_seleccionado;
                        $this->perfil_seleccionado = null;
                    } else {
                        $this->message = "❌ Error al aplicar cambio.";
                    }
                    $this->loading = false;
                    return;
                }
            }
        } catch (\Exception $e) { $this->message = "❌ Error de conexión."; }
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.cambiar-trial-userprofile', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))->get()
        ])->layout('layouts.app');
    }
}