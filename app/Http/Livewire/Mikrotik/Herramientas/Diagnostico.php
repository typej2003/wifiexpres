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

    // CAMBIO: Debe ser public para evitar el error de acceso
    public $bridgeUrl = "http://188.95.113.44:3000";

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
        for ($i = 0; $i < 30; $i++) {
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

    protected function getPresetCommand($key, $mac, $tid) {
        $base = ":local m \"$mac\"; :local t \"$tid\"; :local res \"\"; ";
        $end = " /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$res keep-result=no;";

        $scripts = [
            "identity"  => ":set res [/system identity get name];",
            "cpu"       => ":set res ([/system resource get cpu-load] . \"%\");",
            "uptime"    => ":set res [/system resource get uptime];",
            "puertos"   => "/interface bridge port { :foreach i in=[find] do={ :set res (\$res . [get \$i interface] . \"->\" . [get \$i bridge] . \"\\n\") } };",
            "address"   => "/ip address { :foreach i in=[find] do={ :set res (\$res . [get \$i address] . \"-\" . [get \$i interface] . \"\\n\") } };",
            "pools"     => "/ip pool { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \": \" . [get \$i ranges] . \"\\n\") } };",
            "hotspots"  => "/ip hotspot { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \" (\" . [get \$i interface] . \")\\n\") } };",
            "profiles"  => "/ip hotspot user profile { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \"\\n\") } };",
            "user_list" => "/ip hotspot user { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \" (\" . [get \$i profile] . \")\\n\") } };",
            "dns"       => ":local s [/ip dns get servers]; :set res (\"Static:\" . \$s);",
            "usuarios"  => ":set res [/ip hotspot user count-only];",
            "dhcp"      => "/ip dhcp-server { :foreach i in=[find] do={ :set res (\$res . [get \$i name] . \" -> \" . [get \$i interface] . \" [\" . [get \$i address-pool] . \"]\\n\") } };",
        ];

        return isset($scripts[$key]) ? ($base . $scripts[$key] . $end) : null;
    }

    public function setPreset($key) {
        if (!$this->router_id) return;
        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "DIAG" . time();
        
        $fullCmd = $this->getPresetCommand($key, $mac, $tid);
        $this->terminal_output = ">>> CONSULTANDO: " . strtoupper($key) . "...\n";

        try {
            $this->emitirAlSocket($fullCmd, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);
            $this->terminal_output .= $res ?: "TIMEOUT: Sin respuesta.";
        } catch (\Exception $e) { $this->terminal_output .= "ERROR: " . $e->getMessage(); }
        $this->loading = false;
    }

    public function createUser() {
        $this->validate(['router_id' => 'required', 'new_username' => 'required']);
        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "ADD" . time();

        $fullCmd = ":local m \"$mac\"; :local t \"$tid\"; :do { /ip hotspot user add name=\"{$this->new_username}\" password=\"{$this->new_password}\" profile=\"{$this->new_profile}\" comment=\"Diagnostico\"; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"USUARIO_CREADO_OK\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL_CREACION\" keep-result=no; };";

        try {
            $this->emitirAlSocket($fullCmd, $mac, $tid);
            $this->terminal_output = ">>> CREANDO USUARIO...\n" . ($this->esperarRespuesta($mac, $tid) ?: "Sin confirmación.");
            $this->new_username = "";
        } catch (\Exception $e) { $this->terminal_output = "ERROR: " . $e->getMessage(); }
        $this->loading = false;
    }

    public function changeProfile() {
        $this->validate(['router_id' => 'required', 'new_username' => 'required']);
        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "PROF" . time();

        $fullCmd = ":local m \"$mac\"; :local t \"$tid\"; :do { /ip hotspot user set [find name=\"{$this->new_username}\"] profile=\"{$this->new_profile}\" limit-uptime=0s; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"PERFIL_ACTUALIZADO\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"ERROR_USUARIO_O_PERFIL\" keep-result=no; };";

        try {
            $this->emitirAlSocket($fullCmd, $mac, $tid);
            $this->terminal_output = ">>> CAMBIANDO PERFIL...\n" . ($this->esperarRespuesta($mac, $tid) ?: "Sin confirmación.");
        } catch (\Exception $e) { $this->terminal_output = "ERROR: " . $e->getMessage(); }
        $this->loading = false;
    }

    public function executeCommand() {
        if (!$this->router_id || !$this->command) return;
        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "MAN" . time();
        
        $fullCmd = ":local m \"$mac\"; :local t \"$tid\"; :do { {$this->command}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"EJECUTADO_CON_EXITO\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"ERROR_EN_SINTAXIS\" keep-result=no; };";

        try {
            $this->emitirAlSocket($fullCmd, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);
            $this->terminal_output = ">>> RESULTADO MANUAL:\n" . ($res ?: "Enviado.");
        } catch (\Exception $e) { $this->terminal_output = "ERROR: " . $e->getMessage(); }
        $this->loading = false;
    }

    public function render() {
        $user = Auth::user();
        $routers = ($user->role === "admin") ? Router::all() : Router::where("user_id", $user->id)->get();
        return view("livewire.mikrotik.herramientas.diagnostico", ["routers" => $routers])->layout("layouts.app");
    }
}