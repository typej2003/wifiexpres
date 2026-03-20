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
    
    public $interfaces = []; 
    public $isWaitingResponse = false; 
    public $currentTid = null;
    public $intentos = 0;
    public $showRetry = false;
    public $logs = [];

    // Estados detallados: $taskStatus['ether2']['bridge'] = 'success' | 'missing' | 'loading'
    public $taskStatus = []; 
    public $activeTask = null; 

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function updatedRouterId($value)
    {
        if ($value) { $this->iniciarDescubrimiento(); }
    }

    public function iniciarDescubrimiento()
    {
        if (!$this->router_id) return;
        $this->reset(['interfaces', 'taskStatus', 'intentos', 'showRetry']);
        $this->isWaitingResponse = true;
        $this->currentTid = "DISC" . time();
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        // Script para listar interfaces y luego pedir el estado de cada una
        $script = '{
            :local ifaces "";
            :foreach i in=[/interface find where type="ether" or type="wlan" or type="wifi"] do={
                :set ifaces ($ifaces . [/interface get $i name] . ",");
            };
            /tool fetch url="'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$this->currentTid.'&data=$ifaces" keep-result=no
        }';

        $this->enviarScript($mac, $script);
    }

    /**
     * PASO SECUENCIAL: Pregunta al MikroTik si ya tiene configurado cada aspecto
     */
    public function consultarEstadoInterfaz($iface)
    {
        $this->taskStatus[$iface]['loading_all'] = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->currentTid = "CHECK-" . $iface . "-" . time();
        $this->activeTask = ['iface' => $iface, 'type' => 'status_check'];

        // Script que verifica la existencia de cada componente para esa interfaz
        $script = '{
            :local b [/interface bridge port find where interface="'.$iface.'"];
            :local a [/ip address find where interface~"'.$iface.'"];
            :local p [/ip pool find where name~"'.$iface.'"];
            :local d [/ip dhcp-server find where interface~"'.$iface.'"];
            :local h [/ip hotspot find where interface~"'.$iface.'"];
            
            :local res ( "b=" . ([:len $b]>0) . ",a=" . ([:len $a]>0) . ",p=" . ([:len $p]>0) . ",d=" . ([:len $d]>0) . ",h=" . ([:len $h]>0) );
            /tool fetch url="'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$this->currentTid.'&data=$res" keep-result=no
        }';

        $this->enviarScript($mac, $script);
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
                $data = $res->json('data');

                if ($this->isWaitingResponse) {
                    $this->interfaces = array_filter(explode(',', $data));
                    $this->isWaitingResponse = false;
                    // Al encontrar interfaces, consultamos el estado de la primera automáticamente (opcional)
                } 
                elseif ($this->activeTask && $this->activeTask['type'] === 'status_check') {
                    $iface = $this->activeTask['iface'];
                    $this->parseStatus($iface, $data);
                    $this->activeTask = null;
                }
                elseif ($this->activeTask && $this->activeTask['type'] === 'config') {
                    $this->taskStatus[$this->activeTask['iface']][$this->activeTask['tarea']] = ($data === 'OK') ? 'success' : 'error';
                    $this->activeTask = null;
                }
                $this->intentos = 0;
            } elseif ($this->intentos >= 20) {
                $this->isWaitingResponse = false;
                $this->activeTask = null;
                $this->showRetry = true;
            }
        } catch (\Exception $e) {}
    }

    private function parseStatus($iface, $data)
    {
        // Data viene como: b=1,a=0,p=1...
        $parts = explode(',', $data);
        foreach($parts as $p) {
            list($key, $val) = explode('=', $p);
            $map = ['b'=>'bridge', 'a'=>'address', 'p'=>'pool', 'd'=>'dhcp', 'h'=>'hotspot'];
            $this->taskStatus[$iface][$map[$key]] = ($val == "1" || $val == "true") ? 'success' : 'missing';
        }
        $this->taskStatus[$iface]['loading_all'] = false;
    }

    private function enviarScript($mac, $script) {
        Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
            ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
            ->post("{$this->bridgeUrl}/set-command");
    }

    // El método ejecutarTarea se mantiene similar al anterior...
    public function ejecutarTarea($iface, $index, $tarea) { /* ... código anterior ... */ }

    public function render() {
        return view('livewire.mikrotik.herramientas.conf-detallada', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::where('user_id', $this->selectedAliado)->get()
        ]);
    }
}