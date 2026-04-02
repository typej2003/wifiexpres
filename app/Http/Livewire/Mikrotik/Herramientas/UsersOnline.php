<?php

namespace App\Http\Livewire\Mikrotik\Herramientas;

use Livewire\Component;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UsersOnline extends Component
{
    public $selectedAliado = null;
    public $router_id = null;
    public $routerStatus = [];
    public $users = [];
    public $loading = false;
    public $error_message = null;
    public $success_message = null;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        $this->refreshRouterStatus();
    }

    public function refreshRouterStatus()
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

    public function updatedSelectedAliado()
    {
        $this->router_id = null;
        $this->users = [];
        $this->error_message = null;
    }

    public function scanUsers()
    {
        $this->validate(['router_id' => 'required']);
        
        $this->loading = true;
        $this->users = [];
        $this->error_message = null;
        $this->success_message = null;

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "SCAN_" . time();

        $comando = ":local res \"D:\"; :foreach i in=[/ip hotspot active find] do={ " .
                   ":local u [/ip hotspot active get \$i user]; " .
                   ":local a [/ip hotspot active get \$i address]; " .
                   ":local m [/ip hotspot active get \$i mac-address]; " .
                   ":local t [/ip hotspot active get \$i uptime]; " .
                   ":local c [/ip hotspot user get [find name=\$u] comment]; " .
                   ":set res (\$res . \$u . \",\" . \$a . \",\" . \$m . \",\" . \$t . \",\" . \$c . \"|\"); " .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comando, 'text/plain')->post("{$this->bridgeUrl}/set-command");

            for ($i = 0; $i < 15; $i++) {
                usleep(1000000);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                
                if ($res->successful() && $res->json('status') === 'ready') {
                    $rawData = $res->json('data');
                    if (str_contains($rawData, 'D:')) {
                        $this->parseUsers($rawData);
                        $this->loading = false;
                        return;
                    }
                }
            }
            $this->error_message = "El router no devolvió datos en el tiempo esperado.";
        } catch (\Exception $e) {
            $this->error_message = "Error: " . $e->getMessage();
        }
        $this->loading = false;
    }

    /**
     * MÉTODO NUEVO: Remueve un usuario activo del Hotspot
     */
    public function removeUser($username)
    {
        if (!$this->router_id) return;

        $this->loading = true;
        $this->error_message = null;
        $this->success_message = null;

        $router = Router::findOrFail($this->router_id);
        $mac = strtoupper(trim($router->macAddress));
        $tid = "REM_" . time();

        // Comando MikroTik para remover por nombre de usuario
        $comando = ":do { /ip hotspot active remove [find user=\"$username\"]; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid&data=OK\" keep-result=no; " .
                   "} on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid&data=ERROR\" keep-result=no; }";

        try {
            Http::withHeaders(['x-mac' => $mac, 'x-id' => $tid])->withBody($comando, 'text/plain')->post("{$this->bridgeUrl}/set-command");

            for ($i = 0; $i < 10; $i++) {
                usleep(1000000);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                
                if ($res->successful() && $res->json('status') === 'ready') {
                    if ($res->json('data') === 'OK') {
                        $this->success_message = "Usuario '$username' desconectado exitosamente.";
                        $this->scanUsers(); // Refrescar lista automáticamente
                    } else {
                        $this->error_message = "MikroTik no pudo remover al usuario.";
                    }
                    $this->loading = false;
                    return;
                }
            }
            $this->error_message = "No se recibió confirmación de la expulsión.";
        } catch (\Exception $e) {
            $this->error_message = "Error: " . $e->getMessage();
        }
        $this->loading = false;
    }

    private function parseUsers($raw)
    {
        $datos = str_replace('D:', '', $raw);
        $filas = array_filter(explode('|', trim($datos, "| ")));
        $tempUsers = [];

        foreach ($filas as $fila) {
            $p = explode(',', $fila);
            if (count($p) >= 4) {
                $tempUsers[] = [
                    'username' => $p[0],
                    'ip'       => $p[1],
                    'mac'      => $p[2],
                    'uptime'   => $p[3],
                    'comment'  => $p[4] ?? 'N/A'
                ];
            }
        }
        $this->users = $tempUsers;
    }

    public function render()
    {
        return view('livewire.mikrotik.herramientas.users-online', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::when($this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))->get()
        ])->layout('layouts.app');
    }
}