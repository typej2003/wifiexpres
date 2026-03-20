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
        $this->reset(['router_id', 'interfaces', 'taskStatus', 'taskResult', 'isWaitingResponse', 'showRetry']);
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

        $this->enviarScript($mac, $script);
    }

    public function ejecutarTarea($iface, $index, $tarea)
    {
        $this->taskStatus[$iface][$tarea] = 'loading';
        $this->taskResult[$iface][$tarea] = 'Enviando a MikroTik...';
        $this->activeTask = ['iface' => $iface, 'tarea' => $tarea];
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $counter = $index + 1; 
        $segmento = $counter * 10;

        $comandos = [
            'bridge'  => "/interface bridge add name=bridge-$iface; /interface bridge port add bridge=bridge-$iface interface=$iface",
            'address' => "/ip address add address=192.168.$segmento.1/24 interface=bridge-$iface",
            'pool'    => "/ip pool add name=pool-$iface ranges=192.168.$segmento.10-192.168.$segmento.250",
            'dhcp'    => "/ip dhcp-server add address-pool=pool-$iface interface=bridge-$iface name=srv-$iface disabled=no; /ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8",
            'hotspot' => "/ip hotspot add address-pool=pool-$iface interface=bridge-$iface name=hotspot-$iface profile=hsprof1 disabled=no"
        ];

        $cmd = $comandos[$tarea];
        $this->currentTid = "T" . rand(100,999) . time();
        $this->intentos = 0;

        // ESTRATEGIA: Scheduler para asegurar que la red esté lista antes de reportar
        $script = '{
            :local m "OK: Listo";
            :do { '.$cmd.' } on-error={ :set m "Error o Ya existente" };
            /system scheduler add name="'.$this->currentTid.'" start-time=startup interval=0s on-event="/tool fetch url=\"'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$this->currentTid.'&data=$m\" keep-result=no; /system scheduler remove [find name=\"'.$this->currentTid.'\"]"
            /system scheduler set "'.$this->currentTid.'" start-time=([/system clock get time] + 00:00:03)
        }';

        $this->enviarScript($mac, $script);
    }

    private function enviarScript($mac, $script)
    {
        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
        } catch (\Exception $e) {
            if ($this->activeTask) {
                $this->taskStatus[$this->activeTask['iface']][$this->activeTask['tarea']] = 'error';
            }
        }
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
                $data = $res->json('data');

                if ($this->isWaitingResponse) {
                    $this->interfaces = array_filter(explode(',', $data));
                    $this->isWaitingResponse = false;
                } 
                elseif ($this->activeTask) {
                    $iface = $this->activeTask['iface'];
                    $tarea = $this->activeTask['tarea'];
                    $this->taskStatus[$iface][$tarea] = (strpos($data, 'OK') !== false) ? 'success' : 'error';
                    $this->taskResult[$iface][$tarea] = $data;
                    $this->activeTask = null;
                }
                $this->intentos = 0;
            } elseif ($this->intentos >= 45) { // Damos más tiempo para el Scheduler
                $this->handleTimeout();
            }
        } catch (\Exception $e) {}
    }

    private function handleTimeout()
    {
        if ($this->isWaitingResponse) $this->showRetry = true;
        if ($this->activeTask) {
            $this->taskStatus[$this->activeTask['iface']][$this->activeTask['tarea']] = 'error';
            $this->taskResult[$this->activeTask['iface']][$this->activeTask['tarea']] = 'TIMEOUT: Sin respuesta del Bridge';
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