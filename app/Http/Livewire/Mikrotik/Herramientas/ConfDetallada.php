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
    public $isSearching = false;
    public $isWaitingResponse = false;
    public $showRetry = false; 
    
    // Estados específicos por puerto y por tarea
    // Ejemplo: $status['ether2']['bridge'] = 'success'
    public $taskStatus = [];
    public $logs = [];
    
    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado.');
        }
    }

    public function updatedRouterId($value)
    {
        if ($value) { $this->descubrirInterfaces(); }
    }

    public function descubrirInterfaces()
    {
        if (!$this->router_id) return;
        $this->isSearching = true;
        $this->isWaitingResponse = true;
        $this->interfaces = [];
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "DISC" . time();

        $script = '{
            :local ifaces "";
            :foreach i in=[/interface find where type="ether" or type="wlan" or type="wifi"] do={
                :set ifaces ($ifaces . [/interface get $i name] . ",");
            };
            /tool fetch url="'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$tid.'&data=$ifaces" keep-result=no
        }';

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            $this->isSearching = false;
            $this->esperarRespuestaInterfaces($mac, $tid);
        } catch (\Exception $e) { $this->showRetry = true; }
    }

    private function esperarRespuestaInterfaces($mac, $tid)
    {
        $intentos = 0;
        while ($intentos < 12) {
            sleep(1);
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
            if ($res->successful() && $res->json('status') === 'ready') {
                $this->interfaces = array_filter(explode(',', $res->json('data')));
                $this->isWaitingResponse = false;
                return;
            }
            $intentos++;
        }
        $this->isWaitingResponse = false;
        $this->showRetry = true;
    }

    /**
     * Lógica genérica para ejecutar una tarea específica
     */
    public function ejecutarTarea($iface, $index, $tarea)
    {
        $this->taskStatus[$iface][$tarea] = 'loading';
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $segmento = ($index + 2) * 10;

        // Definición de comandos por tarea
        $comandos = [
            'bridge'  => "/interface bridge add name=bridge-$iface; /interface bridge port add bridge=bridge-$iface interface=$iface",
            'address' => "/ip address add address=192.168.$segmento.1/24 interface=bridge-$iface",
            'pool'    => "/ip pool add name=pool-$iface ranges=192.168.$segmento.10-192.168.$segmento.250",
            'dhcp'    => "/ip dhcp-server add address-pool=pool-$iface interface=bridge-$iface name=srv-$iface disabled=no; /ip dhcp-server network add address=192.168.$segmento.0/24 gateway=192.168.$segmento.1 dns-server=8.8.8.8",
            'hotspot' => "/ip hotspot add address-pool=pool-$iface interface=bridge-$iface name=hotspot-$iface profile=hsprof1 disabled=no"
        ];

        $cmd = $comandos[$tarea];
        $tid = "TASK" . time();

        try {
            $script = '{ :do { '.$cmd.' } on-error={ :log info "Error en '.$tarea.'" } }';
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if ($response->successful()) {
                $this->taskStatus[$iface][$tarea] = 'success';
            } else {
                $this->taskStatus[$iface][$tarea] = 'error';
            }
        } catch (\Exception $e) {
            $this->taskStatus[$iface][$tarea] = 'error';
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