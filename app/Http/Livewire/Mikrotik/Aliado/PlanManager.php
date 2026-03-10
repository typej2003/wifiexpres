<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\Router;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlanManager extends Component
{
    public $router; 
    public $isModalOpen = false;
    public $isSyncModalOpen = false; 
    
    public $mikrotikProfiles = [];
    public $debugRaw = ''; 

    public $plan_id, $price, $rate_limit, $old_mikrotik_name;
    public $tiempo_display = '1 Hora', $session_timeout = '01:00:00';
    public $shared_users = 1;

    public $idle_timeout = 'none';
    public $keepalive_timeout = '00:02:00'; 
    public $status_autorefresh = '00:01:00';
    public $add_mac_cookie = 'yes'; 
    public $mac_cookie_timeout = '03:00:00'; 

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount($router)
    {
        if (is_numeric($router)) {
            $this->router = Router::findOrFail($router);
        } else {
            $this->router = $router;
        }

        if (!$this->router || ($this->router->user_id !== Auth::id() && Auth::user()->role !== 'admin')) {
            abort(403);
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

    public function openSyncModal() 
    {
        $this->reset(['mikrotikProfiles', 'debugRaw']);
        $macActual = strtoupper($this->router->macAddress);
        $tid = "SYNC" . time();

        $comando = ":local m \"$macActual\"; :local t \"$tid\"; :local res \"LISTA:\"; " .
                   ":foreach i in=[/ip hotspot user profile find where name!=\"default\" and name!=\"neutro\" and name!=\"cortesia\" and name!=\"trial\"] do={ " .
                   ":local n [/ip hotspot user profile get \$i name]; :local s [/ip hotspot user profile get \$i shared-users]; " .
                   ":local st [/ip hotspot user profile get \$i session-timeout]; :local idl [/ip hotspot user profile get \$i idle-timeout]; " .
                   ":local kal [/ip hotspot user profile get \$i keepalive-timeout]; :local sar [/ip hotspot user profile get \$i status-autorefresh]; " .
                   ":local amc [/ip hotspot user profile get \$i add-mac-cookie]; :local mct [/ip hotspot user profile get \$i mac-cookie-timeout]; " .
                   ":local r [/ip hotspot user profile get \$i rate-limit]; " .
                   ":if ([:len \$st] = 0) do={ :set st \"00:00:00\" }; :if ([:len \$idl] = 0) do={ :set idl \"none\" }; " .
                   ":if ([:len \$kal] = 0) do={ :set kal \"00:02:00\" }; :if ([:len \$sar] = 0) do={ :set sar \"00:00:00\" }; " .
                   ":if ([:len \$r] = 0) do={ :set r \"none\" }; " .
                   ":set res (\$res . \$n . \",\" . \$s . \",\" . \$st . \",\" . \$idl . \",\" . \$kal . \",\" . \$sar . \",\" . \$amc . \",\" . \$mct . \",\" . \$r . \"|\"); " .
                   "}; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\$res keep-result=no;";

        try {
            $this->emitirAlSocket($comando, $macActual, $tid);
            $respuestaData = $this->esperarRespuesta($macActual, $tid);
            $this->debugRaw = $respuestaData ?: 'TIMEOUT: El router no respondió.';

            if ($respuestaData) {
                $datos = str_replace('LISTA:', '', $respuestaData);
                $filas = array_filter(explode('|', trim($datos, "| ")));
                foreach ($filas as $fila) {
                    $p = explode(',', $fila);
                    if (count($p) >= 9) {
                        $extraerPrecio = explode('-', $p[0]);
                        $precioFinal = isset($extraerPrecio[1]) ? (int)$extraerPrecio[1] : 0;
                        $this->mikrotikProfiles[] = [
                            'name' => $p[0], 
                            'shared_users' => $p[1], 
                            'session_timeout' => $p[2], 
                            'idle_timeout' => $p[3], 
                            'keepalive_timeout' => $p[4], 
                            'status_autorefresh' => $p[5],
                            'add_mac_cookie' => $p[6], 
                            'mac_cookie_timeout' => $p[7], 
                            'rate_limit' => $p[8], 
                            'price' => $precioFinal
                        ];
                    }
                }
            }
            $this->isSyncModalOpen = true; 
        } catch (\Exception $e) { session()->flash('error', $e->getMessage()); }
    }

    public function syncDatabase() 
    {
        try {
            $nombresEnMikrotik = collect($this->mikrotikProfiles)->pluck('name')->toArray();
            Plan::where('router_id', $this->router->id)->whereNotIn('mikrotik_profile', $nombresEnMikrotik)->delete();
            
            foreach ($this->mikrotikProfiles as $mp) {
                Plan::updateOrCreate(
                    ['router_id' => $this->router->id, 'mikrotik_profile' => $mp['name']],
                    [
                        'name' => $mp['name'], 
                        'price' => $mp['price'], 
                        'session_timeout' => $mp['session_timeout'], 
                        'idle_timeout' => $mp['idle_timeout'], 
                        'keepalive_timeout' => $mp['keepalive_timeout'],
                        'status_autorefresh' => $mp['status_autorefresh'], 
                        'add_mac_cookie' => ($mp['add_mac_cookie'] === 'yes' || $mp['add_mac_cookie'] === 'true'),
                        'mac_cookie_timeout' => $mp['mac_cookie_timeout'], 
                        'shared_users' => $mp['shared_users'], 
                        'rate_limit' => ($mp['rate_limit'] === 'none' || $mp['rate_limit'] === 'unlimited') ? null : $mp['rate_limit'], 
                        'is_active' => true
                    ]
                );
            }
            session()->flash('message', "Base de datos sincronizada con éxito."); 
            $this->closeModal();
        } catch (\Exception $e) { session()->flash('error', $e->getMessage()); }
    }

    public function store()
    {
        $this->validate(['price' => 'required|numeric', 'tiempo_display' => 'required']);
        $precioEntero = (int)$this->price;
        $name = "{$this->tiempo_display}-{$precioEntero}";
        $tid = "PLAN" . time(); 
        $macActual = strtoupper($this->router->macAddress);
        $this->isModalOpen = false;

        try {
            $u = "\\24user"; $a = "\\24address";
            $onLogin = ":global gUser $u; :global gAddr $a; :global gType login; /system script run log-event";
            $onLogout = ":global gUser $u; :global gAddr $a; :global gType logout; /system script run log-event";
            $accion = $this->plan_id ? "set [find name=\"$this->old_mikrotik_name\"]" : "add";
            $rate = $this->rate_limit ? "rate-limit=\"$this->rate_limit\"" : "";

            $fullCmd = ":local m \"$macActual\"; :local t \"$tid\"; :do { /ip hotspot user profile $accion name=\"$name\" session-timeout=$this->session_timeout idle-timeout=$this->idle_timeout keepalive-timeout=$this->keepalive_timeout status-autorefresh=$this->status_autorefresh add-mac-cookie=$this->add_mac_cookie mac-cookie-timeout=$this->mac_cookie_timeout shared-users=$this->shared_users on-login=\"$onLogin\" on-logout=\"$onLogout\" $rate; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"SUCCESS\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            $this->emitirAlSocket($fullCmd, $macActual, $tid);
            $res = $this->esperarRespuesta($macActual, $tid);

            if ($res === "SUCCESS") {
                Plan::updateOrCreate(['router_id' => $this->router->id, 'mikrotik_profile' => $name], [
                    'name' => $name, 'price' => $precioEntero, 'session_timeout' => $this->session_timeout, 
                    'idle_timeout' => $this->idle_timeout, 'keepalive_timeout' => $this->keepalive_timeout,
                    'status_autorefresh' => $this->status_autorefresh, 'add_mac_cookie' => ($this->add_mac_cookie === 'yes'),
                    'mac_cookie_timeout' => $this->mac_cookie_timeout, 'rate_limit' => $this->rate_limit, 
                    'shared_users' => $this->shared_users, 'is_active' => true
                ]);
                session()->flash('message', "Plan guardado en MikroTik.");
            } else { throw new \Exception($res === "FAIL" ? "Error en RouterOS." : "Timeout del Router."); }
        } catch (\Exception $e) { session()->flash('error', $e->getMessage()); }
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        $macActual = strtoupper($this->router->macAddress);
        $tid = "DEL" . time();
        try {
            $fullCmd = ":local m \"$macActual\"; :local t \"$tid\"; :do { /ip hotspot user profile remove [find name=\"{$plan->mikrotik_profile}\"]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"SUCCESS\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"FAIL\" keep-result=no; };";
            $this->emitirAlSocket($fullCmd, $macActual, $tid);
            if ($this->esperarRespuesta($macActual, $tid) === "SUCCESS") {
                $plan->delete();
                session()->flash('message', "Plan eliminado.");
            }
        } catch (\Exception $e) { session()->flash('error', $e->getMessage()); }
    }

    public function solicitarIdentity() {
        $mac = strtoupper($this->router->macAddress); $tid = "IDN".time();
        $cmd = ":local m \"$mac\"; :local t \"$tid\"; :local n [/system identity get name]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=\$m&tid=\$t\" http-method=post http-data=\"\$n\" keep-result=no;";
        try {
            $this->emitirAlSocket($cmd, $mac, $tid);
            $res = $this->esperarRespuesta($mac, $tid);
            session()->flash('message', "Identity: " . ($res ?: 'Sin respuesta'));
        } catch (\Exception $e) { session()->flash('error', $e->getMessage()); }
    }

    public function create() { $this->reset(['plan_id', 'price', 'rate_limit', 'old_mikrotik_name']); $this->isModalOpen = true; }
    
    public function edit($id) {
        $p = Plan::findOrFail($id);
        $this->plan_id = $id; 
        $this->price = $p->price; 
        $this->tiempo_display = explode('-', $p->name)[0];
        $this->session_timeout = $p->session_timeout; 
        $this->idle_timeout = $p->idle_timeout;
        $this->keepalive_timeout = $p->keepalive_timeout; 
        $this->status_autorefresh = $p->status_autorefresh;
        $this->add_mac_cookie = $p->add_mac_cookie ? 'yes' : 'no'; 
        $this->mac_cookie_timeout = $p->mac_cookie_timeout;
        $this->rate_limit = $p->rate_limit; 
        $this->shared_users = $p->shared_users;
        $this->old_mikrotik_name = $p->mikrotik_profile; 
        $this->isModalOpen = true;
    }

    public function closeModal() { $this->isModalOpen = false; $this->isSyncModalOpen = false; }
    public function backToRouters() { return redirect()->route(Auth::user()->role === 'admin' ? 'admin.routers.index' : 'aliado.routers'); }
    public function render() { return view('livewire.mikrotik.aliado.plan-manager', ['plans' => Plan::where('router_id', $this->router->id)->get()]); }
}