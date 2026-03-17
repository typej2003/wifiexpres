<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class Diagnostico extends Component {
    public $router_id;
    public $command = "/system resource print";
    public $terminal_output = "Consola lista. Seleccione un router y ejecute un comando...";
    public $loading = false;

    public $new_username;
    public $new_password = "123";
    public $new_profile = "neutro";

    public $bridgeUrl = "http://188.95.113.44:3000";

    public function changeProfile() {
        $this->validate([
            'router_id' => 'required', 
            'new_username' => 'required',
            'new_profile' => 'required'
        ]);
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "CHG" . uniqid();

        $rawCommand = "/ip hotspot user { :local u \"{$this->new_username}\"; :local p \"{$this->new_profile}\"; :if ([:len [find where name=\$u]] > 0) do={ set [find where name=\$u] profile=\$p limit-uptime=0s; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"PERFIL_CAMBIADO_OK\" keep-result=no; } else={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR_USUARIO_NO_ENCONTRADO\" keep-result=no; } }";

        $this->command = $rawCommand;
        $this->executeCommand($tid);
    }

    protected function getPresetCommand($key, $mac, $tid) {
        $presets = [
            "identity"  => ":local val [/system identity get name]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"\$val\" keep-result=no",
            
            "cpu"       => ":local val [/system resource get cpu-load]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"CPU Load: \$val%\" keep-result=no",
            
            "uptime"    => ":local val [/system resource get uptime]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"Uptime: \$val\" keep-result=no",
            
            // --- SERVIDORES HOTSPOT (CORREGIDO) ---
            "hotspots"  => ":local res \"\"; /ip hotspot { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \" -> \" . [get \$i interface] . \"\\n\") } }; :if ([:len \$res] = 0) do={ :set res \"No hay Hotspots\" }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"\$res\" keep-result=no",
            
            // --- PUERTOS BRIDGE (CORREGIDO) ---
            "puertos"   => ":local res \"\"; /interface bridge port { :foreach i in=[find] do={ :set res (\$res . [get \$i interface] . \" -> \" . [get \$i bridge] . \"\\n\") } }; :if ([:len \$res] = 0) do={ :set res \"No hay puertos\" }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"\$res\" keep-result=no",
            
            // --- PERFILES DE USUARIO (NUEVO) ---
            "profiles"  => ":local res \"\"; /ip hotspot user profile { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \" (shared:\" . [get \$i shared-users] . \")\\n\") } }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"\$res\" keep-result=no",
            
            "address"   => ":local res \"\"; /ip address { :foreach i in=[find] do={ :set res (\$res . [get \$i address] . \" - \" . [get \$i interface] . \"\\n\") } }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"\$res\" keep-result=no",
            
            "dns"       => ":local s [/ip dns get servers]; :local d [/ip dns get dynamic-servers]; :local res \"Estaticos: \$s \\nDinamicos: \$d\"; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"\$res\" keep-result=no",
            
            "user_list" => ":local res \"\"; /ip hotspot user { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \" (Prof:\" . [get \$i profile] . \")\\n\") } }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"\$res\" keep-result=no",
            
            "usuarios"  => ":local val [/ip hotspot user count-only]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"Total Usuarios: \$val\" keep-result=no",
        ];

        return $presets[$key] ?? null;
    }

    public function setPreset($key) {
        if (!$this->router_id) {
            $this->terminal_output = "Error: Seleccione un router primero.";
            return;
        }
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "DIAG" . uniqid();
        $cmd = $this->getPresetCommand($key, $mac, $tid);
        if ($cmd) {
            $this->command = $cmd;
            $this->executeCommand($tid);
        }
    }

    public function createUser() {
        $this->validate(['router_id' => 'required', 'new_username' => 'required']);
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "ADD" . uniqid();
        $this->command = "/ip hotspot user add name=\"{$this->new_username}\" password=\"{$this->new_password}\" profile=\"{$this->new_profile}\" comment=\"Test Bridge\";/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"USUARIO_CREADO_OK\" keep-result=no";
        $this->executeCommand($tid);
        $this->new_username = "";
    }

    public function executeCommand($existingTid = null) {
        $this->validate(["router_id" => "required", "command" => "required"]);
        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = $existingTid ?? "MAN" . uniqid();
        $this->terminal_output = ">>> EJECUTANDO EN: " . strtoupper($router->identity) . " (TID: $tid)\n";

        try {
            // Limpieza del comando para evitar fallos por espacios o saltos de línea
            $cleanCommand = trim(preg_replace('/\s+/', ' ', $this->command));
            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($cleanCommand, 'text/plain')->post("{$this->bridgeUrl}/set-command");
            if (!$response->successful()) throw new \Exception("Bridge Offline.");
            $this->terminal_output .= ">>> ESPERANDO RESPUESTA...\n";

            $confirmado = false;
            for ($i = 0; $i < 15; $i++) {
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $this->terminal_output .= "\n--- RESULTADO ---\n" . $res->json('data');
                    $confirmado = true;
                    break;
                }
            }
            if (!$confirmado) $this->terminal_output .= "\n>>> TIMEOUT.";
        } catch (\Exception $e) {
            $this->terminal_output .= "\n>>> ERROR: " . $e->getMessage();
        }
        $this->loading = false;
    }

    public function render() {
        $user = Auth::user();
        $routers = ($user->role === "admin") ? Router::all() : Router::where("user_id", $user->id)->get();
        return view("livewire.mikrotik.herramientas.diagnostico", ["routers" => $routers])->layout("layouts.app");
    }
}