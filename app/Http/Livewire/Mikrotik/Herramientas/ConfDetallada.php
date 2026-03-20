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
    public $isSearching = false;       // Cuando se envía el comando
    public $isWaitingResponse = false; // Mientras se espera el resultado del Bridge
    public $showRetry = false; 
    
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
        $this->reset(['router_id', 'interfaces', 'status', 'logs', 'isSearching', 'isWaitingResponse', 'showRetry']);
    }

    public function updatedRouterId($value)
    {
        if ($value) {
            $this->descubrirInterfaces();
        }
    }

    public function descubrirInterfaces()
    {
        if (!$this->router_id) return;

        $this->isSearching = true;
        $this->isWaitingResponse = false;
        $this->showRetry = false;
        $this->interfaces = [];
        $this->logs['global'] = "🚀 Enviando comando de descubrimiento...";

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "DISC" . time();

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

            $this->isSearching = false;
            $this->isWaitingResponse = true; // ACTIVAMOS EL LOADING DE ESPERA
            $this->logs['global'] = "📡 Comando entregado al Bridge. Esperando respuesta del MikroTik...";
            
            $this->esperarRespuestaInterfaces($mac, $tid);
        } catch (\Exception $e) {
            $this->logs['global'] = "❌ Error al contactar el Bridge.";
            $this->isSearching = false;
            $this->showRetry = true;
        }
    }

    private function esperarRespuestaInterfaces($mac, $tid)
    {
        $intentos = 0;
        while ($intentos < 15) {
            // Livewire no renderiza durante el sleep a menos que uses polling de Livewire,
            // pero para esta lógica síncrona, el usuario verá el estado cargando hasta el final.
            sleep(1);
            try {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                
                if ($res->successful() && $res->json('status') === 'ready') {
                    $data = $res->json('data'); 
                    
                    if (empty($data) || $data == "ERR") {
                        $this->logs['global'] = "⚠️ Respuesta vacía o error en el script del router.";
                        $this->showRetry = true;
                        $this->isWaitingResponse = false;
                        return;
                    }

                    $this->interfaces = array_filter(explode(',', $data));
                    foreach ($this->interfaces as $iface) {
                        $this->status[$iface] = 'idle';
                    }
                    
                    $this->isWaitingResponse = false;
                    $this->logs['global'] = "✅ Interfaces detectadas.";
                    return;
                }
            } catch (\Exception $e) {}
            $intentos++;
        }

        $this->isWaitingResponse = false;
        $this->showRetry = true;
        $this->logs['global'] = "🛑 Tiempo agotado (Timeout). El router no respondió.";
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