<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class Interfaces extends Component
{
    public $router_id;
    public $selectedAliado = null;
    public $routerStatus = []; 
    public $interfaces = [];
    public $logs = [];
    public $isConfiguring = false;
    public $esperandoRespuesta = false;
    public $currentTid = null;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') abort(403);
        $this->refreshStatus();
    }

    public function refreshStatus()
    {
        try {
            $response = Http::timeout(5)->get("{$this->bridgeUrl}/api/routers-online");
            if ($response->successful()) {
                $onlineRouters = $response->json();
                $activeMacs = collect($onlineRouters)->map(fn($item) => strtoupper(trim($item['mac'])))->toArray();
                
                $routers = Router::all();
                $this->routerStatus = [];
                foreach ($routers as $r) {
                    $macLimpia = strtoupper(trim($r->macAddress));
                    $this->routerStatus[$r->id] = in_array($macLimpia, $activeMacs);
                }
            }
        } catch (\Exception $e) {
            $this->routerStatus = [];
        }
    }

    // Al cambiar aliado solo reseteamos selección
    public function updatedSelectedAliado()
    {
        $this->router_id = null;
        $this->interfaces = [];
        $this->refreshStatus();
    }

    // Único disparador de lectura manual
    public function cargarInterfaces()
    {
        $this->validate([
            'router_id' => 'required'
        ], [
            'router_id.required' => 'Seleccione un router de la lista.'
        ]);
        
        $this->refreshStatus();

        if (!($this->routerStatus[$this->router_id] ?? false)) {
            $this->logs[] = "❌ El router no respondió al estado Online.";
            return;
        }

        $this->interfaces = [];
        $this->logs[] = "📡 Petición enviada: Consultando interfaces...";
        $this->enviarComando("/interface print detail without-paging", "LECTURA");
    }

    public function toggleInterface($name, $status)
    {
        $accion = ($status == 'true' || $status == 'yes') ? 'enable' : 'disable';
        $this->logs[] = "⚙️ Ejecutando: $accion en $name";
        $this->enviarComando("/interface $accion [find name=\"$name\"]", "ACCION");
    }

    private function enviarComando($cmd, $tipo)
    {
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->currentTid = "INT" . time() . rand(10, 99);
        $this->isConfiguring = true;

        $script = "{ :local r \"OK\"; :do { ".$cmd." } on-error={ :set r \"ERR\" }; /tool fetch url=\"$this->bridgeUrl/post-result?mac=$mac&tid=$this->currentTid&data=\$r\" keep-result=no }";
        $scriptLimpio = trim(preg_replace('/\s+/', ' ', $script));

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody($scriptLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
            
            $this->esperandoRespuesta = true;
        } catch (\Exception $e) {
            $this->logs[] = "❌ Error de comunicación con el Bridge.";
            $this->isConfiguring = false;
        }
    }

    public function checkStatus()
    {
        if (!$this->esperandoRespuesta) return;

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);
            
            if ($res->successful() && $res->json('status') === 'ready') {
                $this->esperandoRespuesta = false;
                $this->isConfiguring = false;
                $this->logs[] = "✅ Respuesta recibida del Router.";
                // Aquí el Bridge debería retornar el array de interfaces en 'data'
                if($res->json('data') && is_array($res->json('data'))) {
                    $this->interfaces = $res->json('data');
                }
            }
        } catch (\Exception $e) { }
    }

    public function render()
    {
        $routersOnline = Router::when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
            ->get()
            ->filter(fn($r) => $this->routerStatus[$r->id] ?? false);

        return view('livewire.mikrotik.herramientas.interfaces', [
            'routers' => $routersOnline,
            'aliados' => User::where('role', 'aliado')->get()
        ]);
    }
}