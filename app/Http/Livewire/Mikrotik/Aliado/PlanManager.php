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
    public $plan_id, $price, $rate_limit, $old_mikrotik_name;
    public $tiempo_display = '1 Hora', $session_timeout = '01:00:00';
    public $shared_users = 1;
    public $address_pool = 'none', $status_autorefresh = '00:01:00';
    public $keepalive_timeout = '00:02:00', $idle_timeout = '00:05:00', $mac_cookie_timeout = '3d 00:00:00';

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount($router)
    {
        $this->router = is_numeric($router) ? Router::findOrFail($router) : $router;
    }

    public function store()
    {
        set_time_limit(120);
        $this->validate(['price' => 'required|numeric', 'tiempo_display' => 'required']);
        
        $precioEntero = (int)$this->price;
        $name = "{$this->tiempo_display}-{$precioEntero}";
        $tid = "PLAN" . time(); 
        $macActual = strtoupper($this->router->macAddress);

        $this->isModalOpen = false;
        session()->flash('message', "Sincronizando...");

        try {
            // Variables de RouterOS escapadas para una sola línea
            $u = "\\24user"; $a = "\\24address";
            $onL = ":global gUser $u; :global gAddr $a; :global gType login; /system script run log-event";
            $onO = ":global gUser $u; :global gAddr $a; :global gType logout; /system script run log-event";
            
            $accion = $this->plan_id ? "set [find name=\"$this->old_mikrotik_name\"]" : "add";
            $rate = $this->rate_limit ? "rate-limit=\"$this->rate_limit\"" : "";

            // COMANDO EN UNA SOLA LÍNEA SIN SALTOS NI ESPACIOS EXTRAS
            $fullCmd = ":do { /ip hotspot user profile $accion name=\"$name\" session-timeout=$this->session_timeout idle-timeout=$this->idle_timeout keepalive-timeout=$this->keepalive_timeout status-autorefresh=$this->status_autorefresh shared-users=$this->shared_users address-pool=\"$this->address_pool\" mac-cookie-timeout=$this->mac_cookie_timeout on-login=\"$onL\" on-logout=\"$onO\" $rate; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\"SUCCESS\" keep-result=no; } on-error={ /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$macActual&tid=$tid\" http-method=post http-data=\"FAIL\" keep-result=no; };";

            // Enviar al Bridge
            $response = Http::withHeaders(['x-mac' => $macActual, 'x-id' => $tid])
                ->withBody($fullCmd, 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if ($response->successful()) {
                // Esperar resultado
                $respuestaData = null;
                for ($i = 0; $i < 40; $i++) {
                    sleep(1);
                    $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $macActual, 'tid' => $tid]);
                    if ($res->successful() && $res->json('status') === 'ready') {
                        $respuestaData = $res->json('data');
                        break;
                    }
                }

                if ($respuestaData === "SUCCESS") {
                    Plan::updateOrCreate(
                        ['id' => $this->plan_id],
                        [
                            'router_id' => $this->router->id, 'name' => $name, 'mikrotik_profile' => $name,
                            'price' => $precioEntero, 'session_timeout' => $this->session_timeout,
                            'idle_timeout' => $this->idle_timeout, 'keepalive_timeout' => $this->keepalive_timeout,
                            'status_autorefresh' => $this->status_autorefresh, 'mac_cookie_timeout' => $this->mac_cookie_timeout,
                            'address_pool' => $this->address_pool, 'rate_limit' => $this->rate_limit, 
                            'shared_users' => $this->shared_users, 'is_active' => true
                        ]
                    );
                    session()->flash('message', "¡Perfil '$name' actualizado con éxito!");
                } else {
                    throw new \Exception($respuestaData === 'FAIL' ? "Error de sintaxis en MikroTik." : "El router no respondió a tiempo.");
                }
            }
        } catch (\Exception $e) { 
            session()->flash('error', $e->getMessage()); 
        }
    }

    public function edit($id) {
        $plan = Plan::findOrFail($id);
        $this->plan_id = $id; 
        $this->price = $plan->price; 
        $this->tiempo_display = explode('-', $plan->name)[0];
        $this->session_timeout = $plan->session_timeout; 
        $this->idle_timeout = $plan->idle_timeout ?? '00:05:00';
        $this->keepalive_timeout = $plan->keepalive_timeout ?? '00:02:00';
        $this->status_autorefresh = $plan->status_autorefresh ?? '00:01:00';
        $this->mac_cookie_timeout = $plan->mac_cookie_timeout ?? '3d 00:00:00';
        $this->address_pool = $plan->address_pool ?? 'none';
        $this->rate_limit = $plan->rate_limit; 
        $this->shared_users = $plan->shared_users;
        $this->old_mikrotik_name = $plan->mikrotik_profile; 
        $this->isModalOpen = true;
    }

    public function create() { 
        $this->reset(['plan_id', 'price', 'rate_limit', 'old_mikrotik_name', 'address_pool', 'status_autorefresh', 'keepalive_timeout', 'idle_timeout', 'mac_cookie_timeout']); 
        $this->isModalOpen = true; 
    }

    public function closeModal() { $this->isModalOpen = false; }
    public function render() { return view('livewire.mikrotik.aliado.plan-manager', ['plans' => Plan::where('router_id', $this->router->id)->get()]); }
}