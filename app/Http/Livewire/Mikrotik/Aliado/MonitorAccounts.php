<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\TicketLog;
use App\Models\AntennaMapping;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class MonitorAccounts extends Component
{
    public $aliadoId;
    public $aliados = [];
    public $routers = [];
    public $results = []; 
    public $loading = false;
    public $viewMode = 'database';

    public function mount()
    {
        $this->aliados = User::where('role', 'aliado')->orderBy('name')->get();
    }

    public function updatedAliadoId($value)
    {
        $this->results = [];
        $this->viewMode = 'database';
        if ($value) {
            $this->routers = Router::where('user_id', $value)->get();
            $this->loadFromDatabase(); 
        } else {
            $this->routers = [];
        }
    }

    public function loadFromDatabase()
    {
        $this->loading = true;
        $this->viewMode = 'database';
        $this->results = [];

        foreach ($this->routers as $router) {
            // Consulta: Usuarios en línea O desconectados hace menos de 5 minutos
            $latestLogs = TicketLog::where('router_id', $router->id)
                ->where(function($query) {
                    $query->whereNull('disconnected_at')
                          ->orWhere('disconnected_at', '>=', Carbon::now()->subMinutes(5));
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $usuarios = [];
            foreach ($latestLogs as $log) {
                $statusIcon = $log->disconnected_at ? '🔴' : '🟢';
                $usuarios[] = [
                    'user'    => $log->username,
                    'ip'      => $log->mac_address, // IP real del usuario
                    'mac'     => $log->user_ip ?? 'N/A', // Lo que sea que haya en user_ip
                    'uptime'  => $log->disconnected_at 
                                 ? 'Off: ' . Carbon::parse($log->disconnected_at)->format('H:i') 
                                 : 'On: ' . $log->created_at->format('H:i'),
                    'antenna' => $log->ubicacion_fisica,
                    'online'  => $log->disconnected_at ? false : true
                ];
            }

            $this->results[$router->id] = [
                'db_name'   => $router->comercio_nombre ?? $router->identity,
                'identity'  => $router->identity,
                'location'  => $router->location ?? 'Sin ubicación',
                'users'     => $usuarios
            ];
        }
        $this->loading = false;
    }

    protected function enviarComando($mac, $tid)
    {
        $comando = ":local iden [/system identity get name]; :local res (\"IDEN:\" . \$iden . \"|MONITOR:\"); :foreach i in=[/ip hotspot active find] do={ :local u [/ip hotspot active get \$i user]; :local a [/ip hotspot active get \$i address]; :local m [/ip hotspot active get \$i mac-address]; :local t [/ip hotspot active get \$i uptime]; :set res (\$res . \$u . \",\" . \$a . \",\" . \$m . \",\" . \$t . \"|\"); }; /tool fetch url=\"http://188.95.113.44:3000/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        try {
            Http::withHeaders(['x-mac' => $mac])->withBody($comando, 'text/plain')->post("http://188.95.113.44:3000/set-command");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function scanAllRouters()
    {
        if (empty($this->routers)) return;

        $this->results = [];
        $this->loading = true;
        $this->viewMode = 'realtime';

        foreach ($this->routers as $router) {
            $mac = strtoupper($router->macAddress);
            $tid = "MON" . time() . "_" . $router->id;

            if ($this->enviarComando($mac, $tid)) {
                for ($i = 0; $i < 6; $i++) {
                    sleep(1);
                    $res = Http::get("http://188.95.113.44:3000/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                    
                    if ($res->successful() && $res->json('status') === 'ready') {
                        $rawResponse = $res->json('data');
                        $parts = explode('|MONITOR:', $rawResponse);
                        $identity = str_replace('IDEN:', '', $parts[0] ?? 'Unknown');
                        $userListData = $parts[1] ?? '';

                        $lines = array_filter(explode('|', trim($userListData, "| ")));
                        $usuariosCargados = [];
                        foreach ($lines as $line) {
                            $p = explode(',', $line);
                            if (count($p) >= 2) {
                                $ipUser = $p[1];
                                $ipParts = explode('.', $ipUser);
                                $segmento = (count($ipParts) >= 3) ? $ipParts[0].'.'.$ipParts[1].'.'.$ipParts[2].'.' : $ipUser;

                                $mapeo = AntennaMapping::where('router_id', $router->id)
                                    ->where('ip_address', 'LIKE', $segmento . '%')
                                    ->first();

                                $usuariosCargados[] = [
                                    'user'    => $p[0],
                                    'ip'      => $ipUser,
                                    'mac'     => $p[2] ?? 'N/A',
                                    'uptime'  => $p[3] ?? '00:00:00',
                                    'antenna' => $mapeo ? $mapeo->location_name : 'Antena no mapeada',
                                    'online'  => true
                                ];
                            }
                        }
                        
                        $this->results[$router->id] = [
                            'db_name'   => $router->comercio_nombre ?? $router->identity,
                            'identity'  => $identity,
                            'location'  => $router->location ?? 'Sin ubicación',
                            'users'     => $usuariosCargados
                        ];
                        break; 
                    }
                }
            }
        }
        $this->loading = false;
    }

    public function render()
    {
        return view('livewire.mikrotik.aliado.monitor-accounts');
    }
}