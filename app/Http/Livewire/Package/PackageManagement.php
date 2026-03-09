<?php

namespace App\Http\Livewire\Package;

use Livewire\Component;
use App\Models\Package;
use App\Models\HotspotVersion;
use Livewire\WithPagination;

class PackageManagement extends Component
{
    use WithPagination;

    public $package_id, $name, $cost, $duration_months, $description, $offer_cost;
    public $hotspot_version_id; 
    public $limit_routers = 1; // Nuevo campo
    public $is_active = true;
    public $is_visible = true; 
    public $is_offer = false;
    public $isModalOpen = false;
    public $search = '';

    public $service_type = 'cortesia'; 
    public $commission_aliado = 70;
    public $commission_system = 30;

    protected $rules = [
        'name' => 'required|min:3',
        'hotspot_version_id' => 'required|exists:hotspot_versions,id',
        'service_type' => 'required|in:cortesia,reparto',
        'cost' => 'required|numeric|min:0',
        'offer_cost' => 'nullable|numeric|min:0',
        'duration_months' => 'required|integer|min:1',
        'limit_routers' => 'required|integer|min:1', // Validación del nuevo campo
        'commission_aliado' => 'required_if:service_type,reparto|numeric|min:0|max:100',
        'commission_system' => 'required_if:service_type,reparto|numeric|min:0|max:100',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'description' => 'nullable'
    ];

    public function render()
    {
        return view('livewire.package.package-management', [
            'packages' => Package::with('hotspotVersion')
                ->where('name', 'like', '%' . $this->search . '%')
                ->latest()
                ->paginate(10),
            'versions' => HotspotVersion::all()
        ])->layout('layouts.app');
    }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function edit($id)
    {
        $package = Package::findOrFail($id);
        $this->package_id = $id;
        $this->name = $package->name;
        $this->hotspot_version_id = $package->hotspot_version_id;
        $this->service_type = $package->service_type ?? 'cortesia';
        $this->cost = $package->cost;
        $this->offer_cost = $package->offer_cost;
        $this->duration_months = $package->duration_months;
        $this->limit_routers = $package->limit_routers ?? 1; // Cargar valor
        $this->commission_aliado = $package->commission_aliado ?? 70;
        $this->commission_system = $package->commission_system ?? 30;
        $this->is_offer = $package->is_offer;
        $this->is_active = $package->is_active;
        $this->is_visible = $package->is_visible;
        $this->description = $package->description;
        $this->openModal();
    }

    public function store()
    {
        $this->validate();

        $package = Package::updateOrCreate(['id' => $this->package_id], [
            'name' => $this->name,
            'hotspot_version_id' => $this->hotspot_version_id,
            'service_type' => $this->service_type,
            'cost' => $this->cost,
            'offer_cost' => $this->is_offer ? $this->offer_cost : null,
            'duration_months' => $this->duration_months,
            'limit_routers' => $this->limit_routers, // Guardar valor
            'commission_aliado' => $this->service_type == 'reparto' ? $this->commission_aliado : 0,
            'commission_system' => $this->service_type == 'reparto' ? $this->commission_system : 0,
            'is_offer' => $this->is_offer,
            'is_active' => $this->is_active,
            'is_visible' => $this->is_visible,
            'description' => $this->description,
        ]);

        if ($this->is_active && $this->hotspot_version_id) {
            HotspotVersion::where('id', $this->hotspot_version_id)->update(['is_active' => true]);
        }

        session()->flash('message', 'Plan configurado con éxito.');
        $this->closeModal();
    }

    public function toggleVisibility($id) { 
        $p = Package::find($id); 
        if($p) $p->update(['is_visible' => !$p->is_visible]); 
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; $this->resetErrorBag(); }
    
    private function resetInputFields() {
        $this->reset([
            'package_id', 'name', 'hotspot_version_id', 'cost', 'offer_cost', 
            'description', 'is_offer', 'service_type', 'commission_aliado', 
            'commission_system', 'is_visible', 'limit_routers'
        ]);
        $this->duration_months = 1;
        $this->limit_routers = 1;
        $this->is_active = true;
        $this->is_visible = true;
        $this->service_type = 'cortesia';
    }
}