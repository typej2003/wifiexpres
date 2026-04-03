<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CambiarTrialUserprofile extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    public $routerStatus = [];
    public $perfiles = [];
    public $perfil_seleccionado = null;
    public $perfil_actual = null;
    public $loading = false;
    public $message = null;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
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

    // --- MÉTODOS DE COMUNICACIÓN OPTIMIZADOS (Lógica de Diagnóstico) ---

    protected function emitirAlSocket($comando, $mac, $tid)
    {
        try {
            // Limpia el comando de saltos de línea y espacios extra
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

    protected function esperarRespuesta($mac, $tid)
    {
        set_time_limit(60); // Ajustado a 60s para no colgar el servidor
        for ($i = 0; $i < 20; $i++) {
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

    // --- ACCIONES DEL COMPONENTE ---

    public function updatedRouterId()
    {
        $this->reset(['perfiles', 'perfil_actual', 'perfil_seleccionado', 'message']);
        if($this->router_id) {
            $this->consultarPerfilActual();
        }
    }

    public function consultarPerfilActual()
    {
        if (!$this->router_id) return;
        $this->loading = true;
        $this->message = "Consultando perfil actual...";
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "GETCUR_" . time();

        // Comando simplificado y robusto
        $comando = ":local m \"$mac\"; :local t \"$tid\"; " .
                   ":local res [/ip hotspot profile get [find name=\"hsprof1\"] trial-user-profile]; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t&data=\$res\" keep-result=no;";

        try {
            $this->emitirAlSocket($comando, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);
            
            if ($res) {
                $this->perfil_actual = $res;
                $this->message = "✅ Perfil actual obtenido.";
            } else {
                $this->message = "⚠️ No se recibió respuesta del perfil actual.";
            }
        } catch (\Exception $e) { 
            $this->message = "❌ " . $e->getMessage();
        }
        $this->loading = false;
    }

    public function obtenerListaPerfiles()
    {
        if (!$this->router_id) return;
        $this->loading = true;
        $this->message = "Obteniendo lista de perfiles...";

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "GETLST_" . time();

        $comando = ":local m \"$mac\"; :local t \"$tid\"; :local res \"P:\"; " .
                   ":foreach i in=[/ip hotspot user profile find] do={ " .
                   ":set res (\$res . [/ip hotspot user profile get \$i name] . \",\"); " .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$res keep-result=no;";

        try {
            $this->emitirAlSocket($comando, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);

            if ($res) {
                $raw = str_replace('P:', '', $res);
                $this->perfiles = array_filter(explode(',', trim($raw, ",")));
                $this->message = "✅ Lista de perfiles actualizada.";
            } else {
                $this->message = "⚠️ Timeout: MikroTik no envió la lista.";
            }
        } catch (\Exception $e) { 
            $this->message = "❌ " . $e->getMessage();
        }
        $this->loading = false;
    }

    public function aplicarCambio()
    {
        $this->validate(['router_id' => 'required', 'perfil_seleccionado' => 'required']);
        $this->loading = true;

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "SETTRL_" . time();

        $comando = ":local m \"$mac\"; :local t \"$tid\"; " .
                   ":do { /ip hotspot profile set [find name=\"hsprof1\"] trial-user-profile=\"{$this->perfil_seleccionado}\"; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t&data=OK\" keep-result=no; " .
                   "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t&data=ERROR\" keep-result=no; }";

        try {
            $this->emitirAlSocket($comando, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);

            if ($res === 'OK') {
                $this->message = "✅ Perfil Trial actualizado con éxito.";
                $this->perfil_actual = $this->perfil_seleccionado;
                $this->perfil_seleccionado = null;
            } else {
                $this->message = "❌ Error en MikroTik al aplicar cambio.";
            }
        } catch (\Exception $e) { 
            $this->message = "❌ " . $e->getMessage(); 
        }
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.cambiar-trial-userprofile', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))->get()
        ])->layout('layouts.app');
    }
}