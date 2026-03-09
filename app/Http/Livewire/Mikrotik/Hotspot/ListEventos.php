<?php

namespace App\Http\Livewire\Mikrotik\Hotspot;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\Evento;
use App\Models\Router;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class ListEventos extends AdminComponent
{

	public $state = [];

	public $evento;

	public $showEditModal = false;

	public $eventoIdBeingRemoved = null;

	public $searchTerm = null;

    protected $queryString = ['searchTerm' => ['except' => '']];

    public $sortColumnName = 'created_at';

    public $sortDirection = 'desc';

	public function addNew()
	{
		$this->reset();

		$this->showEditModal = false;

		$this->dispatchBrowserEvent('show-form');
	}

	public function createEvento()
	{
		$validatedData = Validator::make($this->state, [
			'prefijo' => 'required',
			'nrorouter' => 'required',
		])->validate();

		$validatedData['user_id'] = auth()->user()->id;

		Evento::create($validatedData);

		// session()->flash('message', 'User added successfully!');

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Evento agregado satisfactoriamente!']);
	}

	public function edit(Evento $evento)
	{
		$this->reset();

		$this->showEditModal = true;

		$this->evento = $evento;

		$this->state = $evento->toArray();

		$this->dispatchBrowserEvent('show-form');
	}

	public function updateEvento()
	{
		$validatedData = Validator::make($this->state, [
			'prefijo' => 'required',
			'nrorouter' => 'required',
		])->validate();

		$this->evento->update($validatedData);

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Evento actualizado satisfactoriamente!']);
	}

	public function confirmEventoRemoval($eventoId)
	{
		$this->eventoIdBeingRemoved = $eventoId;

		$this->dispatchBrowserEvent('show-delete-modal');
	}

	public function deleteEvento()
	{
		$evento = Evento::findOrFail($this->eventoIdBeingRemoved);

		$evento->delete();

		$this->dispatchBrowserEvent('hide-delete-modal', ['message' => 'Evento eliminado satisfactoriamente!']);
	}

    public function sortBy($columnName)
    {
        if ($this->sortColumnName === $columnName) {
            $this->sortDirection = $this->swapSortDirection();
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortColumnName = $columnName;
    }

    public function swapSortDirection()
    {
        return $this->sortDirection === 'asc' ? 'desc' : 'asc';
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function render()
    {
		$routers = Router::where('nrorouter', auth()->user()->nrorouter)->get();

    	$eventos = Evento::query()
    		->where('prefijo', 'like', '%'.$this->searchTerm.'%')
			->where('user_id', auth()->user()->id)
            ->orderBy($this->sortColumnName, $this->sortDirection)
            ->paginate(15);

        return view('livewire.mikrotik.hotspot.list-eventos', [
        	'eventos' => $eventos,
			'routers' => $routers,
        ]);
    }

	
}
