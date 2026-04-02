<?php

namespace App\Http\Livewire\Mikrotik\Ticket;

use Livewire\Component;
use App\Models\User;
use App\Models\Router;
use App\Models\Ticket;
use Livewire\WithPagination;

class ImprimirTickets extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filtros de Selección
    public $aliados = [];
    public $selectedAliado = null;
    public $routers = [];
    public $selectedRouter = null;

    // Lógica de Impresión
    public $tipo_impresion = 'lote';
    public $lote_imprimir;
    public $desde_ticket;
    public $hasta_ticket;

    public function mount()
    {
        // Cargamos los usuarios con rol aliado (ajusta el nombre del rol según tu DB)
        $this->aliados = User::where('role', 'aliado')->get();
    }

    public function updatedSelectedAliado($value)
    {
        $this->selectedRouter = null;
        if ($value) {
            $this->routers = Router::where('user_id', $value)->get();
        } else {
            $this->routers = [];
        }
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
        $tickets = [];
        if ($this->selectedRouter) {
            $tickets = Ticket::where('router_id', $this->selectedRouter)
                             ->latest('id')
                             ->paginate(15);
        }

        return view('livewire.mikrotik.ticket.imprimir-tickets', [
            'tickets' => $tickets
        ])->layout('layouts.app');
    }
}