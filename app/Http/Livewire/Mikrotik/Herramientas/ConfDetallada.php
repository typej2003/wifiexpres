<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class ConfDetallada extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    
    // Datos dinámicos del descubrimiento
    public $interfaces = []; 
    public $isSearching = false;
    public $showRetry = false; // Nueva bandera para mostrar botón de reintento
    public $status = [];
    public $logs = [];
    public $identity = "MikroTik";
    
    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado.');
        }
    }

    public function updatedSelectedAliado()
    {
        $this->reset(['router_id', 'interfaces', 'status', 'logs', 'isSearching', 'showRetry']);
    }

    public function updatedRouterId($value)
    {
        if ($value) {
            $this->descubrirInterfaces();
        }
    }

    /**
     * PASO 1: Enviar comando para listar interfaces reales
     */
    public function descubrirInterfaces()
    {
        if (!$this->router_id) return;

        $this->isSearching = true;
        $this->showRetry = false;
        $this->interfaces = [];
        $this->logs['global'] = "🔍 Interrogando al MikroTik por sus interfaces...";

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "DISC" . time();

        // Script para obtener interfaces Ethernet y WiFi y enviarlas de vuelta
        $script = '{
            :local ifaces "";
            :foreach i in=[/interface find where type="ether" or type="wlan" or type="wifi" or type="vlan"] do={
                :set ifaces ($ifaces . [/interface get $i name] . ",");
            };
            /tool fetch url="'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$tid.'&data=$ifaces" keep-result=no
        }';

        $body = trim(preg_replace('/\s+/', ' ', $script));

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($body, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            // Iniciamos el chequeo de la respuesta
            $this->esperarRespuestaInterfaces($mac, $tid);
        } catch (\Exception $e) {
            $this->logs['global'] = "❌ Error de conexión con el Bridge.";
            $this->isSearching = false;
            $this->showRetry = true;
        }
    }

    /**
     * PASO 2: Esperar el resultado del Bridge (Polling)
     */
    private function esperarRespuestaInterfaces($mac, $tid)
    {
        $intentos = 0;
        // Esperamos un máximo de 15 segundos
        while ($intentos < 15) {
            sleep(1);
            try {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", [
                    'mac' => $mac, 
                    'tid' => $tid
                ]);
                
                if ($res->successful() && $res->json('status') === 'ready') {
                    $data = $res->json('data'); 
                    
                    if (empty($data) || $data == "ERR") {
                        $this->logs['global'] = "⚠️ El MikroTik no devolvió interfaces válidas.";
                        $this->showRetry = true;
                        $this->isSearching = false;
                        return;
                    }

                    $this->interfaces = array_filter(explode(',', $data));
                    
                    foreach ($this->interfaces as $iface) {
                        $this->status[$iface] = 'idle';
                        $this->logs[$iface] = 'Detectada correctamente.';
                    }
                    
                    $this->isSearching = false;
                    $this->logs['global'] = "✅ Descubrimiento finalizado.";
                    return;
                }
            } catch (\Exception $e) {}
            $intentos++;
        }

        $this->isSearching = false;
        $this->showRetry = true;
        $this->logs['global'] = "🛑 Tiempo agotado. El MikroTik no respondió al descubrimiento.";
    }

    public function configurarPuerto($interface, $index)
    {
        $this->status[$interface] = 'loading';
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        
        // El segmento IP basado en la posición del puerto (ether2=20, ether3=30...)
        $segmento = ($index + 2) * 10;

        $cmds = [
            "/interface bridge add name=bridge-$interface",
            "/interface bridge port add bridge=bridge-$interface interface=$interface",
            "/ip pool add name=pool-$interface ranges=192.168.$segmento.10-192.168.$segmento.250",
            "/ip address add address=192.168.$segmento.1/24 interface=bridge-$interface",
            "/ip dhcp-server add address-pool=pool-$interface interface=bridge-$interface name=srv-$interface disabled=no",
            "/ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8",
            "/ip hotspot add address-pool=pool-$interface interface=bridge-$interface name=hotspot-$interface profile=hsprof1 disabled=no"
        ];

        if ($this->enviarAlBridge($mac, implode("; ", $cmds))) {
            $this->status[$interface] = 'success';
            $this->logs[$interface] = "✅ Configurado: 192.168.$segmento.1";
        } else {
            $this->status[$interface] = 'error';
            $this->logs[$interface] = "❌ Error al enviar comandos.";
        }
    }

    private function enviarAlBridge($mac, $comando)
    {
        try {
            $tid = "TID" . time();
            $script = '{ :do { '.$comando.' } on-error={ :log info "Error" } }';
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
            return $response->successful();
        } catch (\Exception $e) { return false; }
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