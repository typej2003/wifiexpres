<?php

namespace App\Http\Livewire\Admin\Settings;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\Area;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;

class ListAreas extends AdminComponent
{
	use WithFileUploads;

	public $state = [];

	public $area;

	public $showEditModal = false;

	public $areaIdBeingRemoved = null;

	public $searchTerm = null;

    protected $queryString = ['searchTerm' => ['except' => '']];

    public $sortColumnName = 'created_at';

    public $sortDirection = 'desc';

    public function mount()
    {
    	
    	
    }

    public function addNew()
	{
		$this->reset();
		
		$this->showEditModal = false;

		$this->dispatchBrowserEvent('show-form');
	}

	public function createArea()
	{
		$validatedData = Validator::make($this->state, [
			'name' => 'required',
		])->validate();

		Area::create($validatedData);

		// session()->flash('message', 'User added successfully!');

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Area agregada satisfactoriamente!']);
	}

	public function edit(Area $area)
	{
		$this->reset();
		
		$this->showEditModal = true;

		$this->area = $area;

		$this->state = $area->toArray();

		$this->dispatchBrowserEvent('show-form');
	}

	public function updateArea()
	{
		$validatedData = Validator::make($this->state, [
			'name' => 'required',
		])->validate();

		$this->area->update($validatedData);

		$this->dispatchBrowserEvent('hide-form', ['message' => 'Area actualizada satisfactoriamente!']);
	}

	public function confirmAreaRemoval($areaId)
	{
		$this->areaIdBeingRemoved = $areaId;

		$this->dispatchBrowserEvent('show-delete-modal');
	}

	public function deleteArea()
	{
		$area = Area::findOrFail($this->areaIdBeingRemoved);

		$area->delete();

		$this->dispatchBrowserEvent('hide-delete-modal', ['message' => 'Area eliminada satisfactoriamente!']);
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
    	$areas = Area::query()
    		->where('name', 'like', '%'.$this->searchTerm.'%')
    		->orderBy($this->sortColumnName, $this->sortDirection)
            ->paginate(15);

        return view('livewire.admin.settings.list-areas', [
        	'areas' => $areas,
        ]);
    }
}
