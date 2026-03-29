<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use App\Models\HotspotVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class CrearDirectorios extends Component
{
    public $router_id;
    public $version_id; // ID de la versión del portal a instalar
    public $selectedAliado = null;
    public $routerStatus = []; 
    public $logs = [];
    public $isConfiguring = false;
    public $progreso = 0;
    public $esperandoRespuesta = false;
    public $currentTid = null;
    public $currentStepIndex = 0;
    public $pasos = [];
    public $intentos = 0;

    protected $bridgeUrl = "http://188.95.113.44:3000";
    protected $apiUrl = "https://wifiexpres.com/api";

    public function mount() {
        if (Auth::user()->role !== 'admin') abort(403);
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

    public function resetHotspot() {
        $this->validate([
            'router_id' => 'required',
            'version_id' => 'required'
        ]);

        $this->iniciarProceso("re-estableciendo HTML Hotspot", [
            ['cmd' => ':if ([:len [/file find name="hotspot"]] = 0) do={ /file add name="hotspot" type="directory" }', 'desc' => 'Verificando carpeta /hotspot'],
            ['cmd' => ':if ([:len [/file find name="hotspot/css"]] = 0) do={ /file add name="hotspot/css" type="directory" }', 'desc' => 'Verificando carpeta /hotspot/css'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/portal-download/'.$this->version_id.'" dst-path="hotspot/login.html" check-certificate=no', 'desc' => 'Resetting login.html'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/hotspot-assets/pasarela.html" dst-path="hotspot/pasarela.html" check-certificate=no', 'desc' => 'Resetting pasarela.html']
        ]);
    }

    public function ejecutarTodo() {
        $this->validate([
            'router_id' => 'required',
            'version_id' => 'required'
        ]);

        $this->iniciarProceso("🚀 Instalación Automática Completa", [
            ['cmd' => ':if ([:len [/file find name="hotspot"]] = 0) do={ /file add name="hotspot" type="directory" }', 'desc' => 'Creando /hotspot'],
            ['cmd' => ':if ([:len [/file find name="hotspot/css"]] = 0) do={ /file add name="hotspot/css" type="directory" }', 'desc' => 'Creando /hotspot/css'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/portal-download/'.$this->version_id.'" dst-path="hotspot/login.html" check-certificate=no', 'desc' => 'Instalando login.html'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/hotspot-assets/pasarela.html" dst-path="hotspot/pasarela.html" check-certificate=no', 'desc' => 'Instalando pasarela.html'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/hotspot-assets/bootstrap.min.css" dst-path="hotspot/css/bootstrap.min.css" check-certificate=no', 'desc' => 'Instalando bootstrap.min.css'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/hotspot-assets/all.min.css" dst-path="hotspot/css/all.min.css" check-certificate=no', 'desc' => 'Instalando all.min.css']
        ]);
    }

    private function iniciarProceso($mensaje, $listaPasos) {
        if (!($this->routerStatus[$this->router_id] ?? false)) {
            $this->logs[] = "❌ ERROR: El router seleccionado está OFFLINE.";
            return;
        }

        $this->isConfiguring = true;
        $this->progreso = 0;
        $this->currentStepIndex = 0;
        $this->pasos = $listaPasos;
        $this->logs = ["🛠️ " . strtoupper($mensaje)];
        $this->enviarSiguienteComando();
    }

    public function enviarSiguienteComando() {
        if ($this->currentStepIndex >= count($this->pasos)) {
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
        } catch (\Exception $e) { 
            $this->logs[] = "❌ Error Bridge"; 
            $this->finalizar(); 
        }
    }

    public function checkStatus() {
        if (!$this->esperandoRespuesta) return;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->intentos++;

        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);
            if ($res->successful() && $res->json('status') === 'ready') {
                $this->avanzar();
            } elseif ($this->intentos >= 35) {
                $this->logs[] = "⚠️ Timeout en paso actual. Saltando...";
                $this->avanzar();
            }
        } catch (\Exception $e) { }
    }

    private function avanzar() {
        $this->esperandoRespuesta = false;
        $this->currentStepIndex++;
        $this->progreso = round(($this->currentStepIndex / count($this->pasos)) * 100);
        $this->dispatchBrowserEvent('logUpdated');
        $this->enviarSiguienteComando();
    }

    private function finalizar() {
        $this->isConfiguring = false;
        $this->esperandoRespuesta = false;
        $this->progreso = 100;
        $this->logs[] = "🏁 OPERACIÓN FINALIZADA.";
        $this->dispatchBrowserEvent('logUpdated');
    }

    public function render() {
        return view('livewire.mikrotik.herramientas.crear-directorios', [
            'routers' => Router::when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))->get(),
            'aliados' => User::where('role', 'aliado')->get(),
            'versiones' => HotspotVersion::all()
        ]);
    }
}