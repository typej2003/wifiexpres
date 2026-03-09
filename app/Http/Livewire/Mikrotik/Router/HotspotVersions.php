<?php

namespace App\Http\Livewire\Mikrotik\Router;

use Livewire\Component;
use App\Models\HotspotVersion;

class HotspotVersions extends Component
{
    public $name, $description, $code, $version_id;
    public $isOpen = false;

    protected $rules = [
        'name' => 'required|min:3',
        'description' => 'nullable|string',
        'code' => 'required',
    ];

    public function openModal()
    {
        $this->resetInput();
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->resetErrorBag();
    }

    private function resetInput()
    {
        $this->name = '';
        $this->description = '';
        $this->code = '';
        $this->version_id = null;
    }

    public function save()
    {
        $this->validate();

        HotspotVersion::updateOrCreate(['id' => $this->version_id], [
            'name' => $this->name,
            'description' => $this->description,
            'code' => $this->code,
        ]);

        session()->flash('message', 'Plantilla guardada correctamente.');
        $this->closeModal();
    }

    public function edit($id)
    {
        $version = HotspotVersion::findOrFail($id);
        $this->version_id = $id;
        $this->name = $version->name;
        $this->description = $version->description;
        $this->code = $version->code;
        $this->isOpen = true;
    }

    public function delete($id)
    {
        HotspotVersion::find($id)->delete();
        session()->flash('message', 'Plantilla eliminada correctamente.');
    }

    public function render()
    {
        return view('livewire.mikrotik.router.hotspot-versions', [
            'versions' => HotspotVersion::latest()->get()
        ])->layout('layouts.app');
    }
}