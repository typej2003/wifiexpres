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
    public $orphansCount = 0;

    public $plan_id, $price, $rate_limit, $old_mikrotik_name;
    public $tiempo_display = '1 Hora', $session_timeout = '01:00:00';
    public $shared_users = 1;

    // PARÁMETROS DE CONFIGURACIÓN
    public $idle_timeout = 'none';
    public $status_autorefresh = '00:01:00';
    public $add_mac_cookie = 'yes'; // Nuevo
    public $mac_cookie_timeout = '03:00:00'; // Nuevo

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
            $response = Http::withHeaders([
                'x-mac' => $mac,
                'x-id'  => $tid
            ])
            ->withBody($comandoLimpio, 'text/plain')
            ->post("{$this->bridgeUrl}/set-command");

            if (!$response->successful()) throw new \Exception("Bridge Offline");
            return true;
        } catch (\Exception $e) {
            Log::error("Error Bridge en PlanManager: " . $e->getMessage());
            throw new \Exception("Error al conectar con el Bridge.");
        }
    }

    protected function esperarRespuesta($mac, $tid)
    {
        for ($i = 0; $i < 60; $i++) {
            sleep(1);
            try {
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", [
                    'mac' => $mac,
                    'tid' => $tid
                ]);

                if ($res->successful() && $res->json('status') === 'ready') { 
                    return $res->json('data');
                }
            } catch (\Exception $e) { }
        }
        return null;
    }

    public function store()
    {
        $this->validate(['price' => 'required|numeric', 'tiempo_display' => 'required']);
        
        $precioEntero = (int)$this->price;
        $name = "{$this->tiempo_display}-{$precioEntero}";
        
        $tid = "PLAN" . time(); 
        $macActual = strtoupper($this->router->macAddress);

        $this->isModalOpen = false;
        session()->flash('message', "Sincronizando con MikroTik (espera 60s)...");

        try {
            $u = "\\24user";
            $a = "\\24address";
            $onLogin = ":global gUser $u; :global gAddr $a; :global gType login; /system script run log-event";
            $onLogout = ":global gUser $u; :global gAddr $a; :global gType logout; /system script run log-event";

            $accion = $this->plan_id ? "set [find name=\"$this->old_mikrotik_name\"]" : "add";
            $rate = $this->rate_limit ? "rate-limit=\"$this->rate_limit\"" : "";

            // Comando incluyendo todos los parámetros solicitados
            $fullCmd = ":do { /ip hotspot user profile $accion name=\"$name\" session-timeout=$this->session_timeout idle-timeout=$this->idle_timeout status-autorefresh=$this->status_autorefresh add-mac-cookie=$this->add_mac_cookie mac-cookie-timeout=$this->mac_cookie_timeout shared-users=$this->shared_users on-login=\"$onLogin\" on-logout=\"$onLogout\" $rate; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\"SUCCESS\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            $this->emitirAlSocket($fullCmd, $macActual, $tid);
            $respuestaData = $this->esperarRespuesta($macActual, $tid);

            if ($respuestaData === "SUCCESS") {
                Plan::updateOrCreate(
                    ['id' => $this->plan_id],
                    [
                        'router_id' => $this->router->id, 
                        'name' => $name, 
                        'mikrotik_profile' => $name, 
                        'price' => $precioEntero, 
                        'session_timeout' => $this->session_timeout, 
                        'idle_timeout' => $this->idle_timeout,
                        'status_autorefresh' => $this->status_autorefresh,
                        'add_mac_cookie' => ($this->add_mac_cookie === 'yes'),
                        'mac_cookie_timeout' => $this->mac_cookie_timeout,
                        'rate_limit' => $this->rate_limit, 
                        'shared_users' => $this->shared_users,
                        'is_active' => true
                    ]
                );
                session()->flash('message', "¡Perfil '$name' configurado correctamente!");
            } else {
                session()->forget('message');
                $errorMsg = ($respuestaData === 'FAIL') ? "El MikroTik rechazó el comando." : "Timeout: El router no confirmó.";
                throw new \Exception($errorMsg);
            }

        } catch (\Exception $e) { 
            session()->forget('message');
            session()->flash('error', $e->getMessage()); 
        }
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        $name = $plan->mikrotik_profile;
        $macActual = strtoupper($this->router->macAddress);
        $tid = "DEL" . time();

        session()->flash('message', "Eliminando perfil '$name' en MikroTik...");

        try {
            $fullCmd = ":do { /ip hotspot user profile remove [find name=\"$name\"]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\"SUCCESS\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\"FAIL\" keep-result=no; };";
            $this->emitirAlSocket($fullCmd, $macActual, $tid);
            $respuestaData = $this->esperarRespuesta($macActual, $tid);

            if ($respuestaData === "SUCCESS") {
                $plan->delete();
                session()->flash('message', "Perfil '$name' eliminado correctamente.");
            } else {
                session()->forget('message');
                throw new \Exception("Error al eliminar en MikroTik.");
            }
        } catch (\Exception $e) {
            session()->forget('message');
            session()->flash('error', $e->getMessage());
        }
    }

    public function openSyncModal() 
    {
        $this->mikrotikProfiles = []; 
        $macActual = strtoupper($this->router->macAddress);
        $tid = "SYNC" . time();

        // Comando de lectura extendido con cookies
        $comando = ":local res \"LISTA:\"; :foreach i in=[/ip hotspot user profile find where name!=\"default\" and name!=\"neutro\"] do={ :local n [/ip hotspot user profile get \$i name]; :local s [/ip hotspot user profile get \$i shared-users]; :local t [/ip hotspot user profile get \$i session-timeout]; :local idl [/ip hotspot user profile get \$i idle-timeout]; :local sar [/ip hotspot user profile get \$i status-autorefresh]; :local amc [/ip hotspot user profile get \$i add-mac-cookie]; :local mct [/ip hotspot user profile get \$i mac-cookie-timeout]; :local r [/ip hotspot user profile get \$i rate-limit]; :if ([:len \$t] = 0) do={ :set t \"00:00:00\" }; :if ([:len \$idl] = 0) do={ :set idl \"none\" }; :if ([:len \$sar] = 0) do={ :set sar \"00:00:00\" }; :if ([:len \$r] = 0) do={ :set r \"unlimited\" }; :set res (\$res . \$n . \",\" . \$s . \",\" . \$t . \",\" . \$idl . \",\" . \$sar . \",\" . \$amc . \",\" . \$mct . \",\" . \$r . \"|\"); }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        try {
            $this->emitirAlSocket($comando, $macActual, $tid);
            $respuestaData = $this->esperarRespuesta($macActual, $tid);

            if ($respuestaData) {
                $datos = str_replace('LISTA:', '', $respuestaData);
                $filas = array_filter(explode('|', trim($datos, "| ")));
                foreach ($filas as $fila) {
                    $p = explode(',', $fila);
                    if (count($p) >= 7) {
                        $extraerPrecio = explode('-', $p[0]);
                        $precioFinal = isset($extraerPrecio[1]) ? (int)$extraerPrecio[1] : 0;

                        $this->mikrotikProfiles[] = [
                            'name' => $p[0], 
                            'shared_users' => $p[1], 
                            'session_timeout' => $p[2], 
                            'idle_timeout' => $p[3],
                            'status_autorefresh' => $p[4],
                            'add_mac_cookie' => $p[5],
                            'mac_cookie_timeout' => $p[6],
                            'rate_limit' => $p[7], 
                            'price' => $precioFinal
                        ];
                    }
                }
                $this->isSyncModalOpen = true; 
            } else {
                session()->flash('error', "No se recibió respuesta del MikroTik.");
            }
        } catch (\Exception $e) { 
            session()->flash('error', $e->getMessage()); 
        }
    }

    public function syncDatabase($depurar = false) 
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
                        'idle_timeout' => ($mp['idle_timeout'] === 'none') ? 'none' : $mp['idle_timeout'],
                        'status_autorefresh' => $mp['status_autorefresh'],
                        'add_mac_cookie' => ($mp['add_mac_cookie'] === 'true' || $mp['add_mac_cookie'] === 'yes'),
                        'mac_cookie_timeout' => $mp['mac_cookie_timeout'],
                        'shared_users' => $mp['shared_users'], 
                        'rate_limit' => ($mp['rate_limit'] === 'unlimited') ? null : $mp['rate_limit'], 
                        'is_active' => true
                    ]
                );
            }
            session()->flash('message', "Sincronizado con la base de datos."); 
            $this->closeModal();
        } catch (\Exception $e) { session()->flash('error', $e->getMessage()); }
    }

    public function create() 
    { 
        $this->reset(['plan_id', 'price', 'rate_limit', 'old_mikrotik_name', 'idle_timeout', 'status_autorefresh']); 
        $this->idle_timeout = 'none';
        $this->status_autorefresh = '00:01:00';
        $this->add_mac_cookie = 'yes';
        $this->mac_cookie_timeout = '03:00:00';
        $this->isModalOpen = true; 
    }
    
    public function edit($id) 
    {
        $plan = Plan::findOrFail($id);
        $this->plan_id = $id; 
        $this->price = $plan->price; 
        $this->tiempo_display = explode('-', $plan->name)[0] ?? $plan->name;
        $this->session_timeout = $plan->session_timeout; 
        $this->idle_timeout = $plan->idle_timeout ?? 'none';
        $this->status_autorefresh = $plan->status_autorefresh ?? '00:01:00';
        $this->add_mac_cookie = $plan->add_mac_cookie ? 'yes' : 'no';
        $this->mac_cookie_timeout = $plan->mac_cookie_timeout ?? '03:00:00';
        $this->rate_limit = $plan->rate_limit; 
        $this->shared_users = $plan->shared_users;
        $this->old_mikrotik_name = $plan->mikrotik_profile; 
        $this->isModalOpen = true;
    }

    public function solicitarIdentity()
    {
        $macActual = strtoupper($this->router->macAddress);
        $tid = "IDN" . time();
        $comando = ":local sysName [/system identity get name]; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\"\$sysName\" keep-result=no;";

        try {
            $this->emitirAlSocket($comando, $macActual, $tid);
            $respuesta = $this->esperarRespuesta($macActual, $tid);
            if ($respuesta) {
                session()->flash('message', "Respuesta del Router: " . $respuesta);
            } else {
                session()->flash('error', "El Router no respondió.");
            }
        } catch (\Exception $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function closeModal() { $this->isModalOpen = false; $this->isSyncModalOpen = false; }

    public function backToRouters() 
    { 
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.routers.index');
        }
        return redirect()->route('aliado.routers');
    }

    public function render() 
    { 
        return view('livewire.mikrotik.aliado.plan-manager', [
            'plans' => Plan::where('router_id', $this->router->id)->get()
        ]); 
    }
}