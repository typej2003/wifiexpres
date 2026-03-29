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
    public $version_id; 
    public $selectedAliado = null;
    public $routerStatus = []; 
    public $logs = [];
    public $isConfiguring = false;
    public $progreso = 0;
    public $esperandoRespuesta = false;
    public $currentTid = null;
    public $currentStepIndex = 0;
    public $pasos = [];
    public $intentos = 0; // Intentos de polling (espera de respuesta)
    public $reintentosPaso = 0; // Intentos de reenvío del comando (máximo 2)

    protected $bridgeUrl = "http://188.95.113.44:3000";
    protected $apiUrl = "https://wifiexpres.com/api";

    public function mount() {
        if (Auth::user()->role !== 'admin') abort(403);
        $this->refreshStatus();
    }

    public function refreshStatus() {
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

    public function resetHotspot() {
        $this->validate(['router_id' => 'required']);
        $this->iniciarProceso("Restaurando Hotspot Original", [
            ['cmd' => '/ip hotspot profile reset-html [find]', 'desc' => 'Restaurando archivos de fábrica'],
            ['cmd' => ':delay 2s; /system reboot', 'desc' => 'Reiniciando router...']
        ]);
    }

    public function ejecutarTodo() {
        $this->validate(['router_id' => 'required', 'version_id' => 'required']);

        $this->iniciarProceso("🚀 Instalación Completa", [
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/portal-download/'.$this->version_id.'" dst-path="hotspot/login.html" check-certificate=no', 'desc' => 'Instalando login.html'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/hotspot-assets/pasarela.html" dst-path="hotspot/pasarela.html" check-certificate=no', 'desc' => 'Instalando pasarela.html'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/hotspot-assets/bootstrap.min.css" dst-path="hotspot/css/bootstrap.min.css" check-certificate=no', 'desc' => 'Descargando bootstrap.min.css'],
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/hotspot-assets/all.min.css" dst-path="hotspot/css/all.min.css" check-certificate=no', 'desc' => 'Descargando all.min.css'],
            ['cmd' => '/ip hotspot profile set [find] html-directory=hotspot', 'desc' => 'Asignando directorio al perfil'],
            ['cmd' => ':delay 2s; /system reboot', 'desc' => 'Reiniciando equipo para aplicar cambios...']
        ]);
    }

    public function descargarLoginIndependiente() {
        $this->validate(['router_id' => 'required', 'version_id' => 'required']);

        $this->iniciarProceso("Force Download: login.html", [
            ['cmd' => '/tool fetch url="'.$this->apiUrl.'/portal-download/'.$this->version_id.'" dst-path="hotspot/login.html" check-certificate=no', 'desc' => 'Forzando descarga de login.html'],
            ['cmd' => ':delay 2s; /system reboot', 'desc' => 'Reinicio post-actualización...']
        ]);
    }

    private function iniciarProceso($mensaje, $listaPasos) {
        if (!($this->routerStatus[$this->router_id] ?? false)) {
            $this->logs[] = "❌ ERROR: Router OFFLINE.";
            return;
        }
        $this->isConfiguring = true;
        $this->progreso = 0;
        $this->currentStepIndex = 0;
        $this->reintentosPaso = 0;
        $this->pasos = $listaPasos;
        $this->logs = ["🛠️ " . strtoupper($mensaje)];
        $this->enviarSiguienteComando();
    }

    public function enviarSiguienteComando() {
        if ($this->currentStepIndex >= count($this->pasos)) {
            $this->finalizar(); 
            return;
        }
        
        $paso = $this->pasos[$this->currentStepIndex];
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->currentTid = "TID" . time() . rand(10, 99);
        $this->intentos = 0;
        
        $statusMsg = ($this->reintentosPaso > 0) ? "🔄 (Reintento {$this->reintentosPaso}/2) " : "📡 ";
        $this->logs[] = $statusMsg . $paso['desc'];

        // Script con captura de error y reporte al bridge
        $script = "{ :local r \"OK\"; :do { ".$paso['cmd']." } on-error={ :set r \"ERR\" }; /tool fetch url=\"$this->bridgeUrl/post-result?mac=$mac&tid=$this->currentTid&data=\$r\" keep-result=no }";
        $scriptLimpio = trim(preg_replace('/\s+/', ' ', $script));

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $this->currentTid])
                ->withBody($scriptLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");
            
            $this->esperandoRespuesta = true;
        } catch (\Exception $e) { 
            $this->logs[] = "❌ Fallo de conexión con Bridge."; 
            $this->gestionarFallo();
        }
    }

    public function checkStatus() {
        if (!$this->esperandoRespuesta) return;
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $this->intentos++;
        
        try {
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $this->currentTid]);
            
            if ($res->successful()) {
                $status = $res->json('status');
                $data = $res->json('data'); // Captura el "OK" o "ERR" enviado por MikroTik

                if ($status === 'ready') {
                    if ($data === 'OK') {
                        $this->reintentosPaso = 0; // Resetear reintentos al tener éxito
                        $this->avanzar();
                    } else {
                        $this->logs[] = "⚠️ Router reportó error en comando.";
                        $this->gestionarFallo();
                    }
                }
            }

            // Manejo de Timeouts o si el router se reinició (último paso)
            if ($this->intentos >= 40) { 
                if ($this->currentStepIndex === count($this->pasos) - 1) {
                    $this->logs[] = "✅ Reinicio confirmado por timeout.";
                    $this->avanzar();
                } else {
                    $this->logs[] = "🕒 Tiempo de espera agotado.";
                    $this->gestionarFallo();
                }
            }
        } catch (\Exception $e) { }
    }

    private function gestionarFallo() {
        $this->esperandoRespuesta = false;
        if ($this->reintentosPaso < 2) {
            $this->reintentosPaso++;
            $this->enviarSiguienteComando();
        } else {
            $this->logs[] = "❌ Comando fallido tras 2 reintentos. Abortando.";
            $this->finalizar();
        }
    }

    private function avanzar() {
        $this->esperandoRespuesta = false;
        $this->currentStepIndex++;
        $this->reintentosPaso = 0;
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