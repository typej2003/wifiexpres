<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ConfigurarRemoto extends Component
{
    public $router_id;
    public $selectedAliado = null;
    public $routerStatus = [];
    public $logs = [];
    public $isConfiguring = false;
    
    // Propiedades para el progreso y abortar
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
        $this->logs[] = "🛑 Petición de interrupción recibida. Deteniendo...";
    }

    private function enviarPaso($comando, $descripcion, $mac)
    {
        $tid = "TASK" . rand(1000, 9999);
        $this->logs[] = "📡 Enviando: $descripcion...";
        
        $script = "
            :local m \"$mac\"; :local t \"$tid\";
            :do { 
                $comando; 
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"OK\" keep-result=no;
            } on-error={ 
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"ERROR\" keep-result=no;
            };
        ";

        $scriptLimpio = trim(preg_replace('/\s+/', ' ', $script));

        try {
            $envio = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($scriptLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$envio->successful()) return "FALLO_CONEXION";

            for ($i = 0; $i < 60; $i++) {
                if ($this->abortar) return "ABORTADO";
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    return $res->json('data');
                }
            }
            return "TIMEOUT";
        } catch (\Exception $e) { return "EXCEPTION"; }
    }

    private function orquestarProceso($pasos, $mac)
    {
        $totalPasos = count($pasos);
        foreach ($pasos as $index => $paso) {
            if ($this->abortar) break;

            $resultado = $this->enviarPaso($paso['cmd'], $paso['desc'], $mac);

            if ($resultado === "OK") {
                $this->logs[] = "✅ " . $paso['desc'] . " finalizado.";
            } elseif ($resultado === "TIMEOUT") {
                $this->logs[] = "⌛ " . $paso['desc'] . " excedió el tiempo, saltando...";
            } else {
                $this->logs[] = "⚠️ " . $paso['desc'] . " con estado: $resultado";
            }

            $this->progreso = round((($index + 1) / $totalPasos) * 100);
        }
        
        $this->logs[] = $this->abortar ? "⛔ Proceso cancelado por el usuario." : "🏁 Tarea finalizada con éxito.";
        $this->isConfiguring = false;
    }

    public function ejecutarConfiguracion()
    {
        $this->validate(['router_id' => 'required']);
        $this->prepararVariables("🚀 Iniciando Provisión Remota...");
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);

        $pasos = [
            ['cmd' => '/user add name="soporte" password="123" group=full', 'desc' => 'Creando usuario de soporte'],
            ['cmd' => '/interface bridge add name=bridge-lan', 'desc' => 'Creando Bridge LAN'],
            ['cmd' => ':foreach i in=[/interface ethernet find where name!="ether1"] do={ :local n [/interface ethernet get $i name]; /interface bridge port add bridge=bridge-lan interface=$n }', 'desc' => 'Asignando puertos al Bridge'],
            ['cmd' => '/ip address add address=192.168.88.1/24 interface=bridge-lan', 'desc' => 'Asignando IP 192.168.88.1'],
            ['cmd' => '/ip pool add name=dhcp_pool1 ranges=192.168.88.10-192.168.88.254', 'desc' => 'Creando Pool DHCP'],
            ['cmd' => '/ip dhcp-server add address-pool=dhcp_pool1 disabled=no interface=bridge-lan name=dhcp-remoto', 'desc' => 'Activando DHCP Server'],
            ['cmd' => '/ip dhcp-server network add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=8.8.8.8', 'desc' => 'Configurando Red DHCP'],
        ];

        $this->orquestarProceso($pasos, $mac);
    }

    public function ejecutarResetSelectivo()
    {
        $this->validate(['router_id' => 'required']);
        $this->prepararVariables("⚠️ Iniciando Limpieza Selectiva...");

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);

        $pasos = [
            ['cmd' => '/ip hotspot user remove [find]', 'desc' => 'Borrando usuarios Hotspot'],
            ['cmd' => '/ip hotspot remove [find]', 'desc' => 'Borrando servidores Hotspot'],
            ['cmd' => '/ip hotspot walled-garden remove [find]', 'desc' => 'Limpiando Walled Garden'],
            ['cmd' => '/ip dhcp-server remove [find]', 'desc' => 'Borrando servidores DHCP'],
            ['cmd' => '/ip pool remove [find]', 'desc' => 'Borrando Pools de IP'],
            ['cmd' => '/interface bridge port remove [find where interface!="ether1"]', 'desc' => 'Liberando puertos'],
            ['cmd' => '/interface bridge remove [find]', 'desc' => 'Eliminando Bridges'],
            ['cmd' => '/ip address remove [find where interface!="ether1"]', 'desc' => 'Limpiando IPs'],
            ['cmd' => '/user remove [find name!="jose" and name!="admin"]', 'desc' => 'Limpiando usuarios sistema'],
        ];

        $this->orquestarProceso($pasos, $mac);
    }

    private function prepararVariables($inicioMsg)
    {
        $this->isConfiguring = true;
        $this->abortar = false;
        $this->progreso = 0;
        $this->logs = [$inicioMsg];
    }

    public function render()
    {
        $aliados = User::where('role', 'aliado')->get();
        $query = Router::query();
        if ($this->selectedAliado) {
            $query->where('user_id', $this->selectedAliado);
        }

        return view('livewire.mikrotik.herramientas.configurar-remoto', [
            'routers' => $query->get(),
            'aliados' => $aliados,
        ]);
    }
}