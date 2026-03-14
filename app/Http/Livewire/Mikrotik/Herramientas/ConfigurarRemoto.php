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
        
        $mensajeSuspendido = "<html><body style='text-align:center;padding-top:50px;'><h1>Servicio Suspendido</h1></body></html>";

        // ORDEN CRÍTICO: De lo más específico a lo más general para evitar errores de dependencia
        $this->iniciarProceso("⚠️ Iniciando Limpieza Selectiva...", [
            ['cmd' => '/ip hotspot user remove [find]', 'desc' => 'Borrando usuarios Hotspot'],
            ['cmd' => '/ip hotspot remove [find]', 'desc' => 'Borrando Servidores Hotspot'],
            ['cmd' => '/ip hotspot profile remove [find where name!="default"]', 'desc' => 'Borrando Perfiles Hotspot'],
            ['cmd' => '/ip hotspot user profile remove [find where name!="default"]', 'desc' => 'Borrando Perfiles de Usuario'],
            ['cmd' => '/ip dhcp-server remove [find]', 'desc' => 'Borrando Servidores DHCP'],
            ['cmd' => '/ip dhcp-server network remove [find]', 'desc' => 'Borrando Redes DHCP'],
            ['cmd' => '/ip pool remove [find]', 'desc' => 'Borrando Pools de IP'],
            ['cmd' => '/interface bridge port remove [find where interface!="ether1"]', 'desc' => 'Liberando puertos'],
            ['cmd' => '/interface bridge remove [find]', 'desc' => 'Eliminando Bridges'],
            ['cmd' => '/ip address remove [find where interface!="ether1"]', 'desc' => 'Limpiando IPs'],
            ['cmd' => '/ip hotspot walled-garden remove [find]', 'desc' => 'Limpiando Walled Garden'],
            ['cmd' => '/ip hotspot walled-garden ip remove [find]', 'desc' => 'Limpiando Walled Garden IP'],
            ['cmd' => '/ip firewall nat remove [find where comment~"Hotspot"]', 'desc' => 'Limpiando NAT de Hotspot'],
            ['cmd' => '/file set "hotspot/login.html" contents="'.$mensajeSuspendido.'"', 'desc' => 'Portal en modo Suspendido'],
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
        $downloadUrl = "https://wifiexpres.com/api/portal-download/" . $this->version_id;

        $this->iniciarProceso("🚀 Iniciando Provisión Remota Full...", [
            ['cmd' => ":if ([:len [/user find name=\"$this->soporte_user\"]]=0) do={/user add name=\"$this->soporte_user\" password=\"$this->soporte_pass\" group=full} else={/user set [find name=\"$this->soporte_user\"] password=\"$this->soporte_pass\" group=full}", 'desc' => "Credenciales Maestro"],
            ['cmd' => ':if ([:len [/interface bridge find name="bridge-lan"]]=0) do={/interface bridge add name=bridge-lan}', 'desc' => 'Creando Bridge LAN'],
            ['cmd' => ':foreach i in=[/interface ethernet find where default-name!="ether1"] do={ :local n [/interface ethernet get $i name]; :if ([:len [/interface bridge port find interface=$n]]=0) do={/interface bridge port add bridge=bridge-lan interface=$n} }', 'desc' => 'Habilitando Puertos LAN'],
            ['cmd' => '/ip dns set allow-remote-requests=yes servers=8.8.8.8,8.8.4.4', 'desc' => 'DNS Google'],
            ['cmd' => ':if ([:len [/ip firewall nat find comment="Masquerade-Hotspot"]]=0) do={/ip firewall nat add chain=srcnat out-interface=ether1 action=masquerade comment="Masquerade-Hotspot"}', 'desc' => 'Configurando NAT'],
            ['cmd' => ':if ([:len [/ip address find address="192.168.88.1/24"]]=0) do={/ip address add address=192.168.88.1/24 interface=bridge-lan}', 'desc' => 'IP Local'],
            ['cmd' => ':if ([:len [/ip pool find name="dhcp_pool1"]]=0) do={/ip pool add name=dhcp_pool1 ranges=192.168.88.10-192.168.88.254}', 'desc' => 'Pool DHCP'],
            ['cmd' => ':if ([:len [/ip dhcp-server find name="dhcp-remoto"]]=0) do={/ip dhcp-server add address-pool=dhcp_pool1 disabled=no interface=bridge-lan name=dhcp-remoto}', 'desc' => 'DHCP Server'],
            ['cmd' => ':if ([:len [/ip dhcp-server network find address="192.168.88.0/24"]]=0) do={/ip dhcp-server network add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=8.8.8.8}', 'desc' => 'DHCP Network'],
            ['cmd' => ':if ([:len [/ip hotspot profile find name="hsprof1"]]=0) do={/ip hotspot profile add name=hsprof1 hotspot-address=192.168.88.1 login-by=http-chap,trial}', 'desc' => 'Perfil Hotspot'],
            ['cmd' => ':if ([:len [/ip hotspot user profile find name="neutro"]]=0) do={/ip hotspot user profile add name="neutro" session-timeout=1s shared-users=1}', 'desc' => 'Perfil Neutro'],
            ['cmd' => ':if ([:len [/ip hotspot find name="hotspot1"]]=0) do={/ip hotspot add name=hotspot1 interface=bridge-lan profile=hsprof1 address-pool=dhcp_pool1 disabled=no}', 'desc' => 'Servidor Hotspot'],
            ['cmd' => '/ip hotspot walled-garden { remove [find]; add dst-host=wifiexpres.com; add dst-host=*.wifiexpres.com; add dst-host=*.biopagobdv.com; add dst-host=*.banvenez.com; add dst-host=fcm.googleapis.com; add dst-host=mtalk.google.com; add dst-host=*.push.apple.com }', 'desc' => 'Walled Garden: Dominios'],
            ['cmd' => '/ip hotspot walled-garden ip { remove [find]; add dst-address=188.95.113.44 comment="Bridge Socket"; add dst-address=190.217.7.106; add dst-address=190.202.148.187 }', 'desc' => 'Walled Garden: IPs Pasarela'],
            ['cmd' => '/tool fetch url="'.$downloadUrl.'" dst-path="hotspot/login.html" check-certificate=no', 'desc' => 'Instalando Portal'],
            ['cmd' => ":if (\"$this->soporte_user\" != \"admin\") do={ /user remove [find name=\"admin\"] }", 'desc' => 'Removiendo admin'],
            ['cmd' => '/system reboot', 'desc' => 'Reinicio'],
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

        $this->logs[] = "📡 Enviando: " . $paso['desc'];

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
            $this->logs[] = "❌ Error de conexión con el Bridge.";
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
                $data = $res->json('data');
                $this->logs[] = ($data === "OK") ? "✅ Hecho" : "⚠️ Error en Router";
                $this->avanzar();
            } elseif ($this->intentos >= 45) {
                $this->logs[] = "⌛ Tiempo agotado, saltando...";
                $this->avanzar();
            }
        } catch (\Exception $e) { }
    }

    private function avanzar()
    {
        $this->esperandoRespuesta = false;
        $this->currentStepIndex++;
        $this->progreso = round(($this->currentStepIndex / count($this->pasos)) * 100);
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