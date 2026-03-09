<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\MetodoPago;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ListMetodosPagos extends AdminComponent
{

	public $state = [];

	public $metodo;

	public $showEditModal = false;

	public $metodoIdBeingRemoved = null;

	public $searchTerm = null;

    protected $queryString = ['searchTerm' => ['except' => '']];

    public $sortColumnName = 'created_at';

    public $sortDirection = 'desc';

    public $comercioId = 0;
    public $metodoId = 0;

	public function addNew()
	{   
    	$this->reset();

    	$this->showEditModal = false;

		$this->dispatchBrowserEvent('show-form');
	}

	public function createMetodo()
	{
		$validatedData = Validator::make($this->state, [
			'metodopago' => 'required',
			'descripcion' => 'nullable',
		])->validate();

		$cadena =str_replace(' ', '', $validatedData['metodopago']);

		$validatedData['metodo'] = strtolower($cadena);

    	MetodoPago::create($validatedData);

		// session()->flash('message', 'User added successfully!');

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Método de pago agregado satisfactoriamente!']);
	}

	public function edit(MetodoPago $metodo)
	{
		$this->reset();

		$this->showEditModal = true;

		$this->metodo = $metodo;

		$this->state = $metodo->toArray();

		$this->dispatchBrowserEvent('show-form');
	}

	public function updateMetodo()
	{
		$validatedData = Validator::make($this->state, [
			'metodopago' => 'required',
			'descripcion' => 'nullable',
		])->validate();

		$cadena =str_replace(' ', '', $validatedData['metodopago']);

		$validatedData['metodo'] = strtolower($cadena);
		
		$this->metodo->update($validatedData);

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Método de Pago actualizado satisfactoriamente!']);
	}

	public function confirmMetodoRemoval($metodoId)
	{
		$this->metodoIdBeingRemoved = $metodoId;

		$this->dispatchBrowserEvent('show-delete-modal');
	}

	public function deleteMetodo()
	{
		$metodo = MetodoPago::findOrFail($this->metodoIdBeingRemoved);

		$metodo->delete();

		$this->dispatchBrowserEvent('hide-delete-modal', ['message' => 'Método de Pago eliminado satisfactoriamente!']);
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
        
        $metodos = MetodoPago::query();
        
    	$metodos = $metodos
            ->where(function($q){
                $q->where('metodopago', 'like', '%'.$this->searchTerm.'%');                
            })
    		->orderBy($this->sortColumnName, $this->sortDirection)
            ->paginate(15);
        
        return view('livewire.admin.settings.list-metodos-pagos', [
        	'metodos' => $metodos,
        ]);
    }
}
