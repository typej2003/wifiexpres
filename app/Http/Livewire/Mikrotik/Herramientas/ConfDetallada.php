<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConfDetallada extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    public $interfaces = []; 
    public $isWaitingResponse = false; 
    public $currentTid = null;
    public $intentos = 0;
    public $taskStatus = []; 
    public $taskResult = []; 
    public $activeTask = null; 

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado.');
        }
    }

    public function updatedSelectedAliado()
    {
        $this->reset(['router_id', 'interfaces', 'taskStatus', 'taskResult', 'isWaitingResponse']);
    }

    public function updatedRouterId($value)
    {
        if ($value) {
            $this->iniciarDescubrimiento();
        }
    }

    public function iniciarDescubrimiento()
    {
        if (!$this->router_id) return;
        $this->reset(['interfaces', 'taskStatus', 'taskResult', 'intentos']);
        $this->isWaitingResponse = true;
        $this->currentTid = "DISC" . time();
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $script = "{
            :local ifaces \"\";
            :foreach i in=[/interface find where type=\"ether\" or type=\"wlan\" or type=\"wifi\"] do={
                :set ifaces (\$ifaces . [/interface get \$i name] . \",\");
            };
            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid={$this->currentTid}\" http-method=post http-data=\$ifaces keep-result=no;
        }";
        $this->emitirAlBridge($script, $mac, $this->currentTid);
    }

    public function ejecutarTarea($iface, $index, $tarea)
    {
        $this->taskStatus[$iface][$tarea] = 'loading';
        $this->taskResult[$iface][$tarea] = 'Procesando dependencias...';
        $this->activeTask = ['iface' => $iface, 'tarea' => $tarea];
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $counter = $index + 1; 
        $segmento = $counter * 10;

        $cmds = [
            'bridge'  => ":if ([:len [/interface bridge find name=\"bridge-$iface\"]] > 0) do={ :set res \"OK: Bridge ya existe\" } else={ /interface bridge add name=\"bridge-$iface\"; /interface bridge port add bridge=\"bridge-$iface\" interface=\"$iface\"; :set res \"OK: Bridge creado\" };",
            'address' => ":if ([:len [/ip address find where interface=\"bridge-$iface\"]] > 0) do={ :set res \"OK: IP ya configurada\" } else={ /ip address add address=192.168.$segmento.1/24 interface=\"bridge-$iface\"; :set res \"OK: IP asignada\" };",
            'pool'    => ":if ([:len [/ip pool find name=\"pool-$iface\"]] > 0) do={ :set res \"OK: Pool ya existe\" } else={ /ip pool add name=\"pool-$iface\" ranges=192.168.$segmento.10-192.168.$segmento.250; :set res \"OK: Pool creado\" };",
            'dhcp'    => ":if ([:len [/ip dhcp-server find interface=\"bridge-$iface\"]] > 0) do={ :set res \"OK: DHCP ya existe\" } else={ /ip dhcp-server add address-pool=\"pool-$iface\" interface=\"bridge-$iface\" name=\"srv-$iface\" disabled=no; /ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8; :set res \"OK: DHCP activo\" };",
            'hotspot' => "{
                :if ([:len [/ip hotspot user profile find name=\"neutro\"]] = 0) do={ /ip hotspot user profile add name=\"neutro\" shared-users=1 };
                :if ([:len [/ip hotspot user profile find name=\"cortesia 20min-0\"]] = 0) do={ /ip hotspot user profile add name=\"cortesia 20min-0\" session-timeout=20m shared-users=1 };
                :if ([:len [/ip hotspot user profile find name=\"conexiongratis\"]] = 0) do={ /ip hotspot user profile add name=\"conexiongratis\" shared-users=1 rate-limit=\"2M/2M\" };
                :if ([:len [/ip hotspot profile find name=\"hsprof1\"]] = 0) do={ 
                    /ip hotspot profile add dns-name=wifi.login name=hsprof1 login-by=http-chap,http-pap,trial,cookie,mac-cookie trial-user-profile=conexiongratis;
                };
                :if ([:len [/ip hotspot find interface=\"bridge-$iface\"]] > 0) do={ 
                    :set res \"OK: Hotspot ya existe\";
                } else={ 
                    /ip hotspot add address-pool=\"pool-$iface\" interface=\"bridge-$iface\" name=\"hotspot-$iface\" profile=hsprof1 disabled=no;
                    :set res \"OK: Hotspot listo con Cookies\";
                };
            }"
        ];

        $this->currentTid = "CFG" . time();
        $this->intentos = 0;
        $script = ":local res \"\"; :local m \"$mac\"; :local t \"{$this->currentTid}\"; " .
                  ":do { {$cmds[$tarea]} } on-error={ :set res \"Error: Falta IP o Pool\" }; " .
                  "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$res keep-result=no;";
        $this->emitirAlBridge($script, $mac, $this->currentTid);
    }

    protected function emitirAlBridge($script, $mac, $tid)
    {
        $comandoLimpio = trim(preg_replace('/\s+/', ' ', $script));
        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($comandoLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
        } catch (\Exception $e) { Log::error("Error enviando al bridge: " . $e->getMessage()); }
    }

    public function checkStatus()
    {
        if (!$this->isWaitingResponse && !$this->activeTask) return;
        $this->intentos++;
        $router = Router::find($this->router_id);
        if (!$router) return;
        $mac = strtoupper(trim($router->macAddress));
        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);
            if ($res->successful() && $res->json('status') === 'ready') {
                $data = trim($res->json('data'));
                if ($this->isWaitingResponse) {
                    $this->interfaces = array_filter(explode(',', $data));
                    $this->isWaitingResponse = false;
                } elseif ($this->activeTask) {
                    $iface = $this->activeTask['iface'];
                    $tarea = $this->activeTask['tarea'];
                    $this->taskStatus[$iface][$tarea] = (strpos($data, 'OK') !== false) ? 'success' : 'error';
                    $this->taskResult[$iface][$tarea] = $data;
                    $this->activeTask = null;
                }
                $this->intentos = 0;
            } elseif ($this->intentos >= 35) { $this->handleTimeout(); }
        } catch (\Exception $e) {}
    }

    private function handleTimeout()
    {
        if ($this->isWaitingResponse) $this->interfaces = [];
        if ($this->activeTask) {
            $this->taskStatus[$this->activeTask['iface']][$this->activeTask['tarea']] = 'error';
            $this->taskResult[$this->activeTask['iface']][$this->activeTask['tarea']] = 'Timeout: Sin respuesta';
        }
        $this->isWaitingResponse = false;
        $this->activeTask = null;
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