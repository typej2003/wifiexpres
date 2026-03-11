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
        $this->validate(['router_id' => 'required']);
        
        // Mensaje minimalista para evitar fallos de buffer en el reset
        $mensajeSuspendido = "<html><body style='text-align:center;padding-top:50px;'><h1>Servicio Suspendido</h1></body></html>";

        $this->iniciarProceso("⚠️ Iniciando Limpieza Selectiva...", [
            ['cmd' => '/ip hotspot user remove [find]', 'desc' => 'Borrando usuarios Hotspot'],
            ['cmd' => '/ip hotspot remove [find]', 'desc' => 'Borrando servidores Hotspot'],
            ['cmd' => '/ip dhcp-server remove [find]', 'desc' => 'Borrando servidores DHCP'],
            ['cmd' => '/ip pool remove [find]', 'desc' => 'Borrando Pools de IP'],
            ['cmd' => '/interface bridge port remove [find where interface!="ether1"]', 'desc' => 'Liberando puertos'],
            ['cmd' => '/interface bridge remove [find]', 'desc' => 'Eliminando Bridges'],
            ['cmd' => '/ip address remove [find where interface!="ether1"]', 'desc' => 'Limpiando IPs'],
            ['cmd' => '/ip hotspot walled-garden remove [find]', 'desc' => 'Limpiando Walled Garden'],
            ['cmd' => '/ip hotspot walled-garden ip remove [find]', 'desc' => 'Limpiando Walled Garden IP'],
            ['cmd' => '/ip firewall nat remove [find where comment="Masquerade-Hotspot"]', 'desc' => 'Limpiando NAT previo'],
            ['cmd' => '/user remove [find name!="jose" and name!="admin"]', 'desc' => 'Limpiando usuarios sistema'],
            ['cmd' => '/file print file="hotspot/login.html"; :delay 1s; /file set "hotspot/login.html" contents="'.$mensajeSuspendido.'"', 'desc' => 'Marcando portal como Suspendido'],
        ]);
    }

    public function ejecutarConfiguracion()
    {
        $this->validate([
            'router_id' => 'required',
            'version_id' => 'required'
        ]);
        
        $version = HotspotVersion::findOrFail($this->version_id);
        
        // IMPORTANTE: Cambia esto por tu dominio real
        // El MikroTik usará esta URL para descargar el archivo login.html
        $baseUrl = "https://wifiexpres.com"; 
        $downloadUrl = $baseUrl . "/api/portal-download/" . $this->version_id;

        $this->iniciarProceso("🚀 Iniciando Provisión Remota Full...", [
            ['cmd' => ':if ([:len [/user find name="soporte"]]=0) do={/user add name="soporte" password="123" group=full}', 'desc' => 'Usuario soporte'],
            ['cmd' => ':if ([:len [/interface bridge find name="bridge-lan"]]=0) do={/interface bridge add name=bridge-lan}', 'desc' => 'Bridge LAN'],
            ['cmd' => ':foreach i in=[/interface ethernet find where name!="ether1"] do={ :local n [/interface ethernet get $i name]; :if ([:len [/interface bridge port find interface=$n]]=0) do={/interface bridge port add bridge=bridge-lan interface=$n} }', 'desc' => 'Habilitando Puertos LAN'],
            ['cmd' => '/interface wireless disable [find name="wifi1"]', 'desc' => 'Desactivando wifi'],
            ['cmd' => '/ip dns set allow-remote-requests=yes servers=8.8.8.8,1.1.1.1', 'desc' => 'Configurando DNS'],
            ['cmd' => ':if ([:len [/ip firewall nat find comment="Masquerade-Hotspot"]]=0) do={/ip firewall nat add chain=srcnat out-interface=ether1 action=masquerade comment="Masquerade-Hotspot"}', 'desc' => 'Configurando NAT'],
            ['cmd' => ':if ([:len [/ip address find address="192.168.88.1/24"]]=0) do={/ip address add address=192.168.88.1/24 interface=bridge-lan}', 'desc' => 'IP Local'],
            ['cmd' => ':if ([:len [/ip pool find name="dhcp_pool1"]]=0) do={/ip pool add name=dhcp_pool1 ranges=192.168.88.10-192.168.88.254}', 'desc' => 'Pool DHCP'],
            ['cmd' => ':if ([:len [/ip dhcp-server find name="dhcp-remoto"]]=0) do={/ip dhcp-server add address-pool=dhcp_pool1 disabled=no interface=bridge-lan name=dhcp-remoto}', 'desc' => 'DHCP Server'],
            ['cmd' => ':if ([:len [/ip dhcp-server network find address="192.168.88.0/24"]]=0) do={/ip dhcp-server network add address=192.168.88.0/24 gateway=192.168.88.1 dns-server=8.8.8.8}', 'desc' => 'DHCP Network'],
            ['cmd' => ':if ([:len [/ip hotspot profile find name="hsprof1"]]=0) do={/ip hotspot profile add name=hsprof1 hotspot-address=192.168.88.1 login-by=http-chap,trial}', 'desc' => 'Perfil Hotspot'],
            ['cmd' => ':if ([:len [/ip hotspot find name="hotspot1"]]=0) do={/ip hotspot add name=hotspot1 interface=bridge-lan profile=hsprof1 address-pool=dhcp_pool1 disabled=no}', 'desc' => 'Servidor Hotspot'],
            ['cmd' => '/ip hotspot walled-garden add dst-host=wifiexpres.com; /ip hotspot walled-garden add dst-host=*.wifiexpres.com', 'desc' => 'Walled Garden Dominios'],
            ['cmd' => '/ip hotspot walled-garden ip add dst-address=188.95.113.44 dst-port=3000 protocol=tcp action=accept', 'desc' => 'Acceso Bridge'],

            // --- MÉTODO POR DESCARGA (EVITA EL TIMEOUT) ---
            ['cmd' => '/tool fetch url="'.$downloadUrl.'" dst-path="hotspot/login.html" mode=http', 'desc' => 'Descargando e Instalando Portal: ' . $version->name],
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
        $this->currentTid = "CMD" . time() . rand(10, 99);
        $this->intentos = 0;

        $this->logs[] = "📡 Enviando: " . $paso['desc'];

        $script = ":do { {$paso['cmd']}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid={$this->currentTid}\" http-method=post http-data=\"OK\" keep-result=no } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid={$this->currentTid}\" http-method=post http-data=\"ERR\" keep-result=no }";
        
        $scriptLimpio = trim(preg_replace('/\s+/', ' ', $script));

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody($scriptLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            $this->esperandoRespuesta = true;
        } catch (\Exception $e) {
            $this->logs[] = "❌ Error al contactar el Bridge.";
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
                $this->logs[] = "⌛ Tiempo de espera agotado, continuando...";
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
        $this->logs[] = $this->abortar ? "🛑 Proceso detenido." : "🏁 Proceso completado correctamente.";
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