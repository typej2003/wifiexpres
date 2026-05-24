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
use Illuminate\Http\Request;

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
    public $filterOrigen = ''; 
    public $filterActivado = false; // Nueva propiedad
    public $sortDirection = 'desc';

    // Control de Modales e Impresión
    public $isSyncModalOpen = false;
    public $isSummaryModalOpen = false;
    public $syncAmount = 50;
    public $showOverlay = false;
    public $routerStatus = [];

    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilterAliado() { $this->resetPage(); }
    public function updatingFilterRouter() { $this->resetPage(); }
    public function updatingFilterPlan() { $this->resetPage(); }
    public function updatingFilterEstado() { $this->resetPage(); }
    public function updatingFilterOrigen() { $this->resetPage(); }
    public function updatingFilterActivado() { $this->resetPage(); }

    public function toggleSort()
    {
        $this->sortDirection = ($this->sortDirection === 'asc') ? 'desc' : 'asc';
    }

    public function openSyncModal() { $this->isSyncModalOpen = true; }
    public function closeSyncModal() { $this->isSyncModalOpen = false; }
    public function closeSummaryModal() { $this->isSummaryModalOpen = false; }

    /**
     * Consulta el bridge para saber qué routers están conectados actualmente
     */
    public function refreshStatus()
    {
        try {
            $response = Http::timeout(2)->get('http://188.95.113.44:3000/api/routers-online');
            $activeMacs = $response->successful() ? collect($response->json())->pluck('mac')->toArray() : [];

            $user = Auth::user();
            $routers = Router::where('is_active', true)->when($user->role !== 'admin', fn($q) => $q->where('user_id', $user->id))->get();
            foreach ($routers as $r) {
                $this->routerStatus[$r->id] = in_array(strtoupper(trim($r->macAddress)), $activeMacs);
            }
        } catch (\Exception $e) {}
    }

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

        $comando = ":local count 0; :local res \"D:\"; :foreach i in=[/ip hotspot user find where name!=\"default-trial\"] do={ " .
                   ":if (\$count < {$this->syncAmount}) do={ " .
                   ":local n [/ip hotspot user get \$i name]; :local p [/ip hotspot user get \$i password]; " .
                   ":local pr [/ip hotspot user get \$i profile]; :local u [/ip hotspot user get \$i uptime]; " .
                   ":local c [/ip hotspot user get \$i comment]; " .
                   ":set res (\$res . \$n . \",\" . \$p . \",\" . \$pr . \",\" . \$u . \",\" . \$c . \"|\"); " .
                   ":set count (\$count + 1); " .
                   "} }; /tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        try {
            Http::timeout(15)->withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim($comando), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

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
                $this->isSummaryModalOpen = true;
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
            Ticket::updateOrCreate(
                ['router_id' => $routerId, 'username' => $p[0]],
                ['tiempo_consumido' => $p[3] ?: '0s', 'estado' => ($p[3] !== '0s') ? 'en_uso' : 'disponible', 'sincronizado' => true]
            );
        }
    }

    // MÉTODO PARA LA IMPRESIÓN (GET)
    public function printReport(Request $request)
    {
        $user = Auth::user();
        $query = Ticket::query()->with(['router', 'router.user']);

        if ($user->role !== 'admin') {
            $query->whereHas('router', fn($q) => $q->where('user_id', $user->id));
        } elseif ($request->aliado) {
            $query->whereHas('router', fn($q) => $q->where('user_id', $request->aliado));
        }

        if ($request->router) $query->where('router_id', $request->router);
        if ($request->plan) $query->where('plan', $request->plan);
        if ($request->estado) $query->where('estado', $request->estado);
        if ($request->activado === 'true') $query->where('activado', 1);

        if ($request->origen === 'tickets') {
            $query->where(fn($q) => $q->where('identity', 'like', '%Lote%')->orWhere('identity', 'like', '%2026-04%'));
        } elseif ($request->origen === 'pasarela') {
            $query->where('identity', 'like', '%IMP-%')->where('identity', 'not like', '%IMP-T-%');
        } elseif ($request->origen === 'trial') {
            $query->where('identity', 'like', '%IMP-T-%');
        }

        if ($request->search) {
            $query->where(fn($q) => $q->where('username', 'like', "%{$request->search}%")->orWhere('identity', 'like', "%{$request->search}%"));
        }

        $tickets = $query->orderBy('tiempo_consumido', $request->sort ?? 'desc')->get();
        return view('pdf.tickets-report', compact('tickets'));
    }

    public function render()
    {
        $user = Auth::user();
        $query = Ticket::query()->with(['router', 'router.user']);

        if ($user->role !== 'admin') {
            $query->whereHas('router', fn($q) => $q->where('user_id', $user->id));
        } elseif ($this->filterAliado) {
            $query->whereHas('router', fn($q) => $q->where('user_id', $this->filterAliado));
        }

        if ($this->filterRouter) $query->where('router_id', $this->filterRouter);
        if ($this->filterPlan) $query->where('plan', $this->filterPlan);
        if ($this->filterEstado) $query->where('estado', $this->filterEstado);
        if ($this->filterActivado) $query->where('activado', 1);

        if ($this->filterOrigen === 'tickets') {
            $query->where(fn($q) => $q->where('identity', 'like', '%Lote%')->orWhere('identity', 'like', '%2026-04%'));
        } elseif ($this->filterOrigen === 'pasarela') {
            $query->where('identity', 'like', '%IMP-%')->where('identity', 'not like', '%IMP-T-%');
        } elseif ($this->filterOrigen === 'trial') {
            $query->where('identity', 'like', '%IMP-T-%');
        }
        
        if ($this->search) {
            $query->where(fn($q) => $q->where('username', 'like', "%{$this->search}%")->orWhere('identity', 'like', "%{$this->search}%"));
        }

        $query->orderBy('tiempo_consumido', $this->sortDirection);

        $this->refreshStatus();

        return view('livewire.mikrotik.data.tickets-history', [
            'tickets' => $query->paginate(15),
            'aliados' => User::where('role', 'aliado')->get(),
            'routers' => Router::where('is_active', true)
                ->when($user->role !== 'admin', fn($q) => $q->where('user_id', $user->id))
                ->orderBy('identity', 'asc')
                ->get(),
            'planes'  => Plan::select('name')->distinct()->get()
        ])->layout('layouts.app');
    }
}