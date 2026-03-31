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
    public $routerStatus = []; // Almacena quién está online
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

    /**
     * Consulta al Bridge qué routers están activos en este momento
     */
    public function refreshStatus()
    {
        try {
            $response = Http::timeout(5)->get("{$this->bridgeUrl}/api/routers-online");
            if ($response->successful()) {
                $onlineRouters = $response->json();
                // Extraemos solo las MACs activas y las normalizamos
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
            $this->logs[] = "⚠️ No se pudo sincronizar el estado de los routers.";
        }
    }

    /**
     * Acción al cambiar el aliado: Resetea el router seleccionado
     */
    public function updatedSelectedAliado()
    {
        $this->router_id = null;
        $this->interfaces = [];
        $this->refreshStatus();
    }

    public function cargarInterfaces()
    {
        $this->validate(['router_id' => 'required']);
        
        // Verificación de seguridad extra por si el estado cambió
        $this->refreshStatus();
        if (!($this->routerStatus[$this->router_id] ?? false)) {
            $this->logs[] = "❌ El router seleccionado se ha desconectado.";
            return;
        }

        $this->logs[] = "📡 Solicitando interfaces al router...";
        $this->enviarComando("/interface print detail without-paging", "LECTURA");
    }

    public function toggleInterface($name, $status)
    {
        // Si status es 'true' (deshabilitado), la acción es 'enable', y viceversa
        $accion = ($status == 'true' || $status == 'yes') ? 'enable' : 'disable';
        $desc = ($accion == 'disable') ? "Deshabilitando" : "Habilitando";
        
        $this->logs[] = "⚙️ $desc interfaz: $name";
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
            $this->logs[] = "❌ Error de conexión con el Bridge.";
            $this->isConfiguring = false;
        }
    }

    public function checkStatus()
    {
        if (!$this->esperandoRespuesta) return;

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", [
                'mac' => $mac, 
                'tid' => $this->currentTid
            ]);
            
            if ($res->successful() && $res->json('status') === 'ready') {
                $this->esperandoRespuesta = false;
                $this->isConfiguring = false;
                $this->logs[] = "✅ Tarea finalizada con éxito.";
                
                // Refrescamos la lista para confirmar el cambio visualmente
                $this->cargarInterfaces(); 
            }
        } catch (\Exception $e) { }
    }

    public function render()
    {
        // FILTRADO DINÁMICO:
        // 1. Filtra por el aliado seleccionado.
        // 2. Solo incluye routers cuyo ID esté marcado como 'true' en routerStatus (Online).
        $routersActivos = Router::when($this->selectedAliado, function($query) {
                return $query->where('user_id', $this->selectedAliado);
            })
            ->get()
            ->filter(function($r) {
                return $this->routerStatus[$r->id] ?? false;
            });

        return view('livewire.mikrotik.herramientas.interfaces', [
            'routers' => $routersActivos,
            'aliados' => User::where('role', 'aliado')->get()
        ]);
    }
}