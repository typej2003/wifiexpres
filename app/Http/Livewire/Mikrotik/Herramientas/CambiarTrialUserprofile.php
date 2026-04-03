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
    
    public $uptime_actual = null;
    public $uptime_seleccionado = "00:05:00"; 
    
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

    protected function emitirAlSocket($comando, $mac, $tid)
    {
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

    protected function esperarRespuesta($mac, $tid)
    {
        set_time_limit(90);
        for ($i = 0; $i < 60; $i++) {
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

    public function updatedRouterId()
    {
        $this->reset(['perfiles', 'perfil_actual', 'perfil_seleccionado', 'uptime_actual', 'message']);
        if($this->router_id && ($this->routerStatus[$this->router_id] ?? false)) {
            $this->consultarPerfilActual();
        }
    }

    public function consultarPerfilActual()
    {
        if (!$this->router_id) return;
        $this->loading = true;
        $this->message = "Consultando configuración...";
        
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "GETTRL_" . time();

        // Usando formato de post-result con http-data como en tu código funcional
        $comando = ":local m \"$mac\"; :local t \"$tid\"; " .
                   ":local p [/ip hotspot profile get [find name=\"hsprof1\"] trial-user-profile]; " .
                   ":local u [/ip hotspot profile get [find name=\"hsprof1\"] trial-uptime-limit]; " .
                   ":if ([:len \$u] = 0) do={ :set u \"00:00:00\" }; " .
                   ":local res (\$p . \"|\" . \$u); " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$res keep-result=no;";

        try {
            $this->emitirAlSocket($comando, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);
            
            if ($res && str_contains($res, '|')) {
                $parts = explode('|', $res);
                $this->perfil_actual = $parts[0];
                $this->uptime_actual = $parts[1];
                $this->uptime_seleccionado = $parts[1];
                $this->message = "✅ Datos obtenidos.";
            } else {
                $this->message = "⚠️ Respuesta incompleta del router.";
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
        $this->message = "Cargando perfiles...";

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "LST_" . time();

        $comando = ":local m \"$mac\"; :local t \"$tid\"; :local res \"LISTA:\"; " .
                   ":foreach i in=[/ip hotspot user profile find] do={ " .
                   ":set res (\$res . [/ip hotspot user profile get \$i name] . \",\"); " .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$res keep-result=no;";

        try {
            $this->emitirAlSocket($comando, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);

            if ($res) {
                $raw = str_replace('LISTA:', '', $res);
                $this->perfiles = array_filter(explode(',', trim($raw, ",")));
                $this->message = "✅ Lista cargada.";
            } else {
                $this->message = "⚠️ No se recibió la lista.";
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
        $tid = "SET_" . time();

        // Formato robusto con :do y on-error enviando SUCCESS/FAIL
        $comando = ":local m \"$mac\"; :local t \"$tid\"; " .
                   ":do { /ip hotspot profile set [find name=\"hsprof1\"] " .
                   "trial-user-profile=\"{$this->perfil_seleccionado}\" " .
                   "trial-uptime-limit=\"{$this->uptime_seleccionado}\"; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"SUCCESS\" keep-result=no; " .
                   "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

        try {
            $this->emitirAlSocket($comando, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);

            if ($res === 'SUCCESS') {
                $this->message = "✅ Configuración Trial actualizada.";
                $this->perfil_actual = $this->perfil_seleccionado;
                $this->uptime_actual = $this->uptime_seleccionado;
            } else {
                $this->message = "❌ Error: MikroTik devolvió FAIL.";
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