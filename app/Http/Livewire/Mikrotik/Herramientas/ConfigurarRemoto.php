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
    
    // Propiedades para el progreso
    public $progreso = 0;
    public $abortar = false;

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
        } catch (\Exception $e) { $this->routerStatus = []; }
    }

    public function detenerProceso()
    {
        $this->abortar = true;
        $this->logs[] = "🛑 Petición de interrupción recibida...";
    }

    private function enviarComandoUnico($comando, $descripcion, $mac)
    {
        $tid = "OP" . rand(1000, 9999);
        $this->logs[] = "📡 Enviando: $descripcion...";
        
        // Construimos el mini-script con reporte inmediato
        $script = "
            :local m \"$mac\"; :local t \"$tid\";
            :do { 
                $comando; 
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no;
            } on-error={ 
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"ERROR\" keep-result=no;
            };
        ";

        // Limpiar espacios para el Bridge
        $scriptLimpio = trim(preg_replace('/\s+/', ' ', $script));

        try {
            $envio = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($scriptLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$envio->successful()) return "FAIL_COMM";

            // Espera de hasta 60 segundos
            for ($i = 0; $i < 60; $i++) {
                if ($this->abortar) return "ABORTED";
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    return $res->json('data'); // "OK" o "ERROR"
                }
            }
            return "TIMEOUT";
        } catch (\Exception $e) {
            return "EXCEPTION";
        }
    }

    public function ejecutarResetSelectivo()
    {
        $this->validate(['router_id' => 'required']);
        $this->isConfiguring = true;
        $this->abortar = false;
        $this->progreso = 0;
        $this->logs = [];

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);

        // DEFINICIÓN DE PASOS COMANDO POR COMANDO
        $pasos = [
            ['cmd' => '/ip hotspot user remove [find]', 'desc' => 'Eliminando Usuarios Hotspot'],
            ['cmd' => '/ip hotspot remove [find]', 'desc' => 'Eliminando Servidores Hotspot'],
            ['cmd' => '/ip hotspot walled-garden remove [find]', 'desc' => 'Limpiando Walled Garden'],
            ['cmd' => '/ip dhcp-server remove [find]', 'desc' => 'Eliminando Servidores DHCP'],
            ['cmd' => '/ip pool remove [find]', 'desc' => 'Limpiando Pools de IP'],
            ['cmd' => '/interface bridge port remove [find where interface!="ether1"]', 'desc' => 'Desconectando puertos del Bridge'],
            ['cmd' => '/interface bridge remove [find]', 'desc' => 'Eliminando Bridges'],
            ['cmd' => '/ip address remove [find where interface!="ether1"]', 'desc' => 'Limpiando Direcciones IP'],
            ['cmd' => '/user remove [find name!="jose" and name!="admin"]', 'desc' => 'Limpiando Usuarios del Sistema'],
        ];

        $total = count($pasos);

        foreach ($pasos as $index => $paso) {
            if ($this->abortar) {
                $this->logs[] = "⛔ Proceso abortado por el usuario.";
                break;
            }

            $resultado = $this->enviarComandoUnico($paso['cmd'], $paso['desc'], $mac);

            if ($resultado === "OK") {
                $this->logs[] = "✅ " . $paso['desc'] . " finalizado.";
            } elseif ($resultado === "TIMEOUT") {
                $this->logs[] = "⌛ " . $paso['desc'] . " no respondió (Timeout), continuando...";
            } else {
                $this->logs[] = "⚠️ " . $paso['desc'] . " falló o ya estaba limpio ($resultado).";
            }

            $this->progreso = round((($index + 1) / total) * 100);
        }

        $this->logs[] = "🏁 Fin del procedimiento.";
        $this->isConfiguring = false;
        $this->progreso = 100;
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.configurar-remoto', [
            'routers' => Router::all(),
            'aliados' => User::where('role', 'aliado')->get()
        ]);
    }
}