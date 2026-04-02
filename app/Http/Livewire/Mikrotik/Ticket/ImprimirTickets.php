<?php

namespace App\Http\Livewire\Mikrotik\Ticket;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\Ticket;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class ImprimirTickets extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filtros de Selección
    public $selectedAliado = null;
    public $selectedRouter = null;
    public $routerStatus = [];

    // Lógica de Impresión
    public $tipo_impresion = 'lote';
    public $lote_imprimir;
    public $desde_ticket;
    public $hasta_ticket;
    
    // Configuración del Bridge
    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Acceso denegado.');
        }
        $this->refreshStatus();
    }

    public function refreshStatus()
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
        $this->selectedRouter = null;
        $this->refreshStatus();
        $this->resetPage();
    }

    public function updatedSelectedRouter()
    {
        $this->resetPage();
    }

    public function printRange()
    {
        $this->validate([
            'selectedRouter' => 'required',
            'tipo_impresion' => 'required'
        ]);

        if ($this->tipo_impresion == 'lote') {
            $this->validate(['lote_imprimir' => 'required']);
            
            $patron = "{$this->selectedRouter}-{$this->lote_imprimir}-";
            $primero = Ticket::where('router_id', $this->selectedRouter)
                             ->where('identity', 'LIKE', $patron . '%')
                             ->orderBy('identity', 'asc')
                             ->first();
            
            $ultimo = Ticket::where('router_id', $this->selectedRouter)
                            ->where('identity', 'LIKE', $patron . '%')
                            ->orderBy('identity', 'desc')
                            ->first();

            if (!$primero) {
                session()->flash('error', 'No se encontraron tickets para el lote especificado.');
                return;
            }

            $desde = $primero->identity;
            $hasta = $ultimo->identity;
        } else {
            $this->validate([
                'desde_ticket' => 'required',
                'hasta_ticket' => 'required'
            ]);
            $desde = $this->desde_ticket;
            $hasta = $this->hasta_ticket;
        }

        $url = route('tickets.print', [
            'router_id' => $this->selectedRouter, 
            'desde' => $desde, 
            'hasta' => $hasta
        ]);

        $this->dispatchBrowserEvent('abrirImpresion', ['url' => $url]);
    }

    public function render()
    {
        $aliados = User::where('role', 'aliado')->get();
        $routersList = Router::when($this->selectedAliado, function($q) {
            $q->where('user_id', $this->selectedAliado);
        })->get();

        $tickets = [];
        if ($this->selectedRouter) {
            $tickets = Ticket::where('router_id', $this->selectedRouter)
                             ->latest('id')
                             ->paginate(15);
        }

        return view('livewire.mikrotik.ticket.imprimir-tickets', [
            'aliados' => $aliados,
            'routersList' => $routersList,
            'tickets' => $tickets
        ])->layout('layouts.app');
    }
}