<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class ConfDetallada extends Component
{
    // Filtros
    public $selectedAliado = null;
    public $router_id = null;
    
    // Estados de la interfaz
    public $interfaces = [];
    public $status = []; // 'idle', 'loading', 'success', 'error'
    public $logs = [];
    public $identity = "MikroTik";
    
    // Configuración de comunicación (según tu código base)
    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado.');
        }
    }

    // Resetear al cambiar aliado
    public function updatedSelectedAliado()
    {
        $this->router_id = null;
        $this->interfaces = [];
        $this->status = [];
        $this->logs = [];
    }

    // Al seleccionar router, obtenemos las interfaces (Simulado por ahora via Script)
    public function updatedRouterId($value)
    {
        if ($value) {
            $router = Router::find($value);
            $this->identity = $router->identity ?? 'MikroTik';
            
            // Simulación de detección: En un escenario real enviarías un comando 
            // para listar interfaces y recibir la respuesta.
            // Por ahora, inicializamos las estándar según tu lógica de "counter 2"
            $this->interfaces = ['ether2', 'ether3', 'ether4', 'ether5'];
            
            foreach ($this->interfaces as $iface) {
                $this->status[$iface] = 'idle';
                $this->logs[$iface] = 'Esperando comandos...';
            }
            $this->status['profiles'] = 'idle';
            $this->logs['profiles'] = 'Pendiente.';
        }
    }

    /**
     * Configuración independiente por puerto
     * Incluye: Bridge, Port, Address, Pool, DHCP, Hotspot
     */
    public function configurarPuerto($interface, $index)
    {
        $this->status[$interface] = 'loading';
        $this->logs[$interface] = "Enviando secuencia completa a $interface...";

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        
        // Lógica de segmentos: ether2 = 192.168.20.x, ether3 = 192.168.30.x...
        $counter = $index + 2; 
        $segmento = $counter * 10;

        // Comandos secuenciales para esta sección específica
        $cmds = [
            "/interface bridge add name=bridge-$interface",
            "/interface bridge port add bridge=bridge-$interface interface=$interface",
            "/ip pool add name=pool-$interface ranges=192.168.$segmento.10-192.168.$segmento.250",
            "/ip address add address=192.168.$segmento.1/24 interface=bridge-$interface",
            "/ip dhcp-server add address-pool=pool-$interface interface=bridge-$interface name=srv-$interface disabled=no",
            "/ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8",
            "/ip hotspot add address-pool=pool-$interface interface=bridge-$interface name=hotspot-$interface profile=hsprof1 disabled=no"
        ];

        $fullCmd = implode("; ", $cmds);
        
        if ($this->enviarAlBridge($mac, $fullCmd)) {
            $this->status[$interface] = 'success';
            $this->logs[$interface] = "✅ Configurado: Red 192.168.$segmento.0/24 activa.";
        } else {
            $this->status[$interface] = 'error';
            $this->logs[$interface] = "❌ Error al enviar comandos.";
        }
    }

    public function configurarGlobal()
    {
        $this->status['profiles'] = 'loading';
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        $cmds = [
            '/ip hotspot user profile add name="neutro" session-timeout=1s shared-users=1',
            '/ip hotspot user profile add name="conexiongratis" shared-users=1 rate-limit="2M/2M"',
            '/ip hotspot profile add dns-name=wifi.login name=hsprof1 login-by=http-chap,http-pap,trial trial-user-profile=conexiongratis',
            '/ip hotspot walled-garden add dst-host=wifiexpres.com'
        ];

        if ($this->enviarAlBridge($mac, implode("; ", $cmds))) {
            $this->status['profiles'] = 'success';
            $this->logs['profiles'] = "✅ Perfiles y Walled Garden configurados.";
        }
    }

    private function enviarAlBridge($mac, $comando)
    {
        try {
            $tid = "TID" . time();
            $script = '{ :do { '.$comando.' } on-error={ :log info "Error en secuencia" } }';
            $body = trim(preg_replace('/\s+/', ' ', $script));

            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($body, 'text/plain')
                ->timeout(10)
                ->post("{$this->bridgeUrl}/set-command");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.conf-detallada', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::query()
                ->when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
                ->get(),
        ]);
    }
}