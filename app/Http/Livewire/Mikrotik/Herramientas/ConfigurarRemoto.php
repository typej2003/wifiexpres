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
            abort(403, 'Solo el administrador puede acceder a esta herramienta.');
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
            Log::error("Error al refrescar status: " . $e->getMessage());
        }
    }

    public function emitirAlSocket($comando, $mac, $tid)
    {
        try {
            $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($comandoLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Error Bridge: " . $e->getMessage());
            return false;
        }
    }

    public function esperarRespuesta($mac, $tid)
    {
        for ($i = 0; $i < 30; $i++) {
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
        if (!($this->routerStatus[$this->router_id] ?? false)) {
            $this->logs[] = "❌ Error: El router seleccionado no está conectado.";
            return;
        }

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);
        $tid = "CONF" . time();
        $this->isConfiguring = true;
        $this->logs[] = "Iniciando configuración remota para: " . ($router->identity ?? $mac);

        // SCRIPT MEJORADO: Cada bloque es independiente para que un error no detenga el resto
        $script = "
            :local m \"$mac\"; :local t \"$tid\"; :local msg \"RES:\";
            :do { /user add name=\"soporte\" password=\"123\" group=full comment=\"Acceso remoto automatizado\"; :set msg (\$msg . \"UserOK,\") } on-error={ :set msg (\$msg . \"UserFail/Exists,\") };
            :do { /interface bridge add name=bridge-lan comment=\"Bridge principal creado desde Panel\"; :set msg (\$msg . \"BridgeOK,\") } on-error={ :set msg (\$msg . \"BridgeFail/Exists,\") };
            :do { 
                :foreach i in=[/interface ethernet find where name!=\"ether1\"] do={
                    :local ethName [/interface ethernet get \$i name];
                    :do { /interface bridge port add bridge=bridge-lan interface=\$ethName } on-error={};
                };
                :set msg (\$msg . \"PortsOK,\");
            } on-error={ :set msg (\$msg . \"PortsError,\") };
            :do { /ip address add address=192.168.88.1/24 interface=bridge-lan network=192.168.88.0; :set msg (\$msg . \"AddrOK,\") } on-error={ :set msg (\$msg . \"AddrFail,\") };
            :do { /ip pool add name=dhcp_pool1 ranges=192.168.88.10-192.168.88.254; :set msg (\$msg . \"PoolOK,\") } on-error={ :set msg (\$msg . \"PoolFail,\") };
            :do { /ip dhcp-server add address-pool=dhcp_pool1 disabled=no interface=bridge-lan name=dhcp-remoto; :set msg (\$msg . \"DHCPOK,\") } on-error={ :set msg (\$msg . \"DHCPFail,\") };
            :do { /ip dhcp-server network add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=8.8.8.8,8.8.4.4; :set msg (\$msg . \"NetOK\") } on-error={ :set msg (\$msg . \"NetFail\") };
            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$msg keep-result=no;
        ";

        if ($this->emitirAlSocket($script, $mac, $tid)) {
            $this->logs[] = "📡 Comando enviado. Analizando ejecución...";
            $resultado = $this->esperarRespuesta($mac, $tid);
            if ($resultado) {
                $partes = explode(',', str_replace('RES:', '', $resultado));
                foreach ($partes as $p) { $this->logs[] = "⚙️ Step: $p"; }
                $this->logs[] = "✅ Proceso finalizado.";
            } else {
                $this->logs[] = "❌ TIMEOUT: El router no devolvió reporte.";
            }
        }
        $this->isConfiguring = false;
        $this->refreshStatus();
    }

    public function ejecutarResetSelectivo()
    {
        $this->validate(['router_id' => 'required']);
        if (!($this->routerStatus[$this->router_id] ?? false)) {
            $this->logs[] = "❌ Error: Router Offline.";
            return;
        }

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper($router->macAddress);
        $tid = "RESET" . time();
        $this->isConfiguring = true;
        $this->logs[] = "⚠️ Iniciando RESET SELECTIVO en: " . ($router->identity ?? $mac);

        // RESET SECTORIZADO CON INDEPENDENCIA DE COMANDOS
        $script = "
            :local m \"$mac\"; :local t \"$tid\"; :local r \"CLEAN:\";
            :do { /ip hotspot user remove [find]; :set r (\$r . \"HS_Users,\") } on-error={};
            :do { /ip hotspot user profile remove [find where name!=\"default\"]; :set r (\$r . \"HS_Prof,\") } on-error={};
            :do { /ip hotspot remove [find]; /ip hotspot server profile remove [find where name!=\"default\"]; :set r (\$r . \"HS_Srv,\") } on-error={};
            :do { /ip hotspot walled-garden remove [find]; /ip hotspot walled-garden ip remove [find]; /ip hotspot ip-binding remove [find]; :set r (\$r . \"Warden,\") } on-error={};
            :do { /ip dhcp-server remove [find]; /ip dhcp-server network remove [find]; /ip pool remove [find]; :set r (\$r . \"DHCP_Pool,\") } on-error={};
            :do { /interface bridge port remove [find]; /interface bridge remove [find]; :set r (\$r . \"Bridges,\") } on-error={};
            :do { /ip address remove [find where interface!=\"ether1\"]; :set r (\$r . \"IPs,\") } on-error={};
            :do { /ip firewall nat remove [find where out-interface!=\"ether1\"]; :set r (\$r . \"NAT,\") } on-error={};
            :do { /queue simple remove [find]; :set r (\$r . \"Queues\") } on-error={};
            /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$r keep-result=no;
        ";

        if ($this->emitirAlSocket($script, $mac, $tid)) {
            $this->logs[] = "📡 Comando de Reset enviado...";
            $resultado = $this->esperarRespuesta($mac, $tid);
            if ($resultado) {
                $partes = explode(',', str_replace('CLEAN:', '', $resultado));
                foreach ($partes as $p) { $this->logs[] = "🗑️ Borrado: $p"; }
                $this->logs[] = "✨ El router ha sido limpiado manteniendo la gestión remota.";
            } else {
                $this->logs[] = "❌ Error: Sin respuesta del router tras el reset.";
            }
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