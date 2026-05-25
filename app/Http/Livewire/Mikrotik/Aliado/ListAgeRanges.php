<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AgeRange;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ListAgeRanges extends Component
{
    use WithPagination;

    public $search = '';
    public $filterAliado = '';
    public $isAdmin = false;

    // Propiedades del Formulario
    public $isModalOpen = false;
    public $selected_id, $name, $min_age, $max_age, $user_id;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->isAdmin = Auth::user()->role === 'admin';
        if (!$this->isAdmin) {
            $this->user_id = Auth::id();
            $this->filterAliado = Auth::id();
        }
    }

    public function openModal()
    {
        $this->resetInputFields();
        $this->isModalOpen = true;
    }

    public function closeModal()
    {
        $this->isModalOpen = false;
    }

    private function resetInputFields()
    {
        $this->name = '';
        $this->min_age = '';
        $this->max_age = '';
        $this->selected_id = null;
        if (!$this->isAdmin) {
            $this->user_id = Auth::id();
        } else {
            $this->user_id = $this->filterAliado;
        }
    }

    public function edit($id)
    {
        $range = AgeRange::findOrFail($id);
        $this->selected_id = $id;
        $this->name = $range->name;
        $this->min_age = $range->min_age;
        $this->max_age = $range->max_age;
        $this->user_id = $range->user_id;
        $this->isModalOpen = true;
    }

    public function delete($id)
    {
        AgeRange::find($id)->delete();
        session()->flash('message', 'Rango de edad eliminado correctamente.');
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'min_age' => 'required|numeric|min:0',
            'max_age' => 'required|numeric|gte:min_age',
            'user_id' => 'required',
        ]);

        AgeRange::updateOrCreate(['id' => $this->selected_id], [
            'name' => $this->name,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            'user_id' => $this->user_id,
        ]);

        session()->flash('message', $this->selected_id ? 'Rango actualizado.' : 'Rango creado.');
        $this->closeModal();
    }

    public function render()
    {
        $query = AgeRange::query()->with('user');

        if (!$this->isAdmin) {
            $query->where('user_id', Auth::id());
        } elseif ($this->filterAliado) {
            $query->where('user_id', $this->filterAliado);
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return view('livewire.mikrotik.aliado.list-age-ranges', [
            'ageRanges' => $query->latest()->paginate(10),
            'aliados' => $this->isAdmin ? User::where('role', 'aliado')->get() : []
        ])->layout('layouts.app');
    }
}
