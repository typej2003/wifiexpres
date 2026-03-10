<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigurarRemoto extends Component
{
    public $router_id;
    public $selectedAliado = null;
    public $routerStatus = [];
    public $logs = [];
    public $isConfiguring = false;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Solo el administrador puede acceder a esta herramienta.');
        }
        $this->refreshStatus();
    }

    public function refreshStatus()
    {
        try {
            $response = Http::timeout(5)->get("{$this->bridgeUrl}/api/routers-online");
            if ($response->successful()) {
                $onlineRouters = $response->json();
                $activeMacs = collect($onlineRouters)->map(function($item) {
                    return strtoupper(trim($item['mac']));
                })->toArray();

                $routers = Router::all();
                $this->routerStatus = [];
                foreach ($routers as $r) {
                    $macLimpia = strtoupper(trim($r->macAddress));
                    $this->routerStatus[$r->id] = in_array($macLimpia, $activeMacs);
                }
            }
        } catch (\Exception $e) {
            $this->routerStatus = [];
            Log::error("Error al refrescar status: " . $e->getMessage());
        }
    }

    public function emitirAlSocket($comando, $mac, $tid)
    {
        try {
            $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
            $response = Http::withHeaders([
                'x-mac' => $mac,
                'x-id'  => $tid
            ])
            ->withBody($comandoLimpio, 'text/plain')
            ->post("{$this->bridgeUrl}/set-command");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error Bridge: " . $e->getMessage());
            return false;
        }
    }

    public function esperarRespuesta($mac, $tid)
    {
        for ($i = 0; $i < 30; $i++) {
            sleep(1);
            try {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    return $res->json('data');
                }
            } catch (\Exception $e) { }
        }
        return null;
    }

    public function ejecutarConfiguracion()
    {
        $this->validate(['router_id' => 'required']);
        
        if (!($this->routerStatus[$this->router_id] ?? false)) {
            $this->logs[] = "❌ Error: El router seleccionado no está conectado al Bridge.";
            return;
        }

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);
        $tid = "CONF" . time();

        $this->isConfiguring = true;
        $this->logs[] = "Iniciando configuración remota para: " . ($router->identity ?? $mac);

        // SCRIPT DE AUTOMATIZACIÓN MIKROTIK
        $script = "
            :local m \"$mac\"; :local t \"$tid\";
            :do {
                /user add name=\"soporte\" password=\"123\" group=full comment=\"Acceso remoto automatizado\";
                /interface bridge add name=bridge-lan comment=\"Bridge principal creado desde Panel\";
                :foreach i in=[/interface ethernet find where name!=\"ether1\"] do={
                    :local ethName [/interface ethernet get \$i name];
                    /interface bridge port add bridge=bridge-lan interface=\$ethName;
                };
                /ip address add address=192.168.88.1/24 interface=bridge-lan network=192.168.88.0;
                /ip pool add name=dhcp_pool1 ranges=192.168.88.10-192.168.88.254;
                /ip dhcp-server add address-pool=dhcp_pool1 disabled=no interface=bridge-lan name=dhcp-remoto;
                /ip dhcp-server network add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=8.8.8.8,8.8.4.4;
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"SUCCESS\" keep-result=no;
            } on-error={
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no;
            };
        ";

        if ($this->emitirAlSocket($script, $mac, $tid)) {
            $this->logs[] = "📡 Comando enviado. Esperando respuesta del router...";
            $resultado = $this->esperarRespuesta($mac, $tid);

            if ($resultado === "SUCCESS") {
                $this->logs[] = "✅ CONFIGURACIÓN EXITOSA: Usuario, Bridge e IP aplicados.";
                session()->flash('message', 'Configuración exitosa.');
            } else {
                $this->logs[] = "❌ ERROR: MikroTik rechazó el script (posible conflicto de nombres).";
            }
        } else {
            $this->logs[] = "❌ ERROR: Falló la comunicación con el Bridge Socket.";
        }

        $this->isConfiguring = false;
        $this->refreshStatus(); // Refrescar por si cambió el estado
    }

    public function render()
    {
        $aliados = User::where('role', 'aliado')->get();
        $query = Router::query();
        
        if ($this->selectedAliado) {
            $query->where('user_id', $this->selectedAliado);
        }

        return view('livewire.mikrotik.herramientas.configurar-remoto', [
            'routers' => $query->get(),
            'aliados' => $aliados
        ]);
    }
}