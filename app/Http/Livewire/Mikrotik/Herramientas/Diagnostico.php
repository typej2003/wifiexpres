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

    public $update_username;
    public $update_profile = "default";

    public $bridgeUrl = "http://188.95.113.44:3000";

    public function changeProfile() {
        $this->validate([
            'router_id' => 'required', 
            'update_username' => 'required',
            'update_profile' => 'required'
        ]);
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "CHG" . uniqid();

        // Comando optimizado: Busca el usuario, cambia el perfil y notifica.
        // Usamos el contexto /ip hotspot user para que el comando sea corto.
        $rawCommand = "/ip hotspot user { 
            :local u \"{$this->update_username}\"; 
            :local p \"{$this->update_profile}\"; 
            :if ([:len [find where name=\$u]] > 0) do={ 
                set [find where name=\$u] profile=\$p limit-uptime=0s; 
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"PERFIL_ACTUALIZADO_OK\" keep-result=no;
            } else={ 
                /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"ERROR_USUARIO_NO_ENCONTRADO\" keep-result=no;
            }
        }";

        $this->command = $rawCommand;
        $this->executeCommand($tid);
        
        // Limpiamos el campo de usuario tras enviar
        $this->update_username = "";
    }

    protected function getPresetCommand($key, $mac, $tid) {
        $presets = [
            "identity"   => ":local val [/system identity get name]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"\$val\" keep-result=no",
            "cpu"        => ":local val [/system resource get cpu-load]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"CPU Load: \$val%\" keep-result=no",
            "uptime"     => ":local val [/system resource get uptime]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"Uptime: \$val\" keep-result=no",
            "usuarios"   => ":local val [/ip hotspot user count-only]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"Total Users: \$val\" keep-result=no"
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

        // COMANDO EN UNA SOLA LÍNEA SIN SALTOS NI ESPACIOS EXTRA AL FINAL
        $this->command = "/ip hotspot user add name=\"{$this->new_username}\" password=\"{$this->new_password}\" profile=\"{$this->new_profile}\" comment=\"Test Bridge\";/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\"USUARIO_CREADO_OK\" keep-result=no";
        
        $this->executeCommand($tid);
        $this->new_username = "";
    }

    public function executeCommand($existingTid = null) {
        $this->validate([
            "router_id" => "required",
            "command"   => "required"
        ]);

        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = $existingTid ?? "MAN" . uniqid();
        
        $this->terminal_output = ">>> EJECUTANDO EN: " . strtoupper($router->identity) . " (TID: $tid)\n";

        try {
            // LIMPIEZA EXTREMA DEL COMANDO: Elimina saltos de línea y tabulaciones
            $cleanCommand = trim(preg_replace('/\s+/', ' ', $this->command));

            $response = Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody($cleanCommand, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$response->successful()) throw new \Exception("Bridge Offline.");

            $this->terminal_output .= ">>> ESPERANDO RESPUESTA DEL MIKROTIK...\n";

            $confirmado = false;
            for ($i = 0; $i < 15; $i++) {
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                
                if ($res->successful() && $res->json('status') === 'ready') {
                    $this->terminal_output .= "\n--- RESPUESTA ---\n" . $res->json('data');
                    $confirmado = true;
                    break;
                }
            }

            if (!$confirmado) {
                $this->terminal_output .= "\n>>> TIMEOUT: El router no devolvió el resultado.";
            }

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