<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use App\Models\HotspotVersion;
use Illuminate\Support\Facades\Http;

class ConfDetallada extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    public $version_id = null; 
    public $interfaces = []; 
    public $isWaitingResponse = false; 
    public $currentTid = null;
    public $intentos = 0;
    
    public $taskStatus = []; 
    public $taskResult = []; 
    public $activeTask = null; 
    public $queue = [];

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function updatedSelectedAliado()
    {
        $this->reset(['router_id', 'interfaces', 'taskStatus', 'taskResult', 'isWaitingResponse', 'queue']);
    }

    public function updatedRouterId($value)
    {
        if ($value) $this->iniciarDescubrimiento();
    }

    public function iniciarDescubrimiento()
    {
        if (!$this->router_id) return;
        $this->reset(['interfaces', 'taskStatus', 'taskResult', 'intentos', 'queue']);
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

    public function scanearInterfaz($iface, $index)
    {
        $this->queue = [];
        $tareas = ['bridge', 'address', 'pool', 'dhcp', 'hotspot'];
        foreach ($tareas as $t) {
            $this->queue[] = ['iface' => $iface, 'index' => $index, 'tarea' => $t];
        }
        $this->procesarSiguienteEnCola();
    }

    public function scanGlobal()
    {
        if (!$this->version_id) return;
        $this->queue = [];
        $tareas = ['walledgarden', 'walledgardenip', 'portal', 'reboot'];
        foreach ($tareas as $t) {
            $this->queue[] = ['iface' => 'global', 'index' => 0, 'tarea' => $t];
        }
        $this->procesarSiguienteEnCola();
    }

    public function procesarSiguienteEnCola()
    {
        if (count($this->queue) > 0) {
            $next = array_shift($this->queue);
            $this->ejecutarTarea($next['iface'], $next['index'], $next['tarea']);
        }
    }

    public function ejecutarTarea($iface, $index, $tarea)
    {
        $this->taskStatus[$iface][$tarea] = 'loading';
        $this->taskResult[$iface][$tarea] = 'Procesando...';
        $this->activeTask = ['iface' => $iface, 'tarea' => $tarea, 'index' => $index];
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $counter = $index + 1; 
        $segmento = $counter * 10;
        
        $vCode = 1;
        if($this->version_id) {
            $versionObj = HotspotVersion::find($this->version_id);
            $vCode = $versionObj ? $versionObj->code : 1;
        }

        $downloadUrl = "https://wifiexpres.com/api/portal-download/" . $vCode;

        // Comandos crudos pero con limpieza previa para evitar el error de "ya existe"
        $cmds = [
            'bridge'  => "/interface bridge { remove [find name=\"bridge-$iface\"]; add name=\"bridge-$iface\" }; /interface bridge port { remove [find interface=\"$iface\"]; add bridge=\"bridge-$iface\" interface=\"$iface\" }",
            
            'address' => "/ip address { remove [find interface=\"bridge-$iface\"]; add address=192.168.$segmento.1/24 interface=\"bridge-$iface\" }",
            
            'pool'    => "/ip pool { remove [find name=\"pool-$iface\"]; add name=\"pool-$iface\" ranges=192.168.$segmento.10-192.168.$segmento.250 }",
            
            'dhcp'    => "/ip dhcp-server { remove [find interface=\"bridge-$iface\"]; add address-pool=\"pool-$iface\" interface=\"bridge-$iface\" name=\"srv-$iface\" disabled=no }; /ip dhcp-server network { remove [find address=192.168.$segmento.0/24]; add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8 }",
            
            'hotspot' => "/ip hotspot user profile { remove [find name=\"neutro\"]; remove [find name=\"cortesia 20min-0\"]; remove [find name=\"conexiongratis\"]; add name=\"neutro\" shared-users=1 session-timeout=1s; add name=\"cortesia 20min-0\" shared-users=1 session-timeout=20m; add name=\"conexiongratis\" shared-users=1 rate-limit=\"2M/2M\" }; /ip hotspot profile { remove [find name=\"hsprof1\"]; add dns-name=wifi.login name=hsprof1 login-by=http-chap,http-pap,trial trial-user-profile=conexiongratis }; /ip hotspot { remove [find interface=\"bridge-$iface\"]; add address-pool=\"pool-$iface\" interface=\"bridge-$iface\" name=\"hotspot-$iface\" profile=hsprof1 disabled=no }",
            
            'walledgarden' => '/ip hotspot user { remove [find name=admin]; add name=admin password=admin123 }; /ip hotspot walled-garden { remove [find]; add dst-host=wifiexpres.com; add dst-host=*.wifiexpres.com; add dst-host=*.biopagobdv.com; add dst-host=*.banvenez.com; add dst-host=biopago.banvenez.com; add dst-host=fcm.googleapis.com; add dst-host=fcm-xmpp.googleapis.com; add dst-host=mtalk.google.com; add dst-host=*.push.apple.com; add dst-host=*.push.apple.com.akadns.net; add dst-host=appleid.apple.com; add dst-host=188.95.113.44 }',
            
            'walledgardenip' => '/ip hotspot walled-garden ip { remove [find]; add dst-address=188.95.113.44; add dst-address=190.217.7.106; add dst-address=190.217.7.229; add dst-address=200.11.243.174; add dst-address=190.202.148.187; add action=accept dst-port=5228-5230 protocol=tcp; add action=accept dst-port=5223 protocol=tcp; add action=accept dst-port=53 protocol=udp; add action=accept dst-port=53 protocol=tcp }',

            'portal' => "/ip hotspot profile set [find name=\"hsprof1\"] html-directory=hotspot; /tool fetch url=\"$downloadUrl\" dst-path=\"hotspot/login.html\" check-certificate=no",
            
            'reboot' => "/system reboot"
        ];

        $this->currentTid = "CFG" . rand(10,99) . time();
        
        // Enviamos el comando directo sin envolverlo en bloques pesados. 
        // Si el MikroTik llega a la última instrucción, reporta "OK".
        $script = $cmds[$tarea] . "; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid={$this->currentTid}\" http-method=post http-data=\"OK\" keep-result=no;";
        
        $this->emitirAlBridge($script, $mac, $this->currentTid);
    }

    protected function emitirAlBridge($script, $mac, $tid)
    {
        $comandoLimpio = trim(preg_replace('/\s+/', ' ', $script));
        Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comandoLimpio, 'text/plain')->post("{$this->bridgeUrl}/set-command");
    }

    public function checkStatus()
    {
        if (!$this->isWaitingResponse && !$this->activeTask) return;
        $this->intentos++;
        $router = Router::find($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        
        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);
            if ($res->successful() && $res->json('status') === 'ready') {
                $data = trim($res->json('data'));
                if ($this->activeTask) {
                    $iface = $this->activeTask['iface'];
                    $tarea = $this->activeTask['tarea'];
                    
                    // Si recibimos "OK", el botón se pone verde. 
                    // Si no recibimos nada o error, el timeout se encargará de ponerlo rojo.
                    if ($data === "OK") {
                        $this->taskStatus[$iface][$tarea] = 'success';
                        $this->taskResult[$iface][$tarea] = "Aplicado correctamente";
                        $this->activeTask = null;
                        $this->procesarSiguienteEnCola();
                    }
                } else {
                    $this->interfaces = array_filter(explode(',', $data));
                    $this->isWaitingResponse = false;
                }
                $this->intentos = 0;
            } elseif ($this->intentos >= 35) {
                $this->handleTimeout();
            }
        } catch (\Exception $e) {}
    }

    private function handleTimeout()
    {
        if ($this->isWaitingResponse) $this->isWaitingResponse = false;
        if ($this->activeTask) {
            $this->taskStatus[$this->activeTask['iface']][$this->activeTask['tarea']] = 'error';
            $this->taskResult[$this->activeTask['iface']][$this->activeTask['tarea']] = 'TIMEOUT / FALLO';
            $this->activeTask = null;
            $this->queue = [];
        }
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.conf-detallada', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::where('user_id', $this->selectedAliado)->get(),
            'hotspot_versions' => HotspotVersion::all()
        ]);
    }
}