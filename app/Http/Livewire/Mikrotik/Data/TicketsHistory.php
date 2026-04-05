<?php

namespace App\Http\Livewire\Mikrotik\Data;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Ticket;
use App\Models\Router;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TicketsHistory extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filtros
    public $search = '';
    public $filterAliado = '';
    public $filterRouter = '';
    public $filterPlan = '';
    public $filterEstado = '';
    public $sortDirection = 'desc'; // Para consumo

    // Control de Modal Sincronización
    public $isSyncModalOpen = false;
    public $syncAmount = 50;
    public $showOverlay = false;

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function updatingSearch() { $this->resetPage(); }

    public function toggleSort()
    {
        $this->sortDirection = ($this->sortDirection === 'asc') ? 'desc' : 'asc';
    }

    public function openSyncModal() { $this->isSyncModalOpen = true; }
    public function closeSyncModal() { $this->isSyncModalOpen = false; }

    public function syncData()
    {
        if (!$this->filterRouter) {
            session()->flash('error', 'Seleccione un router para sincronizar.');
            return;
        }

        $this->showOverlay = true;
        $this->isSyncModalOpen = false;

        $router = Router::find($this->filterRouter);
        $mac = strtoupper($router->macAddress);
        $tid = "HSYNC" . time();

        // Comando optimizado para traer solo la cantidad solicitada
        $comando = ":local count 0; :local res \"D:\"; :foreach i in=[/ip hotspot user find where name!=\"default-trial\"] do={ " .
                   ":if (\$count < {$this->syncAmount}) do={ " .
                   ":local n [/ip hotspot user get \$i name]; :local p [/ip hotspot user get \$i password]; " .
                   ":local pr [/ip hotspot user get \$i profile]; :local u [/ip hotspot user get \$i uptime]; " .
                   ":local c [/ip hotspot user get \$i comment]; " .
                   ":set res (\$res . \$n . \",\" . \$p . \",\" . \$pr . \",\" . \$u . \",\" . \$c . \"|\"); " .
                   ":set count (\$count + 1); " .
                   "} }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        try {
            $response = Http::timeout(15)->withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim($comando), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            // Simulación de espera de resultado (basado en tu lógica sendCommandQuick)
            sleep(2); 
            $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
            
            if ($res->successful() && $res->json('status') === 'ready') {
                $raw = $res->json('data');
                $this->processSyncRawData($raw, $router->id);
                session()->flash('message', 'Sincronización de '.$this->syncAmount.' registros completada.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error de conexión con el Bridge.');
        }

        $this->showOverlay = false;
    }

    private function processSyncRawData($raw, $routerId)
    {
        $datos = str_replace('D:', '', $raw);
        $filas = array_filter(explode('|', trim($datos, "| ")));
        $planes = Plan::where('router_id', $routerId)->get()->keyBy('mikrotik_profile');

        foreach ($filas as $fila) {
            $p = explode(',', $fila);
            if (count($p) < 3) continue;

            $uName = $p[0];
            $uptime = $p[3] ?: '0s';
            $planInfo = $planes->get($p[2]);

            Ticket::updateOrCreate(
                ['router_id' => $routerId, 'username' => $uName],
                [
                    'tiempo_consumido' => $uptime,
                    'estado' => ($uptime !== '0s') ? 'en_uso' : 'disponible',
                    'sincronizado' => true
                ]
            );
        }
    }

    public function render()
    {
        $user = Auth::user();
        
        // Query base
        $query = Ticket::query()->with('router');

        // Si es aliado, filtrar solo sus routers
        if ($user->role !== 'admin') {
            $query->whereHas('router', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($this->filterAliado) {
            $query->whereHas('router', function($q) {
                $q->where('user_id', $this->filterAliado);
            });
        }

        // Filtros adicionales
        if ($this->filterRouter) $query->where('router_id', $this->filterRouter);
        if ($this->filterPlan) $query->where('plan', $this->filterPlan);
        if ($this->filterEstado) $query->where('estado', $this->filterEstado);
        if ($this->search) {
            $query->where(function($q) {
                $q->where('username', 'like', '%' . $this->search . '%')
                  ->orWhere('identity', 'like', '%' . $this->search . '%');
            });
        }

        // Orden de consumo (Hack para ordenar strings de MikroTik como 1h2m)
        $query->orderBy('tiempo_consumido', $this->sortDirection);

        return view('livewire.mikrotik.data.tickets-history', [
            'tickets' => $query->paginate(15),
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::when($user->role !== 'admin', function($q) use ($user) {
                            return $q->where('user_id', $user->id);
                         })->get(),
            'planes'  => Plan::select('name')->distinct()->get()
        ])->layout('layouts.app');
    }
}