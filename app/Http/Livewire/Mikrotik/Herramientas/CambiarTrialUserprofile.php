<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Http;

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
        } catch (\Exception $e) { $this->routerStatus = []; }
    }

    // Limpiar datos si cambia el router, pero NO consultar nada automáticamente
    public function updatedRouterId()
    {
        $this->reset(['perfiles', 'perfil_actual', 'perfil_seleccionado', 'message']);
    }

    /**
     * BOTÓN 1: Consulta exclusivamente el perfil configurado en hsprof1
     */
    public function consultarPerfilActual()
    {
        if (!$this->router_id) return;

        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "GETCUR_" . time();

        $comando = ":local current [/ip hotspot profile get [find name=\"hsprof1\"] trial-user-profile]; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid&data=\$current\" keep-result=no;";

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comando, 'text/plain')->post("{$this->bridgeUrl}/set-command");

            for ($i = 0; $i < 10; $i++) {
                usleep(800000);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $this->perfil_actual = $res->json('data');
                    $this->loading = false;
                    return;
                }
            }
        } catch (\Exception $e) { }
        $this->loading = false;
        $this->message = "❌ No se pudo obtener el perfil actual.";
    }

    /**
     * BOTÓN 2: Consulta la lista de perfiles disponibles en el sistema
     */
    public function obtenerListaPerfiles()
    {
        if (!$this->router_id) return;

        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "GETLST_" . time();

        $comando = ":local res \"P:\"; :foreach i in=[/ip hotspot user profile find] do={ " .
                   ":set res (\$res . [/ip hotspot user profile get \$i name] . \",\"); " .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comando, 'text/plain')->post("{$this->bridgeUrl}/set-command");

            for ($i = 0; $i < 10; $i++) {
                usleep(800000);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $raw = str_replace('P:', '', $res->json('data'));
                    $this->perfiles = array_filter(explode(',', trim($raw, ",")));
                    $this->loading = false;
                    return;
                }
            }
        } catch (\Exception $e) { }
        $this->loading = false;
        $this->message = "❌ No se pudo cargar la lista de perfiles.";
    }

    public function aplicarCambio()
    {
        $this->validate(['router_id' => 'required', 'perfil_seleccionado' => 'required']);

        $this->loading = true;
        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "SETTRL_" . time();

        $comando = ":do { /ip hotspot profile set [find name=\"hsprof1\"] trial-user-profile=\"{$this->perfil_seleccionado}\"; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid&data=OK\" keep-result=no; " .
                   "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid&data=ERROR\" keep-result=no; }";

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comando, 'text/plain')->post("{$this->bridgeUrl}/set-command");

            for ($i = 0; $i < 10; $i++) {
                usleep(800000);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    if($res->json('data') == 'OK') {
                        $this->message = "✅ Perfil Trial actualizado correctamente.";
                        $this->perfil_actual = $this->perfil_seleccionado;
                        $this->perfil_seleccionado = null;
                    } else {
                        $this->message = "❌ Error al aplicar el perfil.";
                    }
                    $this->loading = false;
                    return;
                }
            }
        } catch (\Exception $e) { $this->message = "❌ Error de comunicación."; }
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