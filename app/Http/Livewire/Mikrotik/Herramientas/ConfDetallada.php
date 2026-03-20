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
    
    // Estados de descubrimiento
    public $interfaces = []; 
    public $isWaitingResponse = false; 
    public $currentTid = null;
    public $intentos = 0;
    public $showRetry = false;
    public $logs = [];

    // Estados de tareas por puerto
    public $taskStatus = []; // Almacena 'loading', 'success', 'error'
    public $activeTask = null; // Para saber qué tarea está en polling

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado.');
        }
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

        $this->interfaces = [];
        $this->intentos = 0;
        $this->showRetry = false;
        $this->isWaitingResponse = true;
        $this->currentTid = "DISC" . time();
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        $script = '{
            :local ifaces "";
            :foreach i in=[/interface find where type="ether" or type="wlan" or type="wifi"] do={
                :set ifaces ($ifaces . [/interface get $i name] . ",");
            };
            /tool fetch url="'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$this->currentTid.'&data=$ifaces" keep-result=no
        }';

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
            
            $this->logs['global'] = "Buscando interfaces...";
        } catch (\Exception $e) {
            $this->isWaitingResponse = false;
            $this->showRetry = true;
        }
    }

    /**
     * Ejecuta una tarea específica (Bridge, IP, etc)
     */
    public function ejecutarTarea($iface, $index, $tarea)
    {
        $this->taskStatus[$iface][$tarea] = 'loading';
        $this->activeTask = ['iface' => $iface, 'tarea' => $tarea];
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $segmento = ($index + 2) * 10;
        $this->currentTid = "TASK" . time() . rand(10,99);
        $this->intentos = 0;

        $comandos = [
            'bridge'  => "/interface bridge add name=bridge-$iface; /interface bridge port add bridge=bridge-$iface interface=$iface",
            'address' => "/ip address add address=192.168.$segmento.1/24 interface=bridge-$iface",
            'pool'    => "/ip pool add name=pool-$iface ranges=192.168.$segmento.10-192.168.$segmento.250",
            'dhcp'    => "/ip dhcp-server add address-pool=pool-$iface interface=bridge-$iface name=srv-$iface disabled=no; /ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8",
            'hotspot' => "/ip hotspot add address-pool=pool-$iface interface=bridge-$iface name=hotspot-$iface profile=hsprof1 disabled=no"
        ];

        $script = '{ :local r "OK"; :do { '.$comandos[$tarea].' } on-error={ :set r "ERR" }; /tool fetch url="'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$this->currentTid.'&data=$r" keep-result=no }';

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
        } catch (\Exception $e) {
            $this->taskStatus[$iface][$tarea] = 'error';
        }
    }

    /**
     * Polling unificado para Descubrimiento y Tareas
     */
    public function checkStatus()
    {
        if (!$this->isWaitingResponse && !$this->activeTask) return;

        $this->intentos++;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);

            if ($res->successful() && $res->json('status') === 'ready') {
                $data = $res->json('data');

                // Si estábamos descubriendo interfaces
                if ($this->isWaitingResponse) {
                    $this->interfaces = array_filter(explode(',', $data));
                    $this->isWaitingResponse = false;
                } 
                // Si estábamos ejecutando una tarea de puerto
                elseif ($this->activeTask) {
                    $iface = $this->activeTask['iface'];
                    $tarea = $this->activeTask['tarea'];
                    $this->taskStatus[$iface][$tarea] = ($data === 'OK') ? 'success' : 'error';
                    $this->activeTask = null;
                }
                $this->intentos = 0;
            } elseif ($this->intentos >= 20) {
                $this->resetStatesOnTimeout();
            }
        } catch (\Exception $e) {}
    }

    private function resetStatesOnTimeout() {
        if ($this->isWaitingResponse) $this->showRetry = true;
        if ($this->activeTask) $this->taskStatus[$this->activeTask['iface']][$this->activeTask['tarea']] = 'error';
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