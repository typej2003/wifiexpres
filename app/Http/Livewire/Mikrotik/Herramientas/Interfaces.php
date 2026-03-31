<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class Interfaces extends Component
{
    public $router_id, $selectedAliado, $currentTid;
    public $routerStatus = [];
    public $interfaces = [];
    public $logs = [];
    public $loading = false;
    public $esperandoRespuesta = false;
    public $bridgeUrl = "http://188.95.113.44:3000";

    public function mount() {
        if (Auth::user()->role !== 'admin') abort(403);
        $this->refreshStatus();
    }

    public function refreshStatus() {
        try {
            $response = Http::timeout(3)->get("{$this->bridgeUrl}/api/routers-online");
            if ($response->successful()) {
                $activeMacs = collect($response->json())->map(fn($item) => strtoupper(trim($item['mac'])))->toArray();
                $this->routerStatus = Router::all()->mapWithKeys(fn($r) => [$r->id => in_array(strtoupper(trim($r->macAddress)), $activeMacs)])->toArray();
            }
        } catch (\Exception $e) { $this->routerStatus = []; }
    }

    public function cargarInterfaces() {
        $this->validate(['router_id' => 'required']);
        
        // --- LIMPIEZA DE LOGS ANTERIORES ---
        $this->logs = []; 
        $this->interfaces = [];
        $this->loading = true;
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->currentTid = "INT" . time();

        $script = ":local r \"\";/interface { :foreach i in=[find] do={:set r (\$r.[get \$i name].\"|\".[get \$i disabled].\"|\".[get \$i type].\"|\".[get \$i mac-address].\",\")}};" .
                  "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$this->currentTid\" http-method=post http-data=\$r keep-result=no;";

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody($script, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            $this->logs[] = "📡 Enviando petición al MikroTik...";
            $this->esperandoRespuesta = true;
        } catch (\Exception $e) {
            $this->logs[] = "❌ Error: " . $e->getMessage();
            $this->loading = false;
        }
    }

    public function checkStatus() {
        if (!$this->esperandoRespuesta) return;

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);
            
            if ($res->successful() && $res->json('status') === 'ready') {
                $this->procesarDatos($res->json('data'));
                $this->esperandoRespuesta = false;
                $this->loading = false;
                $this->logs[] = "✅ Datos recibidos con éxito.";
            }
        } catch (\Exception $e) { }
    }

    protected function procesarDatos($raw) {
        $filas = explode(',', rtrim($raw, ','));
        foreach ($filas as $fila) {
            $d = explode('|', $fila);
            if (count($d) >= 4) {
                $this->interfaces[] = [
                    'name' => $d[0],
                    'disabled' => ($d[1] == "true" || $d[1] == "yes") ? 'true' : 'false',
                    'type' => $d[2],
                    'mac-address' => $d[3]
                ];
            }
        }
    }

    public function toggleInterface($name, $status) {
        $accion = ($status == 'true') ? 'enable' : 'disable';
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->currentTid = "TOG" . time();

        $script = "/interface $accion [find name=\"$name\"];/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$this->currentTid\" http-method=post http-data=\"OK\" keep-result=no;";
        
        $this->logs[] = "⚙️ Solicitando $accion de $name...";
        Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])->withBody($script, 'text/plain')->post("{$this->bridgeUrl}/set-command");
        
        $this->esperandoRespuesta = true;
    }

    public function render() {
        $routers = Router::when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
            ->get()->filter(fn($r) => $this->routerStatus[$r->id] ?? false);

        return view('livewire.mikrotik.herramientas.interfaces', [
            'routers' => $routers,
            'aliados' => User::where('role', 'aliado')->get()
        ]);
    }
}