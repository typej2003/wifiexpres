<?php

namespace App\Http\Livewire\Dashboards;

use Livewire\Component;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Router;
use App\Models\Setting;
use RouterOS\Client;
use RouterOS\Query;
use Illuminate\Support\Facades\Session;

class TicketDashboard extends Component
{
    public $ticket;
    public $router;
    public $newPassword;
    public $stats = [
        "uptime" => "00:00:00",
        "limit_uptime" => "00:00:00",
        "status" => "Desconectado"
    ];

    public function mount()
    {
        $ticketId = Session::get("ticket_id");

        if (!$ticketId) {
            return redirect()->route("ticket.login");
        }

        $this->ticket = Ticket::find($ticketId);

        if (!$this->ticket) {
            Session::flush();
            return redirect()->route("ticket.login");
        }

        $this->router = Router::find($this->ticket->router_id);
        
        if ($this->router) {
            $this->refreshStats();
        }
    }

    /**
     * Genera la configuración de conexión considerando modo Local o Remoto
     */
    private function getMikrotikConfig()
    {
        // Buscamos el ajuste global del administrador
        $user = User::where('role', 'admin')->first();
        $setting = Setting::where("user_id", $user->id)->first();
        
        // Determinamos el modo de conexión (1 = Remoto, 0 = Local)
        $connectionMode = $setting ? (int) $setting->mikrotik_connection_mode : 0;

        // Si el modo es Remoto (1) y el DNS no está vacío, usamos DNS. De lo contrario, usamos IP.
        $host = ($connectionMode === 1 && !empty($this->router->dns)) ? $this->router->dns : $this->router->ip;
        
        return [
            "host"    => $host,
            "user"    => $this->router->admin,
            "pass"    => $this->router->password,
            "port"    => (int) ($this->router->api_port ?? 49152),
            "timeout" => 5
        ];
    }

    public function refreshStats()
    {
        if (!$this->router || !$this->ticket) return;
        
        $config = null;

        try {
            
            $config = $this->getMikrotikConfig();

            $client = new Client($config);

            $query = (new Query("/ip/hotspot/user/print"))->where("name", $this->ticket->username);
            $response = $client->query($query)->read();

            // Validación para evitar "Trying to access array offset on value of type null"
            if (is_array($response) && !empty($response) && isset($response[0])) {
                $this->stats["uptime"] = $response[0]["uptime"] ?? "0s";
                $this->stats["limit_uptime"] = $response[0]["limit-uptime"] ?? "Ilimitado";
                
                $queryActive = (new Query("/ip/hotspot/active/print"))->where("user", $this->ticket->username);
                $active = $client->query($queryActive)->read();
                
                $this->stats["status"] = (is_array($active) && !empty($active)) ? "Conectado" : "Fuera de línea";
            } else {
                $this->stats["status"] = "No encontrado";
            }

        } catch (\Exception $e) {
            $hostInfo = $config ? $config["host"] : "desconocido";
            $this->stats["status"] = "Error de enlace";
            session()->flash("error", "No se pudo conectar con el Router en " . $hostInfo);
        }
    }

    public function changePassword()
    {
        if (empty($this->newPassword)) {
            session()->flash("error", "Escribe una nueva contraseña.");
            return;
        }

        try {
            $client = new Client($this->getMikrotikConfig());

            $queryMk = (new Query("/ip/hotspot/user/print"))->where("name", $this->ticket->username);
            $userMk = $client->query($queryMk)->read();

            if (is_array($userMk) && !empty($userMk) && isset($userMk[0][".id"])) {
                $client->query((new Query("/ip/hotspot/user/set"))
                    ->equal(".id", $userMk[0][".id"])
                    ->equal("password", $this->newPassword))->read();

                $this->ticket->update(["password" => $this->newPassword]);
                
                session()->flash("message", "¡Contraseña actualizada correctamente!");
                $this->newPassword = "";
            } else {
                session()->flash("error", "Usuario no encontrado en el sistema MikroTik.");
            }
        } catch (\Exception $e) {
            session()->flash("error", "Error al sincronizar con el router.");
        }
    }

    public function render()
    {
        return view("livewire.dashboards.ticket-dashboard");
    }
}