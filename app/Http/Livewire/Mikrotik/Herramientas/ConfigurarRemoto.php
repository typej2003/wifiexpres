<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ConfigurarRemoto extends Component
{
    public $router_id;
    public $selectedAliado = null;
    public $routerStatus = [];
    public $logs = [];
    public $isConfiguring = false;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado.');
        }
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

    public function emitirAlSocket($comando, $mac, $tid)
    {
        try {
            $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
            return Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($comandoLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command")
                ->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function esperarRespuesta($mac, $tid)
    {
        for ($i = 0; $i < 35; $i++) {
            sleep(1);
            try {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    return $res->json('data');
                }
            } catch (\Exception $e) { }
        }
        return null;
    }

    public function ejecutarResetSelectivo()
    {
        $this->validate(['router_id' => 'required']);
        $this->logs = []; // Limpiar logs antes de empezar
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);
        $tid = "RESET" . time();

        $this->isConfiguring = true;
        $this->logs[] = "⚠️ Iniciando limpieza de Bridges en: " . ($router->identity ?? $mac);

        // Script ultra simplificado: Borra Puertos, luego Bridges
        $script = "
            :local m \"$mac\"; :local t \"$tid\"; :local r \"RES:\";
            
            # 1. Borrar Puertos
            :do { 
                /interface bridge port remove [find]; 
                :set r (\$r . \"Puertos_Eliminados,\") 
            } on-error={ :set r (\$r . \"Error_Puertos,\") };

            # 2. Borrar Bridges
            :do { 
                /interface bridge remove [find]; 
                :set r (\$r . \"Bridges_Eliminados\") 
            } on-error={ :set r (\$r . \"Error_Bridges\") };

            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$r keep-result=no;
        ";

        if ($this->emitirAlSocket($script, $mac, $tid)) {
            $this->logs[] = "📡 Comando enviado. Esperando confirmación...";
            $res = $this->esperarRespuesta($mac, $tid);
            
            if ($res) {
                $limpio = str_replace("RES:", "", $res);
                $pasos = explode(',', $limpio);
                foreach ($pasos as $paso) {
                    $item = trim($paso);
                    if (empty($item)) continue;
                    
                    if (str_contains($item, 'Error')) {
                        $this->logs[] = "🔸 Info: $item";
                    } else {
                        $this->logs[] = "🔹 Success: $item";
                    }
                }
            } else {
                $this->logs[] = "❌ Error: Timeout (El router no respondió).";
            }
        } else {
            $this->logs[] = "❌ Error: No se pudo conectar con el Bridge.";
        }

        $this->isConfiguring = false;
    }

    public function render()
    {
        $aliados = User::where('role', 'aliado')->get();
        $query = Router::query();
        if ($this->selectedAliado) { $query->where('user_id', $this->selectedAliado); }

        return view('livewire.mikrotik.herramientas.configurar-remoto', [
            'routers' => $query->get(),
            'aliados' => $aliados
        ]);
    }
}