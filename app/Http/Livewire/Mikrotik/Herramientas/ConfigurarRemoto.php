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

        $this->iniciarProceso("🚀 Provisión Universal (Restaurando Servidores y IPs)", [
            // 1. USUARIO MAESTRO
            ['cmd' => ":if ([:len [/user find name=\"$this->soporte_user\"]]=0) do={/user add name=\"$this->soporte_user\" password=\"$this->soporte_pass\" group=full} else={/user set [find name=\"$this->soporte_user\"] password=\"$this->soporte_pass\" group=full}", 'desc' => "1. Usuario maestro"],
            
            // 2. LIMPIEZA (Corregida para no dejar rastro de IPs que bloqueen la asignación)
            ['cmd' => '/ip hotspot remove [find]; /ip dhcp-server remove [find]; /ip address remove [find where interface!="ether1"]; /interface bridge port remove [find]; /interface bridge remove [find]; /ip pool remove [find]', 'desc' => '2. Limpieza previa'],

            // 3. CREACIÓN DE BRIDGES INDEPENDIENTES
            ['cmd' => '/interface bridge add name=bridge-wifi; :foreach i in=[/interface ethernet find where name!="ether1"] do={ :local ename [/interface ethernet get $i name]; /interface bridge add name=("bridge-" . $ename) }', 'desc' => '3. Creando puentes'],
            
            // 4. ASIGNACIÓN DE PUERTOS
            ['cmd' => ':foreach i in=[/interface ethernet find where name!="ether1"] do={ :local ename [/interface ethernet get $i name]; /interface bridge port add bridge=("bridge-" . $ename) interface=$ename }', 'desc' => '4. Asignando puertos físicos'],
            
            // 5. WIFI
            ['cmd' => ':foreach i in=[/interface wifi find] do={ :local n [/interface wifi get $i default-name]; /interface wifi set $i configuration.mode=ap configuration.ssid=("'.$identity.'-" . $n) configuration.country="Venezuela" disabled=no; /interface bridge port add bridge=bridge-wifi interface=[/interface wifi get $i name] }', 'desc' => '5. Configurando WiFi'],
            
            // 6. INTERNET Y DNS (CORRECCIÓN: use-peer-dns=no para que funcionen los planes)
            ['cmd' => '/ip dns set allow-remote-requests=yes servers=8.8.8.8,8.8.4.4; /ip dhcp-client add interface=ether1 disabled=no use-peer-dns=no comment="WAN"', 'desc' => '6. WAN y DNS Google'],
            
            // 7. IPs DINÁMICAS (Tu lógica original)
            ['cmd' => '/ip address add address=10.0.0.1/24 interface=bridge-wifi network=10.0.0.0; :local counter 2; :foreach i in=[/interface ethernet find where name!="ether1"] do={ :local ename [/interface ethernet get $i name]; /ip address add address=("192.168." . ($counter * 10) . ".1/24") interface=("bridge-" . $ename) network=("192.168." . ($counter * 10) . ".0"); :set counter ($counter + 1) }', 'desc' => '7. IPs por segmento'],
            
            // 8. POOLS Y DHCP SERVERS (Tu lógica original)
            ['cmd' => '/ip pool add name=pool-wifi ranges=10.0.0.10-10.0.0.250; /ip dhcp-server add address-pool=pool-wifi disabled=no interface=bridge-wifi name="srv-wifi"; /ip dhcp-server network add address=10.0.0.0/24 dns-server=8.8.8.8 gateway=10.0.0.1; :local counter 2; :foreach i in=[/interface ethernet find where name!="ether1"] do={ :local ename [/interface ethernet get $i name]; :local ipnet ("192.168." . ($counter * 10) . ".0/24"); :local gw ("192.168." . ($counter * 10) . ".1"); :local rng ("192.168." . ($counter * 10) . ".10-192.168." . ($counter * 10) . ".250"); /ip pool add name=("pool-" . $ename) ranges=$rng; /ip dhcp-server add address-pool=("pool-" . $ename) disabled=no interface=("bridge-" . $ename) name=("srv-" . $ename); /ip dhcp-server network add address=$ipnet dns-server=8.8.8.8 gateway=$gw; :set counter ($counter + 1) }', 'desc' => '8. DHCP Servers'],
            
            // 9. NAT Y PERFILES
            ['cmd' => '/ip firewall nat add action=masquerade chain=srcnat out-interface=ether1 comment="NAT-General"; /ip hotspot user profile add name="neutro" session-timeout=1s shared-users=1; /ip hotspot user profile add name="cortesia 20min-0" session-timeout=20m keepalive-timeout=none shared-users=1 status-autorefresh=1m', 'desc' => '9. NAT y Perfiles'],

            // 10. HOTSPOTS MÚLTIPLES (Tu lógica original)
            ['cmd' => '/ip hotspot profile add dns-name=wifi.login hotspot-address=10.0.0.1 name=hsprof1 login-by=http-chap,http-pap,trial; /ip hotspot add address-pool=pool-wifi disabled=no interface=bridge-wifi name="hotspot-wifi" profile=hsprof1; :foreach i in=[/interface ethernet find where name!="ether1"] do={ :local ename [/interface ethernet get $i name]; /ip hotspot add address-pool=("pool-" . $ename) disabled=no interface=("bridge-" . $ename) name=("hotspot-" . $ename) profile=hsprof1 }; /ip hotspot user add name=admin password=admin123', 'desc' => '10. Hotspots'],

            // 11. WALLED GARDEN (Dominios API)
            ['cmd' => '/ip hotspot walled-garden { remove [find]; add dst-host=wifiexpres.com; add dst-host=*.wifiexpres.com; add dst-host=*.biopagobdv.com; add dst-host=*.banvenez.com; add dst-host=biopago.banvenez.com; add dst-host=fcm.googleapis.com; add dst-host=fcm-xmpp.googleapis.com; add dst-host=mtalk.google.com; add dst-host=*.push.apple.com; add dst-host=*.push.apple.com.akadns.net; add dst-host=appleid.apple.com; add dst-host=188.95.113.44 }', 'desc' => '11. Walled Garden'],

            // 12. WALLED GARDEN IP (Pasarelas + Push + DNS Bypass para Planes)
            ['cmd' => '/ip hotspot walled-garden ip { remove [find]; add dst-address=188.95.113.44; add dst-address=190.217.7.106; add dst-address=190.217.7.229; add dst-address=200.11.243.174; add dst-address=190.202.148.187; add action=accept dst-port=5228-5230 protocol=tcp; add action=accept dst-port=5223 protocol=tcp; add action=accept dst-port=53 protocol=udp; add action=accept dst-port=53 protocol=tcp }', 'desc' => '12. Walled Garden IP'],

            // 13. PORTAL Y REBOOT
            ['cmd' => '/ip hotspot profile set [find name="hsprof1"] html-directory=hotspot; /tool fetch url="'.$downloadUrl.'" dst-path="hotspot/login.html" check-certificate=no', 'desc' => '13. Descargando Portal'],
            ['cmd' => '/system reboot', 'desc' => '14. Reiniciando equipo'],
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