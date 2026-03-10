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
            // Reemplazamos saltos de línea y múltiples espacios para que el bridge lo reciba bien
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
        // Aumentamos a 40 segundos por si el router tarda en procesar
        for ($i = 0; $i < 40; $i++) {
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
        $this->logs = []; 
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);
        $tid = "RESET" . time();

        $this->isConfiguring = true;
        $this->logs[] = "⚠️ Iniciando limpieza SEGURA en: " . ($router->identity ?? $mac);

        // SCRIPT CON PROTECCIÓN DE CONECTIVIDAD
        // No borramos puertos que sean ether1 para no perder el bridge-socket
        $script = "
            :local m \"$mac\"; :local t \"$tid\"; :local r \"RES:\";
            
            :do { 
                # Borrar puertos EXCEPTO ether1
                /interface bridge port remove [find where interface!=\"ether1\"]; 
                :set r (\$r . \"Puertos_Limpios,\") 
            } on-error={ :set r (\$r . \"Err_Puertos,\") };

            :do { 
                # Borrar bridges EXCEPTO si tienen a ether1 (por si acaso)
                :foreach b in=[/interface bridge find] do={
                    :local bName [/interface bridge get \$b name];
                    :local hasEther1 [/interface bridge port find where bridge=\$bName and interface=\"ether1\"];
                    :if ([:len \$hasEther1] = 0) do={
                        /interface bridge remove \$b;
                    }
                };
                :set r (\$r . \"Bridges_Limpios\") 
            } on-error={ :set r (\$r . \"Err_Bridges\") };

            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$r keep-result=no;
        ";

        if ($this->emitirAlSocket($script, $mac, $tid)) {
            $this->logs[] = "📡 Comando enviado. Procesando en MikroTik...";
            $res = $this->esperarRespuesta($mac, $tid);
            
            if ($res) {
                $limpio = str_replace("RES:", "", $res);
                $pasos = explode(',', $limpio);
                foreach ($pasos as $paso) {
                    $item = trim($paso);
                    if (empty($item)) continue;
                    $this->logs[] = (str_contains($item, 'Err')) ? "🔸 Info: $item" : "🔹 Success: $item";
                }
                $this->logs[] = "✅ Limpieza completada sin perder conexión.";
            } else {
                $this->logs[] = "❌ Error: Timeout. El equipo pudo haber perdido conexión o el script falló.";
            }
        } else {
            $this->logs[] = "❌ Error: Fallo al contactar el Bridge.";
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
            'aliados' => $aliados,
        ]);
    }
}