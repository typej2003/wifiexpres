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

    // Filtros
    public $selectedAliado = null;
    public $selectedRouter = null;
    public $routerStatus = [];

    // Lógica de Impresión
    public $tipo_impresion = 'lote';
    public $lote_imprimir;
    public $desde_ticket;
    public $hasta_ticket;
    
    protected $bridgeUrl = "http://188.95.113.44:3000";

    public function mount()
    {
        $user = Auth::user();
        if (!in_array($user->role, ['admin', 'aliado'])) {
            abort(403);
        }

        if ($user->role === 'aliado') {
            $this->selectedAliado = $user->id;
        }

        $this->refreshStatus();
    }

    public function refreshStatus()
    {
        try {
            $response = Http::timeout(5)->get("{$this->bridgeUrl}/api/routers-online");
            if ($response->successful()) {
                $user = Auth::user();
                $activeMacs = collect($response->json())->map(fn($item) => strtoupper(trim($item['mac'])))->toArray();
                $this->routerStatus = Router::when($user->role !== 'admin', fn($q) => $q->where('user_id', $user->id))
                    ->get()
                    ->mapWithKeys(function ($r) use ($activeMacs) {
                        return [$r->id => in_array(strtoupper(trim($r->macAddress)), $activeMacs)];
                    })->toArray();
            }
        } catch (\Exception $e) { $this->routerStatus = []; }
    }

    public function updatedSelectedAliado()
    {
        $this->selectedRouter = null;
        $this->resetPage();
    }

    public function printRange()
    {
        $this->validate(['selectedRouter' => 'required']);

        if ($this->tipo_impresion == 'lote') {
            $this->validate(['lote_imprimir' => 'required']);
            $patron = "{$this->selectedRouter}-{$this->lote_imprimir}-";
            
            $primero = Ticket::where('router_id', $this->selectedRouter)
                ->where('identity', 'LIKE', $patron . '%')->orderBy('identity', 'asc')->first();
            $ultimo = Ticket::where('router_id', $this->selectedRouter)
                ->where('identity', 'LIKE', $patron . '%')->orderBy('identity', 'desc')->first();

            if (!$primero) {
                session()->flash('error', 'No se hallaron tickets para este lote.');
                return;
            }
            $desde = $primero->identity;
            $hasta = $ultimo->identity;
        } else {
            $this->validate(['desde_ticket' => 'required', 'hasta_ticket' => 'required']);
            $desde = $this->desde_ticket;
            $hasta = $this->hasta_ticket;
        }

        // Generamos la URL usando tu ruta 'tickets.print'
        $url = route('tickets.print', [
            'router_id' => $this->selectedRouter, 
            'desde' => $desde, 
            'hasta' => $hasta
        ]);

        $this->dispatchBrowserEvent('abrirImpresion', ['url' => $url]);
    }

    public function render()
    {
        $user = Auth::user();
        return view('livewire.mikrotik.ticket.imprimir-tickets', [
            'aliados' => User::where('role', 'aliado')->get(),
            'routersList' => Router::where('is_active', true)
                ->when($user->role !== 'admin', fn($q) => $q->where('user_id', $user->id))
                ->when($user->role === 'admin' && $this->selectedAliado, fn($q) => $q->where('user_id', $this->selectedAliado))
                ->orderBy('identity', 'asc')
                ->get(),
            'tickets' => $this->selectedRouter ? Ticket::where('router_id', $this->selectedRouter)->latest('id')->paginate(10) : []
        ])->layout('layouts.app');
    }
}