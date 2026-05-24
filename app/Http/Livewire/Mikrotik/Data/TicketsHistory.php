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

        $router = Router::find($this->filterRouter);

        // Validar si el router está online antes de proceder
        if (!($this->routerStatus[$router->id] ?? false)) {
            session()->flash('error', 'El router seleccionado no está en línea.');
            $this->isSyncModalOpen = false;
            return;
        }

        $this->showOverlay = true;
        $this->isSyncModalOpen = false;
        
        $mac = strtoupper(trim($router->macAddress));
        $tid = "SYNC_" . time();

        // Script Smart: Listar usuarios del hotspot con identidad (comment) y password
        $comando = ":local res \"D:\"; " .
                   ":foreach i in=[/ip hotspot user find where name!=\"default-trial\"] do={ " .
                   ":local n [/ip hotspot user get \$i name]; " .
                   ":local p [/ip hotspot user get \$i password]; " .
                   ":local pr [/ip hotspot user get \$i profile]; " .
                   ":local u [/ip hotspot user get \$i uptime]; " . 
                   ":local c [/ip hotspot user get \$i comment]; " .
                   ":set res (\$res . \$n . \",\" . \$p . \",\" . \$pr . \",\" . \$u . \",\" . \$c . \"|\"); " .
                   "}; " .
                   "/tool fetch url=\"{$this->bridgeUrl}/post-result?mac=$mac&tid=$tid\" http-method=post http-data=\$res keep-result=no;";

        try {
            $response = Http::timeout(10)->withHeaders(['x-mac' => $mac, 'x-id' => $tid])
                ->withBody(trim($comando), 'text/plain')
                ->post("{$this->bridgeUrl}/set-command");

            if (!$response->successful()) {
                throw new \Exception("El servidor Bridge no aceptó el comando.");
            }

            $raw = null;
            for ($i = 0; $i < 15; $i++) {
                sleep(1);
                $res = Http::get("{$this->bridgeUrl}/api/check-task-result", ['mac' => $mac, 'tid' => $tid]);
                if ($res->successful() && $res->json('status') === 'ready') {
                    $raw = $res->json('data');
                    break;
                }
            }
            
            if ($raw) {
                $count = $this->processSyncRawData($raw, $router->id);
                session()->flash('message', "Sincronización finalizada. Se actualizaron $count tickets.");
                $this->isSummaryModalOpen = true;
            } else {
                session()->flash('error', 'El servidor Bridge no recibió respuesta del MikroTik a tiempo.');
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error de comunicación con el servidor Bridge: ' . $e->getMessage());
        }

        $this->showOverlay = false;
    }

    private function processSyncRawData($raw, $routerId)
    {
        $datos = str_replace('D:', '', $raw);
        $filas = array_filter(explode('|', trim($datos, "| ")));
        
        $mikrotikUsernames = [];
        $planesCache = Plan::where('router_id', $routerId)->get()->keyBy('mikrotik_profile');

        $processedCount = 0;
        foreach ($filas as $fila) {
            $p = explode(',', $fila);
            if (count($p) < 3) continue;

            // $p[0]: name, $p[1]: password, $p[2]: profile, $p[3]: uptime, $p[4]: comment
            $uName = $p[0];
            $mikrotikUsernames[] = $uName;

            $profileName = $p[2];
            $uptimeReal = $p[3] ?: '0s'; 
            
            $planData = $planesCache->get($profileName);
            
            $costoSync = 0;
            $tiempoUsoSync = '0s';
            $nombrePlanSync = $profileName;

            if ($planData) {
                $nombrePlanSync = $planData->name;
                $planLower = strtolower($planData->name);
                $esGratis = preg_match('/neutro|cortesia|trial|gratis/i', $planLower);
                $costoSync = $esGratis ? 0 : $planData->price;
                $tiempoUsoSync = $planData->session_timeout ?? '0s';
            }

            $ticketExistente = Ticket::where('router_id', $routerId)->where('username', $uName)->first();
            $nuevoIdentity = (!empty($p[4]) && $p[4] !== "nil") ? $p[4] : ($ticketExistente ? $ticketExistente->identity : "IMP-{$uName}");

            Ticket::updateOrCreate(
                ['router_id' => $routerId, 'username' => $uName],
                [
                    'password'         => $p[1] ?? '',
                    'plan'             => $nombrePlanSync,
                    'costo'            => $costoSync,
                    'identity'         => $nuevoIdentity,
                    'tiempo_consumido' => $uptimeReal,
                    'tiempo_uso'       => $tiempoUsoSync,
                    'sincronizado'     => true,
                    'estado'           => ($uptimeReal !== '0s' && $uptimeReal !== '') ? 'en_uso' : 'disponible'
                ]
            );
            $processedCount++;
        }

        // Eliminar registros locales que ya no existen en el MikroTik (igual que en Aliado)
        Ticket::where('router_id', $routerId)->whereNotIn('username', $mikrotikUsernames)->delete();

        return $processedCount;
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