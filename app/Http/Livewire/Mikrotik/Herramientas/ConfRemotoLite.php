<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use App\Models\HotspotVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class ConfRemotoLite extends Component
{
    public $router_id, $version_id, $soporte_user = 'soporte', $soporte_pass;
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

    public $bridgeUrl = "http://188.95.113.44:3000";

    public function mount() {
        if (Auth::user()->role !== 'admin') abort(403);
        $this->refreshStatus();
    }

    public function refreshStatus() {
        try {
            $response = Http::timeout(5)->get("{$this->bridgeUrl}/api/routers-online");
            if ($response->successful()) {
                $activeMacs = collect($response->json())->map(fn($item) => strtoupper(trim($item['mac'])))->toArray();
                $this->routerStatus = Router::all()->mapWithKeys(fn($r) => [$r->id => in_array(strtoupper(trim($r->macAddress)), $activeMacs)])->toArray();
            }
        } catch (\Exception $e) { $this->routerStatus = []; }
    }

    public function provisionarHapLite() {
        $this->validate([
            'router_id' => 'required',
            'version_id' => 'required',
            'soporte_pass' => 'required|min:4'
        ]);

        $router = Router::findOrFail($this->router_id);
        $downloadUrl = "https://wifiexpres.com/api/portal-download/" . $this->version_id;
        $identity = $router->identity ?? 'HAP-Lite';

        // LÓGICA ESTÁTICA PARA HAP LITE (Evita saturación de CPU)
        $this->iniciarProceso("🛠️ Provisión Especial hAP Lite (Modo Estable)", [
            ['cmd' => "/user { :if ([:len [find name=\"$this->soporte_user\"]]=0) do={add name=\"$this->soporte_user\" password=\"$this->soporte_pass\" group=full} else={set [find name=\"$this->soporte_user\"] password=\"$this->soporte_pass\"} }", 'desc' => '1. Usuario soporte'],
            
            ['cmd' => "/ip hotspot remove [find]; /ip dhcp-server remove [find]; /ip address remove [find where interface!=\"ether1\"]; /interface bridge port remove [find]; /interface bridge remove [find]; /ip pool remove [find]", 'desc' => '2. Limpieza total'],

            ['cmd' => "/interface bridge add name=bridge-LAN; /interface bridge port add bridge=bridge-LAN interface=ether2; /interface bridge port add bridge=bridge-LAN interface=ether3; /interface bridge port add bridge=bridge-LAN interface=ether4; /interface bridge port add bridge=bridge-LAN interface=wlan1", 'desc' => '3. Puente Unificado LAN/WiFi'],

            ['cmd' => "/ip dns set allow-remote-requests=yes servers=8.8.8.8,8.8.4.4; /ip dhcp-client add interface=ether1 disabled=no use-peer-dns=no comment=\"WAN\"", 'desc' => '4. DNS y WAN'],

            ['cmd' => "/ip address add address=10.0.0.1/24 interface=bridge-LAN network=10.0.0.0", 'desc' => '5. IP Local'],

            ['cmd' => "/ip pool add name=pool-lan ranges=10.0.0.10-10.0.0.250; /ip dhcp-server add address-pool=pool-lan disabled=no interface=bridge-LAN name=\"srv-lan\"; /ip dhcp-server network add address=10.0.0.0/24 dns-server=8.8.8.8 gateway=10.0.0.1", 'desc' => '6. DHCP Server'],

            ['cmd' => "/ip firewall nat add action=masquerade chain=srcnat out-interface=ether1 comment=\"NAT-HAP\"; /ip hotspot user profile add name=\"neutro\" session-timeout=1s; /ip hotspot user profile add name=\"cortesia 20min-0\" session-timeout=20m", 'desc' => '7. NAT y Perfiles'],

            ['cmd' => "/ip hotspot profile add dns-name=wifi.login hotspot-address=10.0.0.1 name=hsprof1 login-by=http-chap,http-pap,trial; /ip hotspot add address-pool=pool-lan disabled=no interface=bridge-LAN name=\"hotspot-hap\" profile=hsprof1", 'desc' => '8. Servidor Hotspot'],

            ['cmd' => "/ip hotspot walled-garden { remove [find]; add dst-host=wifiexpres.com; add dst-host=*.wifiexpres.com; add dst-host=188.95.113.44 }", 'desc' => '9. Walled Garden'],

            ['cmd' => "/ip hotspot profile set [find name=\"hsprof1\"] html-directory=hotspot; /tool fetch url=\"$downloadUrl\" dst-path=\"hotspot/login.html\" check-certificate=no", 'desc' => '10. Instalando Portal'],
            
            ['cmd' => "/system reboot", 'desc' => '11. Reiniciando equipo']
        ]);
    }

    private function iniciarProceso($mensaje, $listaPasos) {
        $this->isConfiguring = true;
        $this->abortar = false;
        $this->progreso = 0;
        $this->currentStepIndex = 0;
        $this->pasos = $listaPasos;
        $this->logs = [$mensaje];
        $this->enviarSiguienteComando();
    }

    public function enviarSiguienteComando() {
        if ($this->abortar || $this->currentStepIndex >= count($this->pasos)) {
            $this->finalizar(); return;
        }

        $paso = $this->pasos[$this->currentStepIndex];
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->currentTid = "TID" . time() . rand(10, 99);
        $this->intentos = 0;

        $this->logs[] = "📡 " . $paso['desc'];

        $script = "{ :local r \"OK\"; :do { ".$paso['cmd']." } on-error={ :set r \"ERR\" }; /tool fetch url=\"$this->bridgeUrl/post-result?mac=$mac&tid=$this->currentTid&data=\$r\" keep-result=no }";
        $scriptLimpio = trim(preg_replace('/\s+/', ' ', $script));

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody($scriptLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
            $this->esperandoRespuesta = true;
        } catch (\Exception $e) { $this->logs[] = "❌ Error Bridge"; $this->finalizar(); }
    }

    public function checkStatus() {
        if (!$this->esperandoRespuesta || $this->abortar) return;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->intentos++;

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);
            if ($res->successful() && $res->json('status') === 'ready') {
                $this->avanzar();
            } elseif ($this->intentos >= 35) {
                $this->logs[] = "⚠️ Ignorando timeout...";
                $this->avanzar();
            }
        } catch (\Exception $e) { }
    }

    private function avanzar() {
        $this->esperandoRespuesta = false;
        $this->currentStepIndex++;
        $this->progreso = round(($this->currentStepIndex / count($this->pasos)) * 100);
        $this->enviarSiguienteComando();
    }

    private function finalizar() {
        $this->isConfiguring = false;
        $this->esperandoRespuesta = false;
        $this->progreso = 100;
        $this->logs[] = $this->abortar ? "🛑 Abortado." : "🏁 Proceso Completo.";
    }

    public function render() {
        return view('livewire.mikrotik.herramientas.configurar-remoto-lite', [
            'routers' => Router::when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))->get(),
            'aliados' => User::where('role', 'aliado')->get(),
            'versiones' => HotspotVersion::all()
        ]);
    }
}