<?php

namespace App\Http\Livewire\Mikrotik\Aliado;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads; // Necesario para imágenes/video
use App\Models\AdvertisingCampaign;
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
    public $selected_id, $name, $description, $target_gender = 'todos', $age_min = 0, $age_max = 99, $media_type = 'imagen', $media, $question, $user_id;

    public function mount()
    {
        $this->isAdmin = Auth::user()->role === 'admin';
        $this->user_id = Auth::id(); // Por defecto el usuario actual
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
        $this->age_min = 0;
        $this->age_max = 99;
        $this->media_type = 'imagen';
        $this->media = null;
        $this->question = '';
        $this->selected_id = null;
        if(!$this->isAdmin) $this->user_id = Auth::id();
    }

    public function save()
    {
        $this->validate([
            'name' => 'required',
            'user_id' => 'required',
            'media' => $this->selected_id ? 'nullable' : 'required|max:20480', // 20MB max para video
            'question' => 'required',
        ]);

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'target_gender' => $this->target_gender,
            'age_min' => $this->age_min,
            'age_max' => $this->age_max,
            'media_type' => $this->media_type,
            'question' => $this->question,
        ];

        if ($this->media) {
            $path = $this->media->store('campaigns', 'public');
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
            if ($this->filterAliado) $query->where('user_id', $this->filterAliado);
        }

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return view('livewire.mikrotik.aliado.list-advertising-campaign', [
            'campaigns' => $query->latest()->paginate(10),
            'aliados' => $this->isAdmin ? User::where('role', 'aliado')->get() : []
        ])->layout('layouts.app');
    }
}