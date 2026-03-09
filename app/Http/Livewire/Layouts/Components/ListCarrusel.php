<?php

namespace App\Http\Livewire\Layouts\Components;

use App\Http\Livewire\Admin\AdminComponent;
use App\Models\Carrusel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\WithFileUploads;

class ListCarrusel extends AdminComponent
{
    use WithFileUploads;

    public $state = [];
    public $carrusel; 
    public $showEditModal = false;
    public $carruselIdBeingRemoved = null;
    public $searchTerm = null;
    public $sortColumnName = 'order';
    public $sortDirection = 'asc';
    public $photo;
    public $iteration = 0;

    protected $queryString = ['searchTerm' => ['except' => '']];

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|max:2048', 
        ], [
            'photo.image' => 'El archivo debe ser una imagen.',
            'photo.max' => 'La imagen no debe pesar más de 2MB.',
        ]);
    }

    public function addNew()
    {
        $this->reset(['state', 'photo', 'showEditModal', 'carrusel']);
        $this->resetErrorBag();
        $this->iteration++;
        
        // Valores por defecto
        $this->state['active'] = 'active';
        $this->state['bannerside'] = 1;
        $this->state['device'] = 'd'; // d=desktop, m=mobile
        
        $this->showEditModal = false;
        $this->dispatchBrowserEvent('abrir-modal');
    }

    public function edit(Carrusel $carrusel)
    {
        $this->reset(['photo']);
        $this->resetErrorBag();
        $this->iteration++;

        $this->showEditModal = true;
        $this->carrusel = $carrusel;
        $this->state = $carrusel->toArray();
        $this->dispatchBrowserEvent('abrir-modal');
    }

    public function createCarrusel()
    {
        $validatedData = Validator::make($this->state, [
            'title' => 'required',
            'order' => 'required|integer',
            'active' => 'required',
            'bannerside' => 'required',
            'device' => 'required|in:d,m,t'
        ])->validate();

        $this->validate(['photo' => 'required|image|max:2048']);

        if ($this->photo) {
            $originalName = $this->photo->getClientOriginalName();
            $path = $this->photo->storeAs('/', $originalName, 'avatarscarrusel');
            $validatedData['avatar'] = $path;
        }

        Carrusel::create($validatedData);
        $this->reset(['photo', 'state']);
        $this->iteration++;

        $this->dispatchBrowserEvent('cerrar-modal', ['message' => '¡Banner creado con éxito!', 'type' => 'success']);
    }

    public function updateCarrusel()
    {
        $validatedData = Validator::make($this->state, [
            'title' => 'required',
            'order' => 'required|integer',
            'active' => 'required',
            'bannerside' => 'required',
            'device' => 'required|in:d,m,t'
        ])->validate();

        if ($this->photo) {
            $this->validate(['photo' => 'image|max:2048']);
            if (!empty($this->carrusel->avatar)) {
                Storage::disk('avatarscarrusel')->delete($this->carrusel->avatar);
            }
            $originalName = $this->photo->getClientOriginalName();
            $path = $this->photo->storeAs('/', $originalName, 'avatarscarrusel');
            $validatedData['avatar'] = $path;            
        }

        $this->carrusel->update($validatedData);
        $this->reset(['photo']);
        $this->iteration++;

        $this->dispatchBrowserEvent('cerrar-modal', ['message' => '¡Banner actualizado!', 'type' => 'success']);
    }

    public function confirmCarruselRemoval($id)
    {
        $this->carruselIdBeingRemoved = $id;
        $this->dispatchBrowserEvent('abrir-modal-eliminar');
    }

    public function deleteCarrusel()
    {
        $carrusel = Carrusel::findOrFail($this->carruselIdBeingRemoved);
        if ($carrusel->avatar) {
            Storage::disk('avatarscarrusel')->delete($carrusel->avatar);
        }
        $carrusel->delete();
        $this->dispatchBrowserEvent('cerrar-modal-eliminar', ['message' => 'Eliminado.', 'type' => 'warning']);
    }

    public function render()
    {
        $imagenes = Carrusel::query()
            ->where('bannerside', 1)
            ->where('title', 'like', '%'.$this->searchTerm.'%')
            ->orderBy($this->sortColumnName, $this->sortDirection)
            ->paginate(15);

        return view('livewire.layouts.components.list-carrusel', [
            'imagenes' => $imagenes,
        ]);
    }
}