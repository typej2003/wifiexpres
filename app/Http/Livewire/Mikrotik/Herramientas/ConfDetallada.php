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

    // Cola para el Scan Auto
    public $queue = [];

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado.');
        }
    }

    public function updatedSelectedAliado()
    {
        $this->reset(['router_id', 'interfaces', 'taskStatus', 'taskResult', 'isWaitingResponse', 'queue']);
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
        // Limpiamos cualquier cola previa por seguridad
        $this->queue = [];
        $tareas = ['bridge', 'address', 'pool', 'dhcp', 'hotspot'];
        foreach ($tareas as $t) {
            $this->queue[] = [
                'iface' => $iface,
                'index' => $index,
                'tarea' => $t
            ];
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
        $this->taskResult[$iface][$tarea] = 'Verificando...';
        $this->activeTask = ['iface' => $iface, 'tarea' => $tarea, 'index' => $index];
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $counter = $index + 1; 
        $segmento = $counter * 10;

        $cmds = [
            'bridge'  => ":if ([:len [/interface bridge find name=\"bridge-$iface\"]] > 0) do={ :set res \"OK: Bridge ya existe\" } else={ /interface bridge add name=\"bridge-$iface\"; /interface bridge port add bridge=\"bridge-$iface\" interface=\"$iface\"; :set res \"OK: Bridge creado\" };",
            'address' => ":if ([:len [/ip address find where interface=\"bridge-$iface\"]] > 0) do={ :set res \"OK: IP ya configurada\" } else={ /ip address add address=192.168.$segmento.1/24 interface=\"bridge-$iface\"; :set res \"OK: IP asignada\" };",
            'pool'    => ":if ([:len [/ip pool find name=\"pool-$iface\"]] > 0) do={ :set res \"OK: Pool ya existe\" } else={ /ip pool add name=\"pool-$iface\" ranges=192.168.$segmento.10-192.168.$segmento.250; :set res \"OK: Pool creado\" };",
            'dhcp'    => ":if ([:len [/ip dhcp-server find interface=\"bridge-$iface\"]] > 0) do={ :set res \"OK: DHCP ya existe\" } else={ /ip dhcp-server add address-pool=\"pool-$iface\" interface=\"bridge-$iface\" name=\"srv-$iface\" disabled=no; /ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8; :set res \"OK: DHCP activo\" };",
            'dhcp' => "{
                :if ([:len [/ip dhcp-server find interface=\"bridge-$iface\"]] > 0) do={ 
                    :set res \"OK: DHCP ya existe\" 
                } else={ 
                    :do {
                        /ip dhcp-server add address-pool=\"pool-$iface\" interface=\"bridge-$iface\" name=\"srv-$iface\" disabled=no; 
                        /ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8; 
                        :set res \"OK: DHCP activo\" 
                    } on-error={ :set res \"Error: Interfaz bridge-$iface no lista\" }
                };
            }"
        ];

        $this->currentTid = "CFG" . rand(10,99) . time();
        $this->intentos = 0;
        $script = ":local res \"\"; :local m \"$mac\"; :local t \"{$this->currentTid}\"; " .
                  ":do { {$cmds[$tarea]} } on-error={ :set res \"Error en ejecucion\" }; " .
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
        } catch (\Exception $e) {
            Log::error("Error Bridge: " . $e->getMessage());
        }
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
                
                if ($this->isWaitingResponse) {
                    $this->interfaces = array_filter(explode(',', $data));
                    $this->isWaitingResponse = false;
                } elseif ($this->activeTask) {
                    $iface = $this->activeTask['iface'];
                    $tarea = $this->activeTask['tarea'];
                    
                    if (strpos($data, 'OK') !== false) {
                        $this->taskStatus[$iface][$tarea] = 'success';
                        $this->taskResult[$iface][$tarea] = $data;
                        $this->activeTask = null;
                        // Sigue a la siguiente tarea solo si esta fue exitosa
                        $this->procesarSiguienteEnCola();
                    } else {
                        $this->taskStatus[$iface][$tarea] = 'error';
                        $this->taskResult[$iface][$tarea] = $data;
                        $this->activeTask = null;
                        // DETENER SCAN AUTO: Vaciamos la cola porque hubo un fallo
                        $this->queue = [];
                    }
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
            $this->taskResult[$this->activeTask['iface']][$this->activeTask['tarea']] = 'Timeout: Sin respuesta';
            $this->activeTask = null;
            // DETENER SCAN AUTO en caso de timeout
            $this->queue = [];
        }
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.conf-detallada', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::where('user_id', $this->selectedAliado)->get()
        ]);
    }
}