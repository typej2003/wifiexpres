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
        
        $version = HotspotVersion::findOrFail($this->version_id);
        $router = Router::findOrFail($this->router_id);
        $downloadUrl = "https://wifiexpres.com/api/portal-download/" . $this->version_id;
        $identity = $router->identity ?? 'MikroTik';

        $this->iniciarProceso("🚀 Iniciando Provisión Dual (LAN/WiFi)...", [
            // 1. Configuración de Usuario y Seguridad
            ['cmd' => ":if ([:len [/user find name=\"$this->soporte_user\"]]=0) do={/user add name=\"$this->soporte_user\" password=\"$this->soporte_pass\" group=full} else={/user set [find name=\"$this->soporte_user\"] password=\"$this->soporte_pass\" group=full}", 'desc' => "Configurando usuario maestro"],
            
            // 2. Bridges
            ['cmd' => ':if ([:len [/interface bridge find name="bridge-lan"]]=0) do={/interface bridge add name=bridge-lan}', 'desc' => 'Creando Bridge LAN'],
            ['cmd' => ':if ([:len [/interface bridge find name="bridge-wifi"]]=0) do={/interface bridge add name=bridge-wifi}', 'desc' => 'Creando Bridge WiFi'],
            
            // 3. Puertos (Ethernet 2-5 a LAN)
            ['cmd' => ':foreach i in=[/interface ethernet find where default-name~"ether[2-5]"] do={ :local n [/interface ethernet get $i name]; :if ([:len [/interface bridge port find interface=$n]]=0) do={/interface bridge port add bridge=bridge-lan interface=$n} }', 'desc' => 'Asignando puertos ether a LAN'],
            
            // 4. WiFi (Detección dinámica de nombres wifi1/wifi2 o wlan1/wlan2)
            ['cmd' => ':foreach i in=[/interface wifi find] do={ :local n [/interface wifi get $i default-name]; :local sid ("'.$identity.'-" . $n); /interface wifi set $i configuration.mode=ap configuration.ssid=$sid disabled=no; :if ([:len [/interface bridge port find interface=[/interface wifi get $i name]]]=0) do={/interface bridge port add bridge=bridge-wifi interface=[/interface wifi get $i name]} }', 'desc' => 'Configurando WiFi y Puertos Inalámbricos'],
            
            // 5. IPs e Internet
            ['cmd' => '/ip dhcp-client add interface=ether1 disabled=no comment="WAN"', 'desc' => 'DHCP Client en ether1'],
            ['cmd' => '/ip dns set allow-remote-requests=yes servers=8.8.8.8,8.8.4.4', 'desc' => 'Configurando DNS'],
            ['cmd' => ':if ([:len [/ip address find interface="bridge-lan"]]=0) do={/ip address add address=192.168.10.1/24 interface=bridge-lan}', 'desc' => 'IP Bridge LAN'],
            ['cmd' => ':if ([:len [/ip address find interface="bridge-wifi"]]=0) do={/ip address add address=10.0.0.1/24 interface=bridge-wifi}', 'desc' => 'IP Bridge WiFi'],
            
            // 6. Pools y DHCP Servers
            ['cmd' => ':if ([:len [/ip pool find name="pool-lan"]]=0) do={/ip pool add name=pool-lan ranges=192.168.10.10-192.168.10.250}', 'desc' => 'Pool LAN'],
            ['cmd' => ':if ([:len [/ip pool find name="pool-wifi"]]=0) do={/ip pool add name=pool-wifi ranges=10.0.0.10-10.0.0.250}', 'desc' => 'Pool WiFi'],
            ['cmd' => ':if ([:len [/ip dhcp-server find interface="bridge-lan"]]=0) do={/ip dhcp-server add address-pool=pool-lan disabled=no interface=bridge-lan name=srv-lan}', 'desc' => 'DHCP Server LAN'],
            ['cmd' => ':if ([:len [/ip dhcp-server find interface="bridge-wifi"]]=0) do={/ip dhcp-server add address-pool=pool-wifi disabled=no interface=bridge-wifi name=srv-wifi}', 'desc' => 'DHCP Server WiFi'],
            ['cmd' => '/ip dhcp-server network remove [find]; /ip dhcp-server network add address=192.168.10.0/24 dns-server=8.8.8.8 gateway=192.168.10.1; /ip dhcp-server network add address=10.0.0.0/24 dns-server=8.8.8.8 gateway=10.0.0.1', 'desc' => 'Redes DHCP'],
            
            // 7. NAT y Firewall
            ['cmd' => ':if ([:len [/ip firewall nat find comment="Masquerade-Hotspot"]]=0) do={/ip firewall nat add chain=srcnat out-interface=ether1 action=masquerade comment="Masquerade-Hotspot"}', 'desc' => 'NAT Masquerade'],
            ['cmd' => ':if ([:len [/ip firewall nat find comment="Masquerade-Bridge"]] = 0) do={ /ip firewall nat add chain=srcnat dst-address=188.95.113.44 action=masquerade comment="Masquerade-Bridge" place-before=0 }', 'desc' => 'Priorizando NAT Bridge'],

            // 8. Hotspot Config
            ['cmd' => ':if ([:len [/ip hotspot profile find name="hsprof1"]]=0) do={/ip hotspot profile add dns-name=wifi.login hotspot-address=10.0.0.1 name=hsprof1 login-by=http-chap,http-pap,trial}', 'desc' => 'Perfil Hotspot (PAP/CHAP)'],
            ['cmd' => ':if ([:len [/ip hotspot find name="hotspot-wifi"]]=0) do={/ip hotspot add address-pool=pool-wifi disabled=no interface=bridge-wifi name=hotspot-wifi profile=hsprof1}', 'desc' => 'Hotspot en WiFi'],
            ['cmd' => ':if ([:len [/ip hotspot find name="hotspot-lan"]]=0) do={/ip hotspot add address-pool=pool-lan disabled=no interface=bridge-lan name=hotspot-lan profile=hsprof1}', 'desc' => 'Hotspot en LAN'],
            ['cmd' => ':if ([:len [/ip hotspot user find name="admin"]]=0) do={/ip hotspot user add name=admin password=admin123}', 'desc' => 'Usuario Admin Hotspot'],

            // 9. Walled Garden (Dominios y Pasarelas)
            ['cmd' => '/ip hotspot walled-garden { remove [find]; add dst-host=wifiexpres.com; add dst-host=*.wifiexpres.com; add dst-host=*.biopagobdv.com action=allow; add dst-host=*.banvenez.com action=allow; add dst-host=biopago.banvenez.com action=allow; add dst-host=fcm.googleapis.com action=allow; add dst-host=mtalk.google.com action=allow; add dst-host=*.push.apple.com action=allow; add dst-host=188.95.113.44 }', 'desc' => 'WG: Dominios y Bridge'],
            
            // 10. Walled Garden IP
            ['cmd' => '/ip hotspot walled-garden ip { remove [find]; add dst-address=188.95.113.44 dst-port=3000 protocol=tcp comment="Acceso Bridge Nodejs"; add dst-address=190.217.7.106 comment="Pasarela BDV"; add dst-address=190.202.148.187 comment="Pasarela BDV"; add dst-port=5228-5230 protocol=tcp action=accept comment="Push Google"; add dst-port=5223 protocol=tcp action=accept comment="Push Apple" }', 'desc' => 'WG IP: Puertos y Pasarelas'],

            // 11. Portal y Cierre
            ['cmd' => '/tool fetch url="'.$downloadUrl.'" dst-path="hotspot/login.html" check-certificate=no', 'desc' => 'Descargando Portal Personalizado'],
            ['cmd' => ':if ("'.$this->soporte_user.'" != "admin") do={ /user remove [find name="admin"] }', 'desc' => 'Removiendo admin por seguridad'],
            ['cmd' => '/system reboot', 'desc' => 'Reinicio final'],
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