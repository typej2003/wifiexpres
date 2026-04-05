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
    public $sortDirection = 'desc';

    // Control de Modales
    public $isSyncModalOpen = false;
    public $isSummaryModalOpen = false; // Nuevo modal de resultados
    public $syncAmount = 50;
    public $showOverlay = false;

    // Resultados de la sincronización
    public $syncResults = [
        'nuevos' => 0,
        'actualizados' => 0,
        'sin_cambios' => 0
    ];

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function updatingSearch() { $this->resetPage(); }

    public function toggleSort()
    {
        $this->sortDirection = ($this->sortDirection === 'asc') ? 'desc' : 'asc';
    }

    public function openSyncModal() { $this->isSyncModalOpen = true; }
    public function closeSyncModal() { $this->isSyncModalOpen = false; }
    public function closeSummaryModal() { $this->isSummaryModalOpen = false; }

    public function syncData()
    {
        if (!$this->filterRouter) {
            session()->flash('error', 'Seleccione un router para sincronizar.');
            return;
        }

        $this->showOverlay = true;
        $this->isSyncModalOpen = false;

        // Reiniciar contadores
        $this->syncResults = ['nuevos' => 0, 'actualizados' => 0, 'sin_cambios' => 0];

        $router = Router::find($this->filterRouter);
        $mac = strtoupper($router->macAddress);
        $tid = "HSYNC" . time();

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

            // Reintentos para obtener el resultado
            $raw = null;
            for ($i = 0; $i < 10; $i++) {
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $raw = $res->json('data');
                    break;
                }
            }
            
            if ($raw) {
                $this->processSyncRawData($raw, $router->id);
                $this->isSummaryModalOpen = true; // Abrimos el resumen al terminar
            } else {
                session()->flash('error', 'El router no respondió a tiempo.');
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
        
        foreach ($filas as $fila) {
            $p = explode(',', $fila);
            if (count($p) < 3) continue;

            $uName = $p[0];
            $uptime = $p[3] ?: '0s';

            // --- LÓGICA DE COMPARACIÓN ---
            $ticketExistente = Ticket::where('router_id', $routerId)
                                     ->where('username', $uName)
                                     ->first();

            if (!$ticketExistente) {
                $this->syncResults['nuevos']++;
            } elseif ($ticketExistente->tiempo_consumido !== $uptime) {
                $this->syncResults['actualizados']++;
            } else {
                $this->syncResults['sin_cambios']++;
            }

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
        $query = Ticket::query()->with('router');

        if ($user->role !== 'admin') {
            $query->whereHas('router', function($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($this->filterAliado) {
            $query->whereHas('router', function($q) {
                $q->where('user_id', $this->filterAliado);
            });
        }

        if ($this->filterRouter) $query->where('router_id', $this->filterRouter);
        if ($this->filterPlan) $query->where('plan', $this->filterPlan);
        if ($this->filterEstado) $query->where('estado', $this->filterEstado);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('username', 'like', '%' . $this->search . '%')
                  ->orWhere('identity', 'like', '%' . $this->search . '%');
            });
        }

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