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
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody(trim(preg_replace('/\s+/', ' ', $script)), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if ($response->successful()) {
                $this->isWaitingResponse = true; // Aquí activamos el polling en la vista
                $this->logs['global'] = "Comando enviado. Esperando respuesta del MikroTik...";
            }
        } catch (\Exception $e) {
            $this->showRetry = true;
        }
    }

    /**
     * Esta función es llamada por wire:poll cada segundo desde la vista
     */
    public function checkDiscoveryStatus()
    {
        if (!$this->isWaitingResponse) return;

        $this->intentos++;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", [
                'mac' => $mac,
                'tid' => $this->currentTid
            ]);

            if ($res->successful() && $res->json('status') === 'ready') {
                $data = $res->json('data');
                $this->interfaces = array_filter(explode(',', $data));
                $this->isWaitingResponse = false;
                $this->currentTid = null;
                $this->logs['global'] = "✅ Conectado: " . count($this->interfaces) . " interfaces halladas.";
            } elseif ($this->intentos >= 20) { // Timeout a los 20 segundos
                $this->isWaitingResponse = false;
                $this->showRetry = true;
                $this->logs['global'] = "❌ El router no respondió a tiempo.";
            }
        } catch (\Exception $e) {
            // Error silencioso en el polling
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