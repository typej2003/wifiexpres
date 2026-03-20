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
    
    // Estados principales
    public $interfaces = []; 
    public $isWaitingResponse = false; 
    public $currentTid = null;
    public $intentos = 0;
    public $showRetry = false;

    // Estados de ejecución
    public $taskStatus = []; // 'loading', 'success', 'error'
    public $taskResult = []; // Mensaje detallado del router
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
        $this->reset(['router_id', 'interfaces', 'taskStatus', 'taskResult', 'isWaitingResponse', 'showRetry']);
    }

    public function updatedRouterId($value)
    {
        if ($value) {
            $this->iniciarDescubrimiento();
        }
    }

    /**
     * PASO 1: Descubrir interfaces
     */
    public function iniciarDescubrimiento()
    {
        if (!$this->router_id) return;

        $this->reset(['interfaces', 'taskStatus', 'taskResult', 'intentos', 'showRetry']);
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
        } catch (\Exception $e) {
            $this->isWaitingResponse = false;
            $this->showRetry = true;
        }
    }

    /**
     * PASO 2: Configurar aspecto específico y capturar respuesta
     */
    public function ejecutarTarea($iface, $index, $tarea)
    {
        $this->taskStatus[$iface][$tarea] = 'loading';
        $this->taskResult[$iface][$tarea] = 'Enviando comando...';
        $this->activeTask = ['iface' => $iface, 'tarea' => $tarea];
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $segmento = ($index + 2) * 10;
        $this->currentTid = "TASK-" . $tarea . "-" . time();
        $this->intentos = 0;

        $comandos = [
            'bridge'  => "/interface bridge add name=bridge-$iface; /interface bridge port add bridge=bridge-$iface interface=$iface",
            'address' => "/ip address add address=192.168.$segmento.1/24 interface=bridge-$iface",
            'pool'    => "/ip pool add name=pool-$iface ranges=192.168.$segmento.10-192.168.$segmento.250",
            'dhcp'    => "/ip dhcp-server add address-pool=pool-$iface interface=bridge-$iface name=srv-$iface disabled=no; /ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8",
            'hotspot' => "/ip hotspot add address-pool=pool-$iface interface=bridge-$iface name=hotspot-$iface profile=default disabled=no"
        ];

        // Script que intenta ejecutar y devuelve el resultado o el error
        $script = '{ 
            :local msg "OK: Operacion exitosa"; 
            :do { 
                '.$comandos[$tarea].' 
            } on-error={ :set msg "Error: No se pudo aplicar el comando" }; 
            /tool fetch url="'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$this->currentTid.'&data=$msg" keep-result=no 
        }';

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
            
            $this->taskResult[$iface][$tarea] = 'Esperando confirmación...';
        } catch (\Exception $e) {
            $this->taskStatus[$iface][$tarea] = 'error';
            $this->taskResult[$iface][$tarea] = 'Error de conexión con el Bridge';
        }
    }

    /**
     * POLLING UNIFICADO
     */
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
                $data = $res->json('data');

                if ($this->isWaitingResponse) {
                    $this->interfaces = array_filter(explode(',', $data));
                    $this->isWaitingResponse = false;
                } 
                elseif ($this->activeTask) {
                    $iface = $this->activeTask['iface'];
                    $tarea = $this->activeTask['tarea'];
                    
                    if (strpos($data, 'OK') !== false) {
                        $this->taskStatus[$iface][$tarea] = 'success';
                    } else {
                        $this->taskStatus[$iface][$tarea] = 'error';
                    }
                    $this->taskResult[$iface][$tarea] = $data;
                    $this->activeTask = null;
                }
                $this->intentos = 0;
            } elseif ($this->intentos >= 20) {
                $this->handleTimeout();
            }
        } catch (\Exception $e) {}
    }

    private function handleTimeout()
    {
        if ($this->isWaitingResponse) $this->showRetry = true;
        if ($this->activeTask) {
            $iface = $this->activeTask['iface'];
            $tarea = $this->activeTask['tarea'];
            $this->taskStatus[$iface][$tarea] = 'error';
            $this->taskResult[$iface][$tarea] = 'Timeout: Sin respuesta del router';
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