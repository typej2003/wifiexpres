<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Diagnostico extends Component {
    public $router_id;
    public $command = "/system resource print";
    public $terminal_output = "Consola lista. Seleccione un router...";
    public $loading = false;

    // Campos para gestión de usuarios
    public $new_username, $new_password = "123", $new_profile = "neutro";

    protected $bridgeUrl = "http://188.95.113.44:3000";

    // --- LÓGICA DE COMUNICACIÓN ESTILO "ALIADO" ---

    protected function emitirAlSocket($comando, $mac, $tid) {
        try {
            $comandoLimpio = trim(preg_replace('/\s+/', ' ', $comando));
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($comandoLimpio, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$response->successful()) throw new \Exception("Bridge Offline");
            return true;
        } catch (\Exception $e) {
            Log::error("Error Bridge: " . $e->getMessage());
            throw new \Exception("Error al conectar con el Bridge.");
        }
    }

    protected function esperarRespuesta($mac, $tid) {
        set_time_limit(90);
        for ($i = 0; $i < 30; $i++) { // 30 intentos (aprox 30 seg)
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

    // --- PRESETS OPTIMIZADOS (ESTRUCTURA ROBUSTA) ---

    protected function getPresetCommand($key, $mac, $tid) {
        $base = ":local m \"$mac\"; :local t \"$tid\"; :local res \"\"; ";
        $end = " /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$res keep-result=no;";

        $scripts = [
            "identity"  => ":set res [/system identity get name];",
            "cpu"       => ":set res ([/system resource get cpu-load] . \"%\");",
            "uptime"    => ":set res [/system resource get uptime];",
            "address"   => "/ip address { :foreach i in=[find] do={ :set res (\$res . [get \$i address] . \"-\" . [get \$i interface] . \"\\n\") } };",
            "puertos"   => "/interface bridge port { :foreach i in=[find] do={ :set res (\$res . [get \$i interface] . \"->\" . [get \$i bridge] . \"\\n\") } };",
            "hotspots"  => "/ip hotspot { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \" (\" . [get \$i interface] . \")\\n\") } };",
            "profiles"  => "/ip hotspot user profile { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \"\\n\") } };",
            "user_list" => "/ip hotspot user { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \" (\" . [get \$i profile] . \")\\n\") } };",
            "dns"       => ":local s [/ip dns get servers]; :set res (\"Static:\" . \$s);",
            "usuarios"  => ":set res [/ip hotspot user count-only];",
        ];

        return isset($scripts[$key]) ? ($base . $scripts[$key] . $end) : null;
    }

    public function setPreset($key) {
        $this->validate(['router_id' => 'required']);
        $this->loading = true;
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "DIAG" . time();
        
        $fullCmd = $this->getPresetCommand($key, $mac, $tid);
        $this->terminal_output = ">>> CONSULTANDO: " . strtoupper($key) . "...\n";

        try {
            $this->emitirAlSocket($fullCmd, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);
            $this->terminal_output .= $res ?: "TIMEOUT: Sin respuesta del router.";
        } catch (\Exception $e) {
            $this->terminal_output .= "ERROR: " . $e->getMessage();
        }
        $this->loading = false;
    }

    public function executeCommand() {
        $this->validate(['router_id' => 'required', 'command' => 'required']);
        $this->loading = true;
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "MAN" . time();
        
        // Encapsulamos el comando manual para que pueda devolver "SUCCESS" al menos
        $fullCmd = ":local m \"$mac\"; :local t \"$tid\"; :do { {$this->command}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"EJECUTADO_OK\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"ERROR_EN_COMANDO\" keep-result=no; };";

        try {
            $this->emitirAlSocket($fullCmd, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);
            $this->terminal_output = ">>> RESULTADO MANUAL:\n" . ($res ?: "Comando enviado (sin respuesta de retorno).");
        } catch (\Exception $e) {
            $this->terminal_output = "ERROR: " . $e->getMessage();
        }
        $this->loading = false;
    }

    // Lógica para cambiar perfil estilo "Aliado" (con do-error)
    public function changeProfile() {
        $this->validate(['router_id' => 'required', 'new_username' => 'required']);
        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "PROF" . time();

        $fullCmd = ":local m \"$mac\"; :local t \"$tid\"; :do { /ip hotspot user set [find name=\"{$this->new_username}\"] profile=\"{$this->new_profile}\" limit-uptime=0s; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"PERFIL_ACTUALIZADO\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"USUARIO_NO_EXISTE\" keep-result=no; };";

        try {
            $this->emitirAlSocket($fullCmd, $mac, $tid);
            $this->terminal_output = ">>> CAMBIANDO PERFIL...\n" . ($this->esperarRespuesta($mac, $tid) ?: "Sin confirmación.");
        } catch (\Exception $e) { $this->terminal_output = "ERROR: " . $e->getMessage(); }
        $this->loading = false;
    }

    public function render() {
        $user = Auth::user();
        $routers = ($user->role === "admin") ? Router::all() : Router::where("user_id", $user->id)->get();
        return view("livewire.mikrotik.herramientas.diagnostico", ["routers" => $routers])->layout("layouts.app");
    }
}