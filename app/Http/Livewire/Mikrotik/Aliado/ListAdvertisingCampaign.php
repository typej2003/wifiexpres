<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\AdvertisingCampaign;
use App\Models\AgeRange;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ListAdvertisingCampaign extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $filterAliado = '';
    public $isAdmin = false;
    
    // Propiedades del Formulario
    public $isModalOpen = false;
    public $selected_id, $name, $description, $target_gender = 'todos';
    public $age_range_id;
    public $media_type = 'imagen', $media, $current_media_path;
    public $question_text, $question_type = 'simple';
    public $options = []; // Array para las opciones dinámicas
    public $user_id;

    public function mount()
    {
        $this->isAdmin = Auth::user()->role === 'admin';
        $this->user_id = Auth::id();
        $this->age_range_id = 0;
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
        $this->description = '';
        $this->target_gender = 'todos';
        $this->age_range_id = 0;
        $this->media_type = 'imagen';
        $this->media = null;
        $this->question_text = '';
        $this->question_type = 'simple';
        $this->options = [];
        $this->selected_id = null;
        $this->current_media_path = null;
        if(!$this->isAdmin) $this->user_id = Auth::id();
    }

    // FUNCIÓN PARA CAMBIAR ESTADO ACTIVO/INACTIVO
    public function toggleStatus($id)
    {
        $campaign = AdvertisingCampaign::findOrFail($id);
        $campaign->active = !$campaign->active;
        $campaign->save();
    }

    public function edit($id)
    {
        $campaign = AdvertisingCampaign::findOrFail($id);
        $this->selected_id = $id;
        $this->name = $campaign->name;
        $this->description = $campaign->description;
        $this->target_gender = $campaign->target_gender;
        $this->age_range_id = $campaign->age_range_id;
        $this->media_type = $campaign->media_type;
        $this->question_text = $campaign->question_text;
        $this->question_type = $campaign->question_type;
        $this->options = $campaign->options ?? [];
        $this->user_id = $campaign->user_id;
        $this->current_media_path = $campaign->media_path;
        
        $this->isModalOpen = true;
    }

    public function addOption()
    {
        $this->options[] = '';
    }

    public function removeOption($index)
    {
        unset($this->options[$index]);
        $this->options = array_values($this->options);
    }

    public function delete($id)
    {
        $campaign = AdvertisingCampaign::findOrFail($id);
        if ($campaign->media_path) {
            Storage::disk('public')->delete($campaign->media_path);
        }
        $campaign->delete();
        session()->flash('message', 'Campaña eliminada correctamente.');
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'user_id' => 'required',
            'age_range_id' => 'required',
            'media' => $this->selected_id ? 'nullable|max:20480' : 'required|max:20480',
            'question_text' => 'required',
            'options' => $this->question_type != 'simple' ? 'required|array|min:2' : 'nullable',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'target_gender' => $this->target_gender,
            'age_range_id' => $this->age_range_id,
            'media_type' => $this->media_type,
            'question_text' => $this->question_text,
            'question_type' => $this->question_type,
            'options' => $this->question_type != 'simple' ? $this->options : null,
        ];

        if ($this->media) {
            if ($this->selected_id && $this->current_media_path) {
                Storage::disk('public')->delete($this->current_media_path);
            }
            $originalName = $this->media->getClientOriginalName();
            $path = $this->media->storeAs('campaign', $originalName, 'public');
            $data['media_path'] = $path;
        }

        AdvertisingCampaign::updateOrCreate(['id' => $this->selected_id], $data);

        session()->flash('message', $this->selected_id ? 'Campaña actualizada.' : 'Campaña creada.');
        $this->closeModal();
    }

    public function render()
    {
        $query = AdvertisingCampaign::query()->with('user');
        if (!$this->isAdmin) {
            $query->where('user_id', Auth::id());
        } else {
            if ($this->filterAliado) {
                $query->where('user_id', $this->filterAliado);
            }
        }
        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        // Obtener rangos de edad filtrados por el aliado seleccionado (o el logueado)
        // Esto asegura que al crear una campaña se vean solo los rangos del dueño de la misma.
        $ageRangesQuery = AgeRange::query();
        if (!$this->isAdmin) {
            $ageRangesQuery->where('user_id', Auth::id());
        } elseif ($this->user_id) {
            $ageRangesQuery->where('user_id', $this->user_id);
        }

        return view('livewire.mikrotik.aliado.list-advertising-campaign', [
            'campaigns' => $query->latest()->paginate(10),
            'aliados' => $this->isAdmin ? User::where('role', 'aliado')->get() : [],
            'ageRanges' => $ageRangesQuery->get()
        ])->layout('layouts.app');
    }
}