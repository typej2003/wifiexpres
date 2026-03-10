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

    public function ejecutarConfiguracion()
    {
        $this->validate(['router_id' => 'required']);
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);
        $tid = "CONF" . time();

        $this->isConfiguring = true;
        $this->logs[] = "🚀 Iniciando Provisión en: " . ($router->identity ?? $mac);

        $script = "
            :local m \"$mac\"; :local t \"$tid\"; :local r \"REP:\";
            :do { /user add name=\"soporte\" password=\"123\" group=full; :set r (\$r . \"User_OK,\") } on-error={ :set r (\$r . \"User_Exists/Err,\") };
            :do { /interface bridge add name=bridge-lan; :set r (\$r . \"Bridge_OK,\") } on-error={ :set r (\$r . \"Bridge_Exists/Err,\") };
            :do { 
                :foreach i in=[/interface ethernet find where name!=\"ether1\"] do={
                    :local en [/interface ethernet get \$i name];
                    :do { /interface bridge port add bridge=bridge-lan interface=\$en } on-error={};
                };
                :set r (\$r . \"Ports_Attached,\");
            } on-error={ :set r (\$r . \"Ports_Err,\") };
            :do { /ip address add address=192.168.88.1/24 interface=bridge-lan; :set r (\$r . \"IP_OK,\") } on-error={ :set r (\$r . \"IP_Err,\") };
            :do { /ip pool add name=dhcp_pool1 ranges=192.168.88.10-192.168.88.254; :set r (\$r . \"Pool_OK,\") } on-error={ :set r (\$r . \"Pool_Err,\") };
            :do { /ip dhcp-server add address-pool=dhcp_pool1 disabled=no interface=bridge-lan name=dhcp-remoto; :set r (\$r . \"DHCP_OK,\") } on-error={ :set r (\$r . \"DHCP_Err,\") };
            :do { /ip dhcp-server network add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=8.8.8.8; :set r (\$r . \"Net_OK\") } on-error={ :set r (\$r . \"Net_Err\") };
            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$r keep-result=no;
        ";

        if ($this->emitirAlSocket($script, $mac, $tid)) {
            $res = $this->esperarRespuesta($mac, $tid);
            $this->procesarLogs($res, "REP:");
        }
        $this->isConfiguring = false;
    }

    public function ejecutarResetSelectivo()
    {
        $this->validate(['router_id' => 'required']);
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);
        $tid = "RESET" . time();

        $this->isConfiguring = true;
        $this->logs[] = "⚠️ Ejecutando Limpieza Selectiva en: " . ($router->identity ?? $mac);

        $script = "
            :local m \"$mac\"; :local t \"$tid\"; :local r \"RES:\";
            :do { /ip hotspot user remove [find]; :set r (\$r . \"HS_Users_Del,\") } on-error={ :set r (\$r . \"HS_Users_Skip,\") };
            :do { /ip hotspot server remove [find]; /ip hotspot server profile remove [find where name!=\"default\"]; :set r (\$r . \"HS_Srv_Del,\") } on-error={ :set r (\$r . \"HS_Srv_Skip,\") };
            :do { /ip hotspot ip-binding remove [find]; /ip hotspot walled-garden remove [find]; :set r (\$r . \"Walled_Del,\") } on-error={ :set r (\$r . \"Walled_Skip,\") };
            :do { /ip dhcp-server remove [find]; /ip dhcp-server network remove [find]; /ip pool remove [find]; :set r (\$r . \"DHCP_Pool_Del,\") } on-error={ :set r (\$r . \"DHCP_Skip,\") };
            :do { /interface bridge port remove [find]; /interface bridge remove [find]; :set r (\$r . \"Bridges_Del,\") } on-error={ :set r (\$r . \"Bridges_Skip,\") };
            :do { /ip address remove [find where interface!=\"ether1\"]; :set r (\$r . \"IPs_Other_Del,\") } on-error={ :set r (\$r . \"IPs_Skip,\") };
            :do { /ip firewall nat remove [find where out-interface!=\"ether1\"]; :set r (\$r . \"NAT_Other_Del,\") } on-error={ :set r (\$r . \"NAT_Skip,\") };
            :do { /queue simple remove [find]; :set r (\$r . \"Queues_Del\") } on-error={ :set r (\$r . \"Queues_Skip\") };
            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$r keep-result=no;
        ";

        if ($this->emitirAlSocket($script, $mac, $tid)) {
            $res = $this->esperarRespuesta($mac, $tid);
            $this->procesarLogs($res, "RES:");
        }
        $this->isConfiguring = false;
    }

    private function procesarLogs($resultado, $prefix)
    {
        if ($resultado) {
            $limpio = str_replace($prefix, '', $resultado);
            $pasos = explode(',', $limpio);
            foreach ($pasos as $paso) {
                if (str_contains($paso, 'Err') || str_contains($paso, 'Skip')) {
                    $this->logs[] = "🔸 Info: $paso";
                } else {
                    $this->logs[] = "🔹 Success: $paso";
                }
            }
            $this->logs[] = "🏁 Operación finalizada.";
        } else {
            $this->logs[] = "❌ Error: Sin respuesta del equipo (Timeout).";
        }
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