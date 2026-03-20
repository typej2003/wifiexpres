<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use App\Models\HotspotVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ConfigurarRemoto extends Component
{
    public $router_id;
    public $version_id;
    public $soporte_user = 'soporte';
    public $soporte_pass;
    public $selectedAliado = null;
    public $routerStatus = [];
    public $logs = [];
    public $isConfiguring = false;
    
    public $progreso = 0;
    public $abortar = false;
    public $esperandoRespuesta = false;
    public $currentTid = null;
    public $currentStepIndex = 0;
    public $pasos = [];
    public $intentos = 0;
    public $reintentosRealizados = 0; // Nueva variable para controlar reintentos

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

    public function ejecutarResetSelectivo()
    {
  
        $this->validate([
            'router_id' => 'required',
            'soporte_user' => 'required|min:4',
            'soporte_pass' => 'required|min:4'
        ]);
        
        $this->iniciarProceso("⚠️ Iniciando Limpieza Selectiva...", [
            ['cmd' => '/ip hotspot user remove [find where name!="default-trial"]', 'desc' => '1. Borrando usuarios Hotspot'],
            ['cmd' => '/ip hotspot remove [find]', 'desc' => '2. Borrando Servidores Hotspot'],
            ['cmd' => '/ip hotspot profile remove [find where name!="default"]', 'desc' => '3. Borrando Perfiles Hotspot'],
            ['cmd' => '/ip hotspot user profile remove [find where name!="default"]', 'desc' => '4. Borrando Perfiles de Usuario'],
            ['cmd' => '/ip dhcp-server remove [find]', 'desc' => '5. Borrando Servidores DHCP'],
            ['cmd' => '/ip dhcp-server network remove [find]', 'desc' => '6. Borrando Redes DHCP'],
            ['cmd' => '/ip pool remove [find]', 'desc' => '7. Borrando Pools de IP'],
            ['cmd' => '/interface bridge port remove [find where interface!="ether1"]', 'desc' => '8. Liberando puertos'],
            ['cmd' => '/interface bridge remove [find]', 'desc' => '9. Eliminando Bridges'],
            ['cmd' => '/ip address remove [find where interface!="ether1"]', 'desc' => '10. Limpiando IPs'],
            ['cmd' => '/ip hotspot walled-garden remove [find]', 'desc' => '11. Limpiando Walled Garden'],
            ['cmd' => '/ip hotspot walled-garden ip remove [find]', 'desc' => '12. Limpiando Walled Garden IP'],
            ['cmd' => '/ip firewall nat remove [find where comment~"Hotspot" or comment~"masq"]', 'desc' => '13. Limpiando NAT'],
            ['cmd' => '/user remove [find name!="jose" and name!="admin" and name!="'.$this->soporte_user.'"]', 'desc' => '14. Limpiando usuarios sistema'],
        ]);
    }

    public function ejecutarConfiguracion()
    {
        $this->validate([
            'router_id' => 'required',
            'version_id' => 'required',
            'soporte_user' => 'required|min:4',
            'soporte_pass' => 'required|min:4'
        ]);
        
        $router = Router::findOrFail($this->router_id);
        $downloadUrl = "https://wifiexpres.com/api/portal-download/" . $this->version_id;
        $identity = $router->identity ?? 'MikroTik';

        $this->iniciarProceso("🚀 Provisión Universal (Modo Fuerza Bruta)", [
            // 1. USUARIO Y LIMPIEZA
            ['cmd' => "/user add name=\"$this->soporte_user\" password=\"$this->soporte_pass\" group=full", 'desc' => "1. Usuario maestro"],
            ['cmd' => '/ip hotspot remove [find]; /ip hotspot profile remove [find where name!="default"]; /ip hotspot user profile remove [find where name!="default"]; /ip dhcp-server remove [find]; /ip address remove [find where interface!="ether1"]; /interface bridge port remove [find]; /interface bridge remove [find]; /ip pool remove [find]', 'desc' => '2. Limpieza total'],

            // 3. ESTRUCTURA DE BRIDGES
            ['cmd' => '/interface bridge add name=bridge-wifi', 'desc' => '3a. Bridge WiFi'],
            ['cmd' => ':foreach i in=[/interface ethernet find where name!="ether1"] do={ /interface bridge add name=("bridge-" . [/interface ethernet get $i name]) }', 'desc' => '3b. Bridges Ethernet'],
            
            // 4. PUERTOS
            ['cmd' => ':foreach i in=[/interface ethernet find where name!="ether1"] do={ :local ename [/interface ethernet get $i name]; /interface bridge port add bridge=("bridge-" . $ename) interface=$ename }', 'desc' => '4. Puertos físicos'],
            
            // 5. WIFI
            ['cmd' => ':foreach i in=[/interface wifi find] do={ /interface wifi set $i configuration.mode=ap configuration.ssid="'.$identity.'" disabled=no; /interface bridge port add bridge=bridge-wifi interface=[/interface wifi get $i name] }', 'desc' => '5. Configurando WiFi'],
            
            // 6. RED BASE E INTERNET
            ['cmd' => '/ip dns set allow-remote-requests=yes servers=8.8.8.8; /ip dhcp-client add interface=ether1 disabled=no; /ip firewall nat add action=masquerade chain=srcnat out-interface=ether1', 'desc' => '6. WAN y NAT'],

            // 7. PERFILES DE USUARIO (IMPORTANTES)
            ['cmd' => '/ip hotspot user profile add name="neutro" session-timeout=1s shared-users=1', 'desc' => '7a. Perfil Neutro'],
            ['cmd' => '/ip hotspot user profile add name="cortesia 20min-0" session-timeout=20m shared-users=1', 'desc' => '7b. Perfil Cortesia'],
            ['cmd' => '/ip hotspot user profile add name="conexiongratis" shared-users=1 rate-limit="2M/2M"', 'desc' => '7c. Perfil Gratis'],
            ['cmd' => '/ip hotspot profile add dns-name=wifi.login name=hsprof1 login-by=http-chap,http-pap,trial trial-user-profile=conexiongratis', 'desc' => '7d. Perfil Hotspot'],

            // 8. CREACIÓN DE POOLS (Paso crítico - Separado)
            ['cmd' => '/ip pool add name=pool-wifi ranges=10.0.0.10-10.0.0.250', 'desc' => '8a. Pool WiFi'],
            ['cmd' => ':local counter 2; :foreach i in=[/interface ethernet find where name!="ether1"] do={ /ip pool add name=("pool-" . [/interface ethernet get $i name]) ranges=("192.168." . ($counter * 10) . ".10-192.168." . ($counter * 10) . ".250"); :set counter ($counter + 1); }', 'desc' => '8b. Pools Ethernet'],

            // 9. ADDRESSES
            ['cmd' => '/ip address add address=10.0.0.1/24 interface=bridge-wifi', 'desc' => '9a. IP WiFi'],
            ['cmd' => ':local counter 2; :foreach i in=[/interface ethernet find where name!="ether1"] do={ /ip address add address=("192.168." . ($counter * 10) . ".1/24") interface=("bridge-" . [/interface ethernet get $i name]); :set counter ($counter + 1); }', 'desc' => '9b. IPs Ethernet'],

            // 10. SERVIDORES DHCP
            ['cmd' => '/ip dhcp-server add address-pool=pool-wifi interface=bridge-wifi name=srv-wifi disabled=no; /ip dhcp-server network add address=10.0.0.0/24 gateway=10.0.0.1 dns-server=8.8.8.8', 'desc' => '10a. DHCP WiFi'],
            ['cmd' => ':local counter 2; :foreach i in=[/interface ethernet find where name!="ether1"] do={ :local ename [/interface ethernet get $i name]; /ip dhcp-server add address-pool=("pool-" . $ename) interface=("bridge-" . $ename) name=("srv-" . $ename) disabled=no; /ip dhcp-server network add address=("192.168." . ($counter * 10) . ".0/24") gateway=("192.168." . ($counter * 10) . ".1") dns-server=8.8.8.8; :set counter ($counter + 1); }', 'desc' => '10b. DHCPs Ethernet'],

            // 11. SERVIDORES HOTSPOT (Finalmente, cuando todo lo anterior existe)
            ['cmd' => '/ip hotspot add address-pool=pool-wifi interface=bridge-wifi name=hotspot-wifi profile=hsprof1 disabled=no', 'desc' => '11a. Hotspot WiFi'],
            ['cmd' => ':foreach i in=[/interface ethernet find where name!="ether1"] do={ :local ename [/interface ethernet get $i name]; /ip hotspot add address-pool=("pool-" . $ename) interface=("bridge-" . $ename) name=("hotspot-" . $ename) profile=hsprof1 disabled=no }', 'desc' => '11b. Hotspots Ethernet'],

            // 12. WALLED GARDEN Y PORTAL
            ['cmd' => '/ip hotspot walled-garden add dst-host=wifiexpres.com; /ip hotspot walled-garden ip add dst-address=188.95.113.44', 'desc' => '12. Walled Garden'],
            ['cmd' => '/ip hotspot profile set [find name="hsprof1"] html-directory=hotspot; /tool fetch url="'.$downloadUrl.'" dst-path="hotspot/login.html" check-certificate=no', 'desc' => '13. Portal'],
            ['cmd' => '/system reboot', 'desc' => '14. Reinicio'],
        ]);
    }

    private function iniciarProceso($mensaje, $listaPasos)
    {
        $this->isConfiguring = true;
        $this->abortar = false;
        $this->progreso = 0;
        $this->currentStepIndex = 0;
        $this->pasos = $listaPasos;
        $this->logs = [$mensaje];
        $this->reintentosRealizados = 0;
        $this->enviarSiguienteComando();
    }

    public function enviarSiguienteComando()
    {
        if ($this->abortar || $this->currentStepIndex >= count($this->pasos)) {
            $this->finalizar();
            return;
        }

        $paso = $this->pasos[$this->currentStepIndex];
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->currentTid = "TID" . time() . rand(10, 99);
        $this->intentos = 0;

        $this->logs[] = "📡 Enviando: " . $paso['desc'] . ($this->reintentosRealizados > 0 ? " (Reintento)" : "");

        $script = '{ 
            :local r "OK"; 
            :do { '.$paso['cmd'].' } on-error={ :set r "ERR" };
            /tool fetch url="'.$this->bridgeUrl.'/post-result?mac='.$mac.'&tid='.$this->currentTid.'&data=$r" keep-result=no 
        }';
        
        $scriptLimpio = trim(preg_replace('/\s+/', ' ', $script));

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody($scriptLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            $this->esperandoRespuesta = true;
        } catch (\Exception $e) {
            $this->logs[] = "❌ Error de conexión.";
            $this->finalizar();
        }
    }

    public function checkStatus()
    {
        if (!$this->esperandoRespuesta || $this->abortar) return;

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->intentos++;

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", [
                'mac' => $mac, 
                'tid' => $this->currentTid
            ]);

            if ($res->successful() && $res->json('status') === 'ready') {
                $this->logs[] = "✅ Hecho";
                $this->reintentosRealizados = 0; // Reset de reintentos
                $this->avanzar();
            } elseif ($this->intentos >= 40) { // 40 intentos = ~20-30 seg
                if ($this->reintentosRealizados < 1) {
                    $this->logs[] = "⌛ Reintentando comando...";
                    $this->reintentosRealizados++;
                    $this->esperandoRespuesta = false;
                    $this->enviarSiguienteComando();
                } else {
                    $this->logs[] = "⏭️ Tiempo agotado, saltando...";
                    $this->reintentosRealizados = 0;
                    $this->avanzar();
                }
            }
        } catch (\Exception $e) { }
    }

    private function avanzar()
    {
        $this->esperandoRespuesta = false;
        $this->currentStepIndex++;
        $this->progreso = round(($this->currentStepIndex / count($this->pasos)) * 100);
        
        // Pausa de 2 segundos antes del siguiente comando para dejar respirar al RouterOS
        sleep(2); 
        
        $this->enviarSiguienteComando();
        $this->dispatchBrowserEvent('logUpdated');
    }

    private function finalizar()
    {
        $this->isConfiguring = false;
        $this->esperandoRespuesta = false;
        $this->progreso = 100;
        $this->logs[] = $this->abortar ? "🛑 Proceso abortado." : "🏁 Finalizado con éxito.";
        $this->dispatchBrowserEvent('logUpdated');
    }

    public function detenerProceso() { $this->abortar = true; }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.configurar-remoto', [
            'routers' => Router::query()
                ->when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
                ->get(),
            'aliados' => User::where('role', 'aliado')->get(),
            'versiones' => HotspotVersion::all()
        ]);
    }
}